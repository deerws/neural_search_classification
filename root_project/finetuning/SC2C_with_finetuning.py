import re
import pandas as pd
import numpy as np
from sentence_transformers import SentenceTransformer, util, InputExample, losses
from torch.utils.data import DataLoader
from contextlib import redirect_stdout
from datetime import datetime

# ==================== CONFIGURAÇÃO INICIAL ====================
class Config:
    MODEL_BASE = 'all-MiniLM-L6-v2'
    LIMIAR_SIMILARIDADE = 0.75
    LIMIAR_HISTORICO = 0.85
    BATCH_SIZE = 16
    EPOCHS = 3

# ==================== PREPARAÇÃO DE DADOS ====================
def carregar_taxonomia(arquivo):
    """Carrega a taxonomia do arquivo"""
    dominio_atual = ""
    categorias = []
    with open(arquivo, encoding="utf-8") as f:
        for linha in f:
            linha = linha.strip()
            if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):
                dominio_atual = linha
            elif re.match(r"^\d+\.\d+", linha):
                categorias.append((dominio_atual, linha))
    return categorias

def ler_estrutura_hierarquica(arquivo):
    """Lê a estrutura hierárquica das linhas de pesquisa"""
    dados = []
    programa = None
    area = None
    with open(arquivo, encoding="utf-8") as f:
        for linha in f:
            linha = linha.strip()
            if linha.startswith("Programa:"):
                programa = linha.replace("Programa:", "").strip()
            elif linha.startswith("Área:"):
                area = linha.replace("Área:", "").strip()
            elif linha.startswith("- "):
                pesquisa = linha.replace("- ", "").strip()
                dados.append((programa, area, pesquisa))
    return dados

def preparar_dados_treinamento(csv_path):
    """Prepara dados para fine-tuning a partir do CSV validado"""
    df = pd.read_csv(csv_path, sep=';')
    
    # Filtrar apenas classificações validadas
    dados_validados = df[df['Status'] == 'Aprovado']
    
    exemplos_treinamento = []
    
    for _, row in dados_validados.iterrows():
        if pd.notna(row['Linha de Pesquisa']) and pd.notna(row['Classificação']):
            # Texto de entrada: combinação de informações relevantes
            texto_entrada = f"{row['Linha de Pesquisa']} {row['Área']} {row['Programa']}"
            
            # Texto alvo: classificação validada
            texto_alvo = f"{row['Domínio']} {row['Subdomínio']} {row['Classificação']}"
            
            exemplos_treinamento.append(
                InputExample(texts=[texto_entrada, texto_alvo], label=1.0)
            )
    
    return exemplos_treinamento

# ==================== MODELO E CLASSIFICAÇÃO ====================
def classificar_taxonomia(model, categorias, embed_projeto):
    """Classifica usando similaridade decoseno"""
    resultados = []
    for dominio, sub in categorias:
        embed_sub = model.encode(sub)
        sim = util.cos_sim(embed_projeto, embed_sub).item()
        resultados.append((dominio, sub, sim))
    resultados.sort(key=lambda x: x[2], reverse=True)
    return resultados

def fine_tune_model(model, dados_treinamento, output_path="modelo_treinado"):
    """Realiza fine-tuning do modelo"""
    if len(dados_treinamento) == 0:
        return model
    
    train_dataloader = DataLoader(dados_treinamento, shuffle=True, batch_size=Config.BATCH_SIZE)
    train_loss = losses.MultipleNegativesRankingLoss(model)
    warmup_steps = int(len(train_dataloader) * 0.1)
    
    model.fit(
        train_objectives=[(train_dataloader, train_loss)],
        epochs=Config.EPOCHS,
        warmup_steps=warmup_steps,
        output_path=output_path,
        show_progress_bar=True
    )
    
    return model

