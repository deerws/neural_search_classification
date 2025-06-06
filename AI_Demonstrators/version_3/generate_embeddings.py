from sentence_transformers import SentenceTransformer
import re
import pickle

# Load model
model = SentenceTransformer('all-MiniLM-L6-v2')

# Function to generate and save embeddings
def generate_embeddings(taxonomy_file, embeddings_file):
    dominio_atual = ""
    categories = []
    with open(taxonomy_file, encoding="utf-8") as f:
        for linha in f:
            linha = linha.strip()
            if re.match(r"^\d+\.", linha) and not re.match(r"^\d+\.\d+", linha):
                dominio_atual = linha
            elif re.match(r"^\d+\.\d+", linha):
                categories.append((dominio_atual, linha))
    
    subdomains = [sub for _, sub in categories]
    embeddings = model.encode(subdomains, convert_to_tensor=False)
    
    with open(embeddings_file, 'wb') as f:
        pickle.dump({'categories': categories, 'embeddings': embeddings}, f)
    print(f"Embeddings salvos em {embeddings_file}")

# Generate embeddings for both taxonomies
generate_embeddings("taxonomy.txt", "acare_embeddings.pkl")
generate_embeddings("nasa_taxonomy.txt", "nasa_embeddings.pkl")