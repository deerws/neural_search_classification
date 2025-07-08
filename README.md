# Neural Search Classification for Aerospace Research Projects

An intelligent classifier for automated categorization of aerospace research lines using semantic similarity and NLP techniques.

## 🎯 Overview

This project implements an automated classification system for research lines in graduate programs within aerospace fields. By leveraging advanced Natural Language Processing (NLP) techniques, the system accurately classifies research lines according to established taxonomies from ACARE (Advisory Council for Aeronautics Research in Europe) and NASA.

The classifier uses semantic similarity analysis between research line descriptions and taxonomy categories, powered by the SentenceTransformer model (all-MiniLM-L6-v2) for text embeddings and cosine similarity calculations.

**Key Objective**: Streamline the organization and analysis of aerospace research by automatically identifying the most relevant domains and subdomains within established taxonomies.

## ✨ Key Features

- **Structured Data Processing**: Intelligently extracts and parses information about programs, concentration areas, and research lines from hierarchical input files
- **Multi-Taxonomy Support**: Processes and classifies against both ACARE and NASA taxonomies simultaneously
- **Semantic Similarity Analysis**: Leverages advanced text embeddings to compare research lines with taxonomy categories
- **Top-K Classification**: Returns the top 3 most similar matches for each taxonomy with confidence scores
- **Persistent Results**: Automatically saves classification results with support for resuming interrupted processing
- **Quality Assurance**: Flags potential classification errors when semantic similarity falls below configurable thresholds
- **Progress Tracking**: Provides real-time feedback on classification progress

## 🛠️ Technology Stack

- **Python 3.7+**: Core programming language
- **SentenceTransformers**: State-of-the-art text embedding generation and similarity computation
- **Regular Expressions**: Advanced pattern matching for data extraction
- **Contextlib**: Efficient output redirection and file handling

## 🚀 Quick Start

### Prerequisites

- Python 3.7 or higher
- pip package manager

### Installation

1. **Clone the repository**:
   ```bash
   git clone https://github.com/deerws/neural_search_classification.git
   cd neural_search_classification
   ```

2. **Install dependencies**:
   ```bash
   pip install -r requirements.txt
   ```
   
   Or install manually:
   ```bash
   pip install sentence-transformers
   ```

3. **Prepare input files**:

   **Research Lines** (`linhas_pesquisa.txt`):
   ```
   Programa: Aerospace Engineering
   Área: Flight Dynamics and Control
   - Advanced Flight Control Systems
   - Autonomous Navigation Systems
   - Computational Fluid Dynamics
   
   Programa: Space Technology
   Área: Satellite Systems
   - Satellite Communication Systems
   - Orbital Mechanics and Mission Design
   ```

   **Taxonomies** (`taxonomy.txt` and `nasa_taxonomy.txt`):
   ```
   1. Aerodynamics and Flight Physics
   1.1 Computational Fluid Dynamics
   1.2 Experimental Aerodynamics
   1.3 Flight Physics and Dynamics
   
   2. Flight Systems and Technology
   2.1 Flight Control Systems
   2.2 Avionics and Systems Integration
   ```

4. **Review results**:
   Check the generated `resultados_classificacao.txt` file for classification results.

## 📊 Output Format

The classification results are saved in a structured, readable format:

```
=== Project #1 ===
🎓 Program: Aerospace Engineering
🏷️ Concentration Area: Flight Dynamics and Control
📝 Research Line: Advanced Flight Control Systems

🔍 Classification (ACARE):
Suggestion #1:
📂 Domain: 2. Flight Systems and Technology
📁 Subdomain: 2.1 Flight Control Systems
📊 Similarity: 0.823

Suggestion #2:
📂 Domain: 1. Aerodynamics and Flight Physics
📁 Subdomain: 1.3 Flight Physics and Dynamics
📊 Similarity: 0.756

Suggestion #3:
📂 Domain: 2. Flight Systems and Technology
📁 Subdomain: 2.2 Avionics and Systems Integration
📊 Similarity: 0.712

🔍 Classification (NASA):
Suggestion #1:
📂 Domain: 3. Flight Control and Navigation
📁 Subdomain: 3.1 Advanced Control Systems
📊 Similarity: 0.795

⚠️ Low similarity detected — potential classification error (NASA).
```

## ⚙️ Configuration

### Similarity Threshold
Adjust the similarity threshold for quality control:
```python
limiar = 0.75  # Default threshold (0.0-1.0)
```

### Model Selection
Change the embedding model if needed:
```python
model = SentenceTransformer('all-MiniLM-L6-v2')  # Default model
```

## 📈 Performance Notes

- **Dataset Size**: Optimized for small to medium datasets (up to 10,000 research lines)
- **Processing Time**: Approximately 1-2 seconds per research line
- **Memory Usage**: ~500MB for model loading + embedding storage
- **Accuracy**: Typical similarity scores range from 0.6-0.9

## 🔄 Advanced Usage

### Resuming Interrupted Processing
The system automatically detects and resumes from the last processed project:
```python
# The system checks resultados_classificacao.txt for the last processed project
# and continues from the next unprocessed entry
```

### Batch Processing
For large datasets, consider processing in batches:
```python
# Process in chunks to optimize memory usage
batch_size = 100
for i in range(0, len(research_lines), batch_size):
    batch = research_lines[i:i+batch_size]
    process_batch(batch)
```

## 🐛 Troubleshooting

### Common Issues

1. **Low similarity scores**: Check if research line descriptions are detailed enough
2. **Memory errors**: Reduce batch size or use a smaller model
3. **File encoding issues**: Ensure input files are UTF-8 encoded
4. **Missing dependencies**: Run `pip install -r requirements.txt`

### Debug Mode
Enable verbose output for troubleshooting:
```python
import logging
logging.basicConfig(level=logging.DEBUG)
```

## 🤝 Contributing

We welcome contributions! Please follow these steps:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📜 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 📞 Support

For questions, issues, or suggestions:

- **Email**: paes.andre33@gmail.com
- **Documentation**: [Wiki](https://github.com/deerws/neural_search_classification/wiki)

## 🙏 Acknowledgments

- ACARE for providing the aerospace taxonomy framework
- NASA for their comprehensive aerospace research taxonomy
- The SentenceTransformers team for their excellent library
- The open-source community for continuous support

## 📊 Citation

If you use this project in your research, please cite:

```bibtex
@software{neural_search_classification,
  author = {André Paes},
  title = {Neural Search Classification for Aerospace Research Projects},
  url = {https://github.com/deerws/neural_search_classification},
  year = {2025}
}
```