# ==================== CLASSIFICADOR MELHORADO ====================
class ClassificadorMelhorado:
    def __init__(self, modelo_base, modelo_finetuned=None, limiar=Config.LIMIAR_SIMILARIDADE):
        self.modelo_base = modelo_base
        self.modelo_finetuned = modelo_finetuned or modelo_base
        self.limiar = limiar
        self.historico_validacoes = []
    
    def carregar_historico_validacoes(self, csv_path):
        """Carrega histórico de validações aprovadas"""
        df = pd.read_csv(csv_path, sep=';')
        dados_aprovados = df[df['Status'] == 'Aprovado']
        self.historico_validacoes = dados_aprovados.to_dict('records')
    
    def classificar_com_historico(self, programa, area, pesquisa):
        """Classifica usando histórico + modelo"""
        texto_entrada = f"{pesquisa} {area} {programa}"
        
        # 1. Verificar histórico primeiro
        resultado_historico = self._verificar_historico(texto_entrada)
        if resultado_historico:
            return resultado_historico
        
        # 2. Usar modelo se não encontrar no histórico
        return self.classificar_com_modelo(programa, area, pesquisa)
    
    def _verificar_historico(self, texto_entrada):
        """Verifica se há classificações similares no histórico"""
        if not self.historico_validacoes:
            return None
        
        embed_entrada = self.modelo_base.encode(texto_entrada)
        similaridades = []
        
        for validacao in self.historico_validacoes:
            texto_historico = f"{validacao['Linha de Pesquisa']} {validacao['Área']} {validacao['Programa']}"
            embed_historico = self.modelo_base.encode(texto_historico)
            sim = util.cos_sim(embed_entrada, embed_historico).item()
            similaridades.append((validacao, sim))
        
        similaridades.sort(key=lambda x: x[1], reverse=True)
        
        if similaridades and similaridades[0][1] > Config.LIMIAR_HISTORICO:
            melhor = similaridades[0][0]
            return {
                'dominio': melhor.get('Domínio', ''),
                'subdominio': melhor.get('Subdomínio', ''),
                'classificacao': melhor.get('Classificação', ''),
                'score': similaridades[0][1],
                'fonte': 'historico',
                'taxonomia': melhor.get('Domínio', '').split()[0] if 'Domínio' in melhor else 'ACARE'
            }
        
        return None
    
    def classificar_com_modelo(self, programa, area, pesquisa):
        """Classifica usando o modelo fine-tuned"""
        texto_entrada = f"{pesquisa} {area} {programa}"
        embed = self.modelo_finetuned.encode(texto_entrada)
        
        # Classificar para ambas as taxonomias
        resultado_acare = self._classificar_taxonomia(embed, "ACARE")
        resultado_nasa = self._classificar_taxonomia(embed, "NASA")
        
        # Escolher o melhor resultado baseado no score
        if resultado_acare['score'] > resultado_nasa['score']:
            return resultado_acare
        else:
            return resultado_nasa
    
    def _classificar_taxonomia(self, embed, tipo_taxonomia):
        """Classifica para uma taxonomia específica"""
        if tipo_taxonomia == "ACARE":
            categorias = carregar_taxonomia("taxonomy.txt")
        else:
            categorias = carregar_taxonomia("nasa_taxonomy.txt")
        
        resultados = classificar_taxonomia(self.modelo_finetuned, categorias, embed)
        
        return {
            'dominio': resultados[0][0],
            'subdominio': resultados[0][1],
            'classificacao': resultados[0][1],  # Usando subdomínio como classificação
            'score': resultados[0][2],
            'fonte': 'modelo',
            'taxonomia': tipo_taxonomia,
            'top3': resultados[:3]
        }

# ==================== SISTEMA DE FEEDBACK ====================
class SistemaFeedback:
    def __init__(self):
        self.feedback_data = []
    
    def coletar_feedback(self, classificacao_original, classificacao_corrigida, usuario):
        """Coleta feedback para melhoria contínua"""
        self.feedback_data.append({
            'original': classificacao_original,
            'corrigida': classificacao_corrigida,
            'usuario': usuario,
            'timestamp': datetime.now().isoformat()
        })
    
    def exportar_para_treinamento(self, output_file):
        """Exporta feedback para CSV"""
        if self.feedback_data:
            df = pd.DataFrame(self.feedback_data)
            df.to_csv(output_file, index=False)
            print(f"✅ Feedback exportado para {output_file}")

