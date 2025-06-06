import sys
import re
import json
import pickle
import os
from sentence_transformers import SentenceTransformer, util
import numpy as np

# Load model
model = SentenceTransformer('all-MiniLM-L6-v2')

# Paths to taxonomy and embedding files
acare_taxonomy_file = "taxonomy.txt"
nasa_taxonomy_file = "nasa_taxonomy.txt"
acare_embeddings_file = "acare_embeddings.pkl"
nasa_embeddings_file = "nasa_embeddings.pkl"

# Function to load or generate embeddings
def load_or_generate_embeddings(taxonomy_file, embeddings_file, model):
    if os.path.exists(embeddings_file):
        with open(embeddings_file, 'rb') as f:
            data = pickle.load(f)
            return data['categories'], data['embeddings']
    
    # Read taxonomy and generate embeddings
    dominio_atual = ""
    categories = []
    with open(taxonomy_file, encoding="utf-8") as f:
        for linha in f:
            linha = linha.strip()
            if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):
                dominio_atual = linha
            elif re.match(r"^\d+\.\d+", linha):
                categories.append((dominio_atual, linha))
    
    # Generate embeddings
    subdomains = [sub for _, sub in categories]
    embeddings = model.encode(subdomains, convert_to_tensor=False)
    
    # Save embeddings
    with open(embeddings_file, 'wb') as f:
        pickle.dump({'categories': categories, 'embeddings': embeddings}, f)
    
    return categories, embeddings

# Load embeddings
acare_categories, acare_embeddings = load_or_generate_embeddings(acare_taxonomy_file, acare_embeddings_file, model)
nasa_categories, nasa_embeddings = load_or_generate_embeddings(nasa_taxonomy_file, nasa_embeddings_file, model)

# Get input from command line
if len(sys.argv) < 2:
    print(json.dumps({"error": "No input text provided"}))
    sys.exit(1)

texto_projeto = sys.argv[1]
embed_projeto = model.encode(texto_projeto, convert_to_tensor=False)

# Function to classify taxonomy
def classify_taxonomy(categories, embeddings, embed_projeto):
    similarities = util.cos_sim(embed_projeto, embeddings)[0]
    results = [
        {"domain": domain, "subdomain": subdomain, "similarity": float(sim)}
        for (domain, subdomain), sim in zip(categories, similarities)
    ]
    results.sort(key=lambda x: x["similarity"], reverse=True)
    return results[:3]

# Classify and output results
try:
    resultados_acare = classify_taxonomy(acare_categories, acare_embeddings, embed_projeto)
    resultados_nasa = classify_taxonomy(nasa_categories, nasa_embeddings, embed_projeto)
    
    output = {
        "acare": resultados_acare,
        "nasa": resultados_nasa
    }
    print(json.dumps(output))
except Exception as e:
    print(json.dumps({"error": str(e)}))