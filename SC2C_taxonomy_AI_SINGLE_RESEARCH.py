import re
from sentence_transformers import SentenceTransformer, util

arquivo = "taxonomy.txt"

# Modelo BERT para comparação semântica
model = SentenceTransformer('all-MiniLM-L6-v2')

# Leitura domínios e subdomínios
dominio_atual = ""
categorias = []

with open(arquivo, encoding="utf-8") as f:
    for linha in f:
        linha = linha.strip()
        if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):  # Domínio "numero. nome"
            dominio_atual = linha
        elif re.match(r"^\d+\.\d+", linha):  # Subdomínio "numero.numero. nome"
            categorias.append((dominio_atual, linha))

# Leitura domínios e subdomínios ACARE
arquivo_acare = "taxonomy.txt"
dominio_atual_acare = ""
categorias_acare = []

with open(arquivo_acare, encoding="utf-8") as f:
    for linha in f:
        linha = linha.strip()
        if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):  # Domínio "numero. nome"
            dominio_atual_acare = linha
        elif re.match(r"^\d+\.\d+", linha):  # Subdomínio "numero.numero. nome"
            categorias_acare.append((dominio_atual_acare, linha))

# Leitura domínios e subdomínios NASA
arquivo_nasa = "nasa_taxonomy.txt"
dominio_atual_nasa = ""
categorias_nasa = []

with open(arquivo_nasa, encoding="utf-8") as f:
    for linha in f:
        linha = linha.strip()
        if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):  # Domínio "numero. nome"
            dominio_atual_nasa = linha
        elif re.match(r"^\d+\.\d+", linha):  # Subdomínio "numero.numero. nome"
            categorias_nasa.append((dominio_atual_nasa, linha))

# texto do projeto
texto_projeto = """
TEXT EXEMPLE
"""

# Codifica o texto do projeto
embed_projeto = model.encode(texto_projeto)

# Função para comparar e ordenar resultados
def classificar_taxonomia(categorias, embed_projeto):
    resultados = []
    for dominio, sub in categorias:
        embed_sub = model.encode(sub)
        sim = util.cos_sim(embed_projeto, embed_sub).item()
        resultados.append((dominio, sub, sim))
    resultados.sort(key=lambda x: x[2], reverse=True)
    return resultados

# Classificação ACARE
resultados_acare = classificar_taxonomia(categorias_acare, embed_projeto)

print("\n🔍 Texto do projeto classificado em (ACARE):")
for i, (dominio, sub, sim) in enumerate(resultados_acare[:3], 1):
    print(f"\nSugestão #{i}:")
    print(f"📂 Domínio: {dominio}")
    print(f"📁 Subdomínio: {sub}")
    print(f"📊 Similaridade: {sim:.3f}")

limiar = 0.75
if resultados_acare and resultados_acare[0][2] < limiar:
    print("⚠️ Similaridade baixa — possível erro de classificação (ACARE).")

# Classificação NASA
resultados_nasa = classificar_taxonomia(categorias_nasa, embed_projeto)

print("\n🔍 Texto do projeto classificado em (NASA):")
for i, (dominio, sub, sim) in enumerate(resultados_nasa[:3], 1):
    print(f"\nSugestão #{i}:")
    print(f"📂 Domínio: {dominio}")
    print(f"📁 Subdomínio: {sub}")
    print(f"📊 Similaridade: {sim:.3f}")

if resultados_nasa and resultados_nasa[0][2] < limiar:
    print("⚠️ Similaridade baixa — possível erro de classificação (NASA).")
