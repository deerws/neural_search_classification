import sys
import re
import json
from sentence_transformers import SentenceTransformer, util

# Load model
model = SentenceTransformer('all-MiniLM-L6-v2')

# Read ACARE taxonomy
arquivo_acare = "taxonomy.txt"
dominio_atual_acare = ""
categorias_acare = []

with open(arquivo_acare, encoding="utf-8") as f:
    for linha in f:
        linha = linha.strip()
        if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):
            dominio_atual_acare = linha
        elif re.match(r"^\d+\.\d+", linha):
            categorias_acare.append((dominio_atual_acare, linha))

# Read NASA taxonomy
arquivo_nasa = "nasa_taxonomy.txt"
dominio_atual_nasa = ""
categorias_nasa = []

with open(arquivo_nasa, encoding="utf-8") as f:
    for linha in f:
        linha = linha.strip()
        if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):
            dominio_atual_nasa = linha
        elif re.match(r"^\d+\.\d+", linha):
            categorias_nasa.append((dominio_atual_nasa, linha))

# Get input from command line
if len(sys.argv) < 2:
    print(json.dumps({"error": "No input text provided"}))
    sys.exit(1)

texto_projeto = sys.argv[1]
embed_projeto = model.encode(texto_projeto)

# Function to classify taxonomy
def classificar_taxonomia(categorias, embed_projeto):
    resultados = []
    for dominio, sub in categorias:
        embed_sub = model.encode(sub)
        sim = util.cos_sim(embed_projeto, embed_sub).item()
        resultados.append({"domain": dominio, "subdomain": sub, "similarity": sim})
    resultados.sort(key=lambda x: x["similarity"], reverse=True)
    return resultados[:3]

# Classify and output results
try:
    resultados_acare = classificar_taxonomia(categorias_acare, embed_projeto)
    resultados_nasa = classificar_taxonomia(categorias_nasa, embed_projeto)
    
    output = {
        "acare": resultados_acare,
        "nasa": resultados_nasa
    }
    print(json.dumps(output))
except Exception as e:
    print(json.dumps({"error": str(e)}))