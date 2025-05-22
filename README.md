# taxonomy_automaton_NASA_AI

Aerospace Research Line Classifier
📋 Overview
This project implements an automatic classifier for research lines in graduate programs related to aerospace fields. It leverages Natural Language Processing (NLP) techniques to classify research lines according to predefined taxonomies, such as those from ACARE (Advisory Council for Aeronautics Research in Europe) and NASA. The classification is based on semantic similarity between the descriptions of research lines and taxonomy categories, using the SentenceTransformer model (all-MiniLM-L6-v2) for text embeddings and cosine similarity.
The goal is to streamline the organization and analysis of research lines by automatically identifying the most relevant domains and subdomains within aerospace taxonomies.

🚀 Features

Structured Data Parsing: Extracts information about programs, concentration areas, and research lines from a hierarchical file (linhas_pesquisa.txt).
Taxonomy Loading: Processes hierarchical taxonomies from ACARE and NASA using text files (taxonomy.txt and nasa_taxonomy.txt).
Semantic Classification: Uses text embeddings to compare research lines with taxonomy categories, returning the top 3 most similar matches for each taxonomy.
Result Persistence: Saves classification results to a file (resultados_classificacao.txt) and supports resuming processing from the last processed project.
Similarity Validation: Flags potential classification errors when semantic similarity falls below a configurable threshold (default: 0.75).


🛠️ Technologies Used

Python 3.x: Core programming language of the project.
SentenceTransformers: Library for generating text embeddings and computing cosine similarity.
Regular Expressions (re): For parsing and extracting data from text files.
Contextlib: For redirecting output to a file during processing.


📂 Project Structure

main.py: Main script containing the classification logic.
linhas_pesquisa.txt: Input file with the hierarchical structure of programs, areas, and research lines.
taxonomy.txt: ACARE taxonomy file.
nasa_taxonomy.txt: NASA taxonomy file.
resultados_classificacao.txt: Output file with classification results.


⚙️ Setup and Installation

Clone the Repository:
git clone https://github.com/your-username/aerospace-research-classifier.git
cd aerospace-research-classifier


Install Dependencies:Ensure you have Python 3.x installed. Then, install the required libraries using pip:
pip install sentence-transformers


Prepare Input Files:

linhas_pesquisa.txt: Add your research lines in the format:Programa: Program Name
Área: Concentration Area
- Research Line 1
- Research Line 2


taxonomy.txt and nasa_taxonomy.txt: Add the taxonomies in the format:1. Domain Name
1.1 Subdomain Name
1.2 Another Subdomain
2. Another Domain
2.1 Subdomain




Run the Script:Execute the main script to classify the research lines:
python main.py


Check Results:The classification results will be appended to resultados_classificacao.txt.



📊 Output Format
The output file (resultados_classificacao.txt) contains the classification results for each research line in the following format:
=== Projeto #1 ===
🎓 Programa: Program Name
🏷️ Área de Concentração: Concentration Area
📝 Linha de Pesquisa: Research Line Description

🔍 Classificação (ACARE):
Sugestão #1:
📂 Domínio: 1. Domain Name
📁 Subdomínio: 1.1 Subdomain Name
📊 Similaridade: 0.823

Sugestão #2:
[...]

🔍 Classificação (NASA):
Sugestão #1:
📂 Domínio: 1. Domain Name
📁 Subdomínio: 1.1 Subdomain Name
📊 Similaridade: 0.795

[...]
⚠️ Similaridade baixa — possível erro de classificação (NASA).


🔧 Usage Notes

Threshold Adjustment: The similarity threshold (default: 0.75) can be adjusted by modifying the limiar variable in the script.
Resuming Processing: The script automatically resumes from the last processed project by checking resultados_classificacao.txt.
Scalability: The script is designed for small to medium-sized datasets. For larger datasets, consider optimizing the embedding process or batching the computations.


🤝 Contributing
Contributions are welcome! To contribute:

Fork the repository.
Create a new branch for your feature or bugfix:git checkout -b feature-name


Make your changes and commit them:git commit -m "Add feature description"


Push your changes to your fork:git push origin feature-name


Open a pull request with a detailed description of your changes.


📧 Contact
For questions or suggestions, feel free to open an issue or contact the repository owner at your-email@example.com.

📜 License
This project is licensed under the MIT License. See the LICENSE file for details.
