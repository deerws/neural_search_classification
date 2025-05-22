import re
import csv

with open("resultados_classificacao.txt", "r", encoding="utf-8") as f:
    linhas = f.read().split("\n")

dados = []
projeto = {}

for linha in linhas:
    if linha.startswith("=== Projeto #"):
        if projeto:
            dados.append(projeto)
        projeto = {"Classificações ACARE": [], "Classificações NASA": []}
    elif linha.startswith("🎓 Programa:"):
        projeto["Programa"] = linha.replace("🎓 Programa:", "").strip()
    elif linha.startswith("🏷️ Área"):
        projeto["Área"] = linha.replace("🏷️ Área de Concentração:", "").strip()
    elif linha.startswith("📝 Linha"):
        projeto["Linha de Pesquisa"] = linha.replace("📝 Linha de Pesquisa:", "").strip()
    elif linha.startswith("📁 Subdomínio:"):
        dominio = linha.replace("📁 Subdomínio:", "").strip()
        tipo = "ACARE" if "Classificação (ACARE):" in projeto else "NASA"
        projeto[f"Classificações {tipo}"].append(dominio)
    elif linha.startswith("📊 Similaridade:"):
        sim = float(linha.replace("📊 Similaridade:", "").strip())
        tipo = "ACARE" if "Classificação (ACARE):" in projeto else "NASA"
        projeto[f"Classificações {tipo}"][-1] += f" ({sim:.3f})"
    elif "Classificação (ACARE):" in linha:
        projeto["Classificação (ACARE):"] = True
    elif "Classificação (NASA):" in linha:
        projeto.pop("Classificação (ACARE):", None)

# Adiciona último projeto
if projeto:
    dados.append(projeto)

# Escreve CSV
with open("resultados_formatado.csv", "w", newline='', encoding="utf-8") as csvfile:
    writer = csv.writer(csvfile)
    header = ["Programa", "Área", "Linha de Pesquisa",
              "ACARE 1", "ACARE 2", "ACARE 3",
              "NASA 1", "NASA 2", "NASA 3",
              "Aprovado?", "Comentário"]
    writer.writerow(header)
    for proj in dados:
        row = [
            proj.get("Programa", ""),
            proj.get("Área", ""),
            proj.get("Linha de Pesquisa", ""),
        ]
        row += proj.get("Classificações ACARE", [])[:3] + [""] * (3 - len(proj.get("Classificações ACARE", [])))
        row += proj.get("Classificações NASA", [])[:3] + [""] * (3 - len(proj.get("Classificações NASA", [])))
        row += ["", ""]  # Aprovado? Comentário
        writer.writerow(row)
