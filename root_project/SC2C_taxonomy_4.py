import re
from sentence_transformers import SentenceTransformer, util
from contextlib import redirect_stdout

# Carrega o modelo
model = SentenceTransformer('all-MiniLM-L6-v2')

def carregar_taxonomia(arquivo):
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

def classificar_taxonomia(categorias, embed_projeto):
    resultados = []
    for dominio, sub in categorias:
        embed_sub = model.encode(sub)
        sim = util.cos_sim(embed_projeto, embed_sub).item()
        resultados.append((dominio, sub, sim))
    resultados.sort(key=lambda x: x[2], reverse=True)
    return resultados

def ler_estrutura_hierarquica(arquivo):
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

# Carrega dados
categorias_acare = carregar_taxonomia("taxonomy.txt")
categorias_nasa = carregar_taxonomia("nasa_taxonomy.txt")
projetos = ler_estrutura_hierarquica("linhas_pesquisa.txt")
limiar = 0.75

# Verifica quais projetos já foram processados
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

# Continua a escrita no arquivo
with open("resultados_classificacao.txt", "a", encoding="utf-8") as f_out:
    with redirect_stdout(f_out):
        for idx, (programa, area, pesquisa) in enumerate(projetos, 1):
            if idx <= ult_idx:
                continue  # pula já processados

            print(f"\n\n=== Projeto #{idx} ===")
            print(f"🎓 Programa: {programa}")
            print(f"🏷️ Área de Concentração: {area}")
            print(f"📝 Linha de Pesquisa: {pesquisa}")
            
            embed = model.encode(pesquisa)

            # Classificação ACARE
            resultados_acare = classificar_taxonomia(categorias_acare, embed)
            print("\n🔍 Classificação (ACARE):")
            for i, (dom, sub, sim) in enumerate(resultados_acare[:3], 1):
                print(f"\nSugestão #{i}:")
                print(f"📂 Domínio: {dom}")
                print(f"📁 Subdomínio: {sub}")
                print(f"📊 Similaridade: {sim:.3f}")
            if resultados_acare[0][2] < limiar:
                print("⚠️ Similaridade baixa — possível erro de classificação (ACARE).")

            # Classificação NASA
            resultados_nasa = classificar_taxonomia(categorias_nasa, embed)
            print("\n🔍 Classificação (NASA):")
            for i, (dom, sub, sim) in enumerate(resultados_nasa[:3], 1):
                print(f"\nSugestão #{i}:")
                print(f"📂 Domínio: {dom}")
                print(f"📁 Subdomínio: {sub}")
                print(f"📊 Similaridade: {sim:.3f}")
            if resultados_nasa[0][2] < limiar:
                print("⚠️ Similaridade baixa — possível erro de classificação (NASA).")
