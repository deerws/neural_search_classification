import pandas as pd
from datetime import datetime
import os
import chardet
import numpy as np

def detectar_encoding(arquivo):
    """Detecta o encoding do arquivo CSV"""
    with open(arquivo, 'rb') as f:
        resultado = chardet.detect(f.read())
    return resultado['encoding']

def limpar_dataframe(df):
    """Limpa o DataFrame substituindo NaN/INF por valores válidos"""
    # Fazer uma cópia para não modificar o original
    df_clean = df.copy()
    
    # Substituir NaN por string vazia ou 0
    df_clean = df_clean.fillna('')
    
    # Substituir infinitos por valores grandes mas finitos
    df_clean = df_clean.replace([np.inf, -np.inf], 999999)
    
    # Garantir que colunas numéricas sejam numéricas
    colunas_numericas = ['ID_Projeto', 'TRL_ID', 'Situacao_ID']
    for col in colunas_numericas:
        if col in df_clean.columns:
            df_clean[col] = pd.to_numeric(df_clean[col], errors='coerce').fillna(0).astype(int)
    
    return df_clean

def gerar_excel_bonito():
    """Lê o CSV e gera um Excel estilizado"""
    
    # Verificar se o arquivo existe
    if not os.path.exists('project.csv'):
        print("❌ Arquivo 'project.csv' não encontrado na pasta atual")
        print("📁 Arquivos na pasta:", [f for f in os.listdir('.') if f.endswith('.csv')])
        return
    
    try:
        # Detectar encoding
        print("🔍 Detectando encoding do arquivo...")
        encoding = detectar_encoding('project.csv')
        print(f"📝 Encoding detectado: {encoding}")
        
        # Tentar ler com encoding detectado, se não funcionar, tentar outros
        encodings_para_tentar = [encoding, 'latin-1', 'iso-8859-1', 'cp1252', 'utf-8']
        
        df = None
        for enc in encodings_para_tentar:
            try:
                print(f"📖 Tentando ler com encoding: {enc}...")
                df = pd.read_csv('project.csv', sep=';', encoding=enc)
                print(f"✅ Sucesso com encoding: {enc}")
                break
            except UnicodeDecodeError as e:
                print(f"❌ Falha com {enc}: {e}")
                continue
            except Exception as e:
                print(f"❌ Erro com {enc}: {e}")
                continue
        
        if df is None:
            print("❌ Não foi possível ler o arquivo com nenhum encoding")
            return
        
        print(f"✅ CSV lido com sucesso! {len(df)} projetos encontrados")
        print(f"📋 Colunas: {list(df.columns)}")
        
        # Limpar o DataFrame
        print("🧹 Limpando dados...")
        df = limpar_dataframe(df)
        
        # Nome do arquivo de saída
        data_hoje = datetime.now().strftime('%Y-%m-%d_%H-%M')
        nome_saida = f'relatorio_projetos_bonito_{data_hoje}.xlsx'
        
        # Criar Excel com estilos - SEM a opção options
        with pd.ExcelWriter(nome_saida, engine='xlsxwriter') as writer:
            # Obter objetos do Excel
            workbook = writer.book
            
            # ===== DEFINIR ESTILOS =====
            # Cabeçalho principal
            header_style = workbook.add_format({
                'bold': True, 'font_color': 'white', 'bg_color': '#1E3A8A',
                'border': 1, 'align': 'center', 'valign': 'vcenter', 'font_size': 14
            })
            
            # Cabeçalho das colunas
            col_header_style = workbook.add_format({
                'bold': True, 'font_color': 'white', 'bg_color': '#3B82F6',
                'border': 1, 'text_wrap': True, 'font_size': 10
            })
            
            # Linhas alternadas
            row_even = workbook.add_format({
                'bg_color': '#EFF6FF', 'border': 1, 'font_size': 9, 'text_wrap': True
            })
            
            row_odd = workbook.add_format({
                'bg_color': '#F8FAFC', 'border': 1, 'font_size': 9, 'text_wrap': True
            })
            
            # Destaque para projetos ativos
            row_active = workbook.add_format({
                'bg_color': '#DBEAFE', 'border': 1, 'font_size': 9, 'text_wrap': True, 'bold': True
            })
            
            # Estilo para números
            number_style = workbook.add_format({
                'bg_color': '#EFF6FF', 'border': 1, 'font_size': 9, 'align': 'center'
            })
            
            # Estilo para texto
            text_style = workbook.add_format({
                'bg_color': '#EFF6FF', 'border': 1, 'font_size': 9, 'text_wrap': True
            })
            
            # ===== CRIAR PLANILHA =====
            worksheet = workbook.add_worksheet('Projetos')
            
            # ===== CONFIGURAR LAYOUT =====
            # Ajustar largura das colunas
            worksheet.set_column('A:A', 8)   # ID_Projeto
            worksheet.set_column('B:B', 35)  # Nome_Projeto
            worksheet.set_column('C:C', 8)   # TRL_ID
            worksheet.set_column('D:D', 25)  # Nome_Laboratorio
            worksheet.set_column('E:E', 12)  # Abreviação_Laboratorio
            worksheet.set_column('F:F', 50)  # Descrição
            worksheet.set_column('G:G', 20)  # Link_Referencia
            worksheet.set_column('H:H', 25)  # Email_Projeto
            worksheet.set_column('I:I', 20)  # Responsavel
            worksheet.set_column('J:J', 12)  # Situacao_ID
            worksheet.set_column('K:K', 15)  # Data_Cadastro
            
            # ===== CABEÇALHO PRINCIPAL =====
            worksheet.merge_range('A1:K1', '🚀 RELATÓRIO DE PROJETOS - SC2C', header_style)
            worksheet.merge_range('A2:K2', f'📊 Gerado em: {datetime.now().strftime("%d/%m/%Y %H:%M")} | Total: {len(df)} projetos', header_style)
            
            # ===== ESCREVER CABEÇALHOS DAS COLUNAS =====
            for col_num, value in enumerate(df.columns.values):
                worksheet.write(2, col_num, value, col_header_style)
            
            # ===== ESCREVER DADOS =====
            for row_num in range(len(df)):
                actual_row = row_num + 3
                
                # Escolher estilo baseado na situação
                situacao = df.iloc[row_num]['Situacao_ID']
                if situacao == 2:  # Ativo
                    base_style = row_active
                elif row_num % 2 == 0:
                    base_style = row_even
                else:
                    base_style = row_odd
                
                # Aplicar estilo à linha
                for col_num in range(len(df.columns)):
                    value = df.iloc[row_num, col_num]
                    col_name = df.columns[col_num]
                    
                    # Determinar o estilo específico para esta célula
                    if col_name in ['ID_Projeto', 'TRL_ID', 'Situacao_ID']:
                        cell_style = number_style
                        # Garantir que seja número
                        try:
                            if pd.isna(value) or value == '':
                                value = 0
                            value = int(float(value))
                        except:
                            value = 0
                    else:
                        cell_style = base_style
                        # Garantir que seja string
                        if pd.isna(value) or value is None:
                            value = ''
                        else:
                            value = str(value)
                    
                    # Formatar datas se for a coluna de data
                    if col_name == 'Data_Cadastro' and value and str(value).strip() != '':
                        try:
                            # Tentar converter para formato brasileiro
                            if ' ' in str(value):
                                date_part = str(value).split(' ')[0]
                                value = pd.to_datetime(date_part).strftime('%d/%m/%Y')
                            elif len(str(value)) > 0:
                                value = pd.to_datetime(value).strftime('%d/%m/%Y')
                        except:
                            # Manter o valor original se não conseguir converter
                            pass
                    
                    # Escrever na célula
                    worksheet.write(actual_row, col_num, value, cell_style)
            
            # ===== ADICIONAR ESTATÍSTICAS =====
            stats_row = len(df) + 5
            
            # Calcular estatísticas
            total_ativos = len(df[df['Situacao_ID'] == 2]) if 'Situacao_ID' in df.columns else 0
            total_com_links = len(df[~df['Link_Referencia'].isin(['A preencher', '']) & (df['Link_Referencia'].notna()) & (df['Link_Referencia'] != '')]) if 'Link_Referencia' in df.columns else 0
            labs_unicos = df['Nome_Laboratorio'].nunique() if 'Nome_Laboratorio' in df.columns else 0
            total_com_responsavel = len(df[~df['Responsavel'].isin(['A preencher', '']) & (df['Responsavel'].notna()) & (df['Responsavel'] != '')]) if 'Responsavel' in df.columns else 0
            
            # Título das estatísticas
            worksheet.merge_range(f'A{stats_row}:K{stats_row}', '📈 RESUMO ESTATÍSTICO', header_style)
            
            # Estatísticas
            stats_data = [
                ['📋 Total de Projetos', len(df)],
                ['✅ Projetos Ativos', total_ativos],
                ['🔗 Projetos com Links', total_com_links],
                ['👤 Projetos com Responsável', total_com_responsavel],
                ['🏢 Laboratórios com Projetos', labs_unicos]
            ]
            
            for i, (label, value) in enumerate(stats_data):
                worksheet.write(stats_row + 1 + i, 0, label, col_header_style)
                worksheet.write(stats_row + 1 + i, 1, value, row_even)
            
            print(f"✅ Excel bonito gerado: {nome_saida}")
            print(f"📊 Estatísticas: {total_ativos} ativos, {total_com_links} com links, {labs_unicos} laboratórios")
            
    except Exception as e:
        print(f"❌ Erro ao processar arquivo: {e}")
        import traceback
        traceback.print_exc()

# Executar diretamente
if __name__ == "__main__":
    print("🎨 Gerando Excel bonito do project.csv...")
    gerar_excel_bonito()
    print("✨ Pronto! Verifique o arquivo .xlsx na pasta.")