# ==================== PIPELINE PRINCIPAL ====================
def main():
    print("🚀 Iniciando sistema de classificação melhorado...")
    
    # 1. Carregar modelos
    print("📦 Carregando modelos...")
    modelo_base = SentenceTransformer(Config.MODEL_BASE)
    
    # 2. Preparar dados de treinamento
    print("🔧 Preparando dados para fine-tuning...")
    dados_treinamento = preparar_dados_treinamento("Book1.csv")
    
    # 3. Fine-tuning (se houver dados suficientes)
    if len(dados_treinamento) >= 20:
        print("🎯 Realizando fine-tuning...")
        modelo_treinado = fine_tune_model(modelo_base, dados_treinamento, "modelo_finetuned")
    else:
        print("⚠️ Dados insuficientes para fine-tuning, usando modelo base")
        modelo_treinado = modelo_base
    
    # 4. Criar classificador melhorado
    print("🤖 Criando classificador melhorado...")
    classificador = ClassificadorMelhorado(modelo_base, modelo_treinado)
    classificador.carregar_historico_validacoes("Book1.csv")
    
    # 5. Sistema de feedback
    sistema_feedback = SistemaFeedback()
    
    # 6. Exemplo de uso
    print("\n" + "="*50)
    print("🧪 TESTANDO CLASSIFICAÇÃO")
    print("="*50)
    
    # Testar com alguns exemplos
    exemplos_teste = [
        ("Engenharia Elétrica – PPGEEL", "Processamento de Energia", "Eletromagnetismo e Dispositivos Eletromagnéticos"),
        ("Engenharia Elétrica – PPGEEL", "Processamento de Informação", "Sistemas Embarcados"),
        ("Engenharia de Automação e Sistemas - PosAutomação", "Controle", "Modelagem, Simulação e Identificação de Sistemas")
    ]
    
    for programa, area, pesquisa in exemplos_teste:
        print(f"\n🔍 Classificando: {pesquisa}")
        resultado = classificador.classificar_com_historico(programa, area, pesquisa)
        
        print(f"   📊 Score: {resultado['score']:.3f}")
        print(f"   🏷️ Classificação: {resultado['classificacao']}")
        print(f"   📂 Domínio: {resultado['dominio']}")
        print(f"   📍 Fonte: {resultado['fonte']}")
        print(f"   🗂️ Taxonomia: {resultado['taxonomia']}")
    
    # 7. Salvar modelo treinado
    modelo_treinado.save("modelo_treinado_final")
    print(f"\n💾 Modelo salvo como 'modelo_treinado_final'")
    
    return classificador, sistema_feedback

# ==================== FUNÇÃO DE CLASSIFICAÇÃO ORIGINAL (COMPATIBILIDADE) ====================
def executar_classificacao_original():
    """Mantém compatibilidade com seu código original"""
    categorias_acare = carregar_taxonomia("taxonomy.txt")
    categorias_nasa = carregar_taxonomia("nasa_taxonomy.txt")
    projetos = ler_estrutura_hierarquica("linhas_pesquisa.txt")
    
    modelo = SentenceTransformer(Config.MODEL_BASE)
    classificador = ClassificadorMelhorado(modelo)
    classificador.carregar_historico_validacoes("Book1.csv")
    
    # Verificar projetos já processados
    try:
        with open("resultados_classificacao.txt", "r", encoding="utf-8") as f:
            conteudo_existente = f.read()
        ult_idx = 0
        for linha in conteudo_existente.splitlines():
            if linha.startswith("=== Projeto #"):
                ult_idx = int(re.search(r"#(\d+)", linha).group(1))
        print(f"🔁 Retomando a partir do projeto #{ult_idx + 1}")
    except FileNotFoundError:
        ult_idx = 0
        print("🆕 Iniciando do zero.")
    
    # Processar projetos
    with open("resultados_classificacao_melhorado.txt", "a", encoding="utf-8") as f_out:
        with redirect_stdout(f_out):
            for idx, (programa, area, pesquisa) in enumerate(projetos, 1):
                if idx <= ult_idx:
                    continue
                
                print(f"\n\n=== Projeto #{idx} ===")
                print(f"🎓 Programa: {programa}")
                print(f"🏷️ Área de Concentração: {area}")
                print(f"📝 Linha de Pesquisa: {pesquisa}")
                
                # Classificação melhorada
                resultado = classificador.classificar_com_historico(programa, area, pesquisa)
                
                print(f"\n🎯 Classificação Melhorada:")
                print(f"📊 Score: {resultado['score']:.3f}")
                print(f"🏷️ Classificação: {resultado['classificacao']}")
                print(f"📂 Domínio: {resultado['dominio']}")
                print(f"📍 Fonte: {resultado['fonte']}")
                print(f"🗂️ Taxonomia: {resultado['taxonomia']}")
                
                if resultado['score'] < Config.LIMIAR_SIMILARIDADE:
                    print("⚠️ Similaridade baixa — possível erro de classificação.")

if __name__ == "__main__":
    # Executar o sistema melhorado
    classificador, feedback_system = main()
    
    # Opcional: executar classificação completa
    executar = input("\n▶️ Executar classificação completa? (s/n): ")
    if executar.lower() == 's':
        executar_classificacao_original()
