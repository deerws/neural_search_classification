# Styled Excel Report Generator from CSV

A Python script that converts project CSV files into beautifully formatted Excel reports with automatic styling, statistics, and data cleaning.

## 📋 Table of Contents

- [Features](#features)
- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [How to Use](#how-to-use)
- [CSV Structure](#csv-structure)
- [How It Works](#how-it-works)
- [Styling Details](#styling-details)
- [Generated Statistics](#generated-statistics)
- [Troubleshooting](#troubleshooting)

## ✨ Features

- 🔍 **Automatic encoding detection** for CSV files
- 🧹 **Intelligent data cleaning** (handles NaN, infinity values, and inconsistencies)
- 🎨 **Professionally styled Excel** with colors and formatting
- 📊 **Automatic statistics generation** and summaries
- 📅 **Date formatting** (converts to Brazilian format DD/MM/YYYY)
- ✅ **Visual highlighting** for active projects
- 🏢 **Summaries** of laboratories and projects
- 🔄 **Robust error handling** with multiple reading attempts

## 📦 Prerequisites

- Python 3.6 or higher
- Required Python libraries:
  - pandas
  - xlsxwriter
  - chardet
  - numpy
  - openpyxl

## 🚀 Installation

1. **Install Python** (if you don't have it): [python.org](https://www.python.org/downloads/)

2. **Install dependencies** by running in terminal:

```bash
pip install pandas xlsxwriter chardet numpy openpyxl
```

## 📖 How to Use

### Step 1: Prepare your CSV file

- Name the file as `project.csv`
- Place it in the same directory as the script
- Make sure it uses **semicolon (`;`)** as separator
- The file can be in any encoding (UTF-8, Latin-1, etc.)

### Step 2: Run the script

In the terminal, navigate to the script folder and run:

```bash
python script_name.py
```

### Step 3: Check the result

The script will generate an Excel file with a name in the format:
```
relatorio_projetos_bonito_2025-11-11_14-30.xlsx
```

The file will include:
- All CSV data formatted
- Summary statistics
- Professional styling

## 📊 CSV Structure

The script expects a CSV file with the following columns:

| Column | Description | Type |
|--------|-------------|------|
| `ID_Projeto` | Unique project identifier | Number |
| `Nome_Projeto` | Project name | Text |
| `TRL_ID` | Technology Readiness Level (1-9) | Number |
| `Nome_Laboratorio` | Full laboratory name | Text |
| `Abreviação_Laboratorio` | Laboratory abbreviation | Text |
| `Descrição` | Detailed project description | Text |
| `Link_Referencia` | Project reference URL | Text |
| `Email_Projeto` | Project contact email | Text |
| `Responsavel` | Project responsible person name | Text |
| `Situacao_ID` | Project status (2 = Active) | Number |
| `Data_Cadastro` | Project registration date | Date |

### CSV Example:

```csv
ID_Projeto;Nome_Projeto;TRL_ID;Nome_Laboratorio;Abreviação_Laboratorio;Descrição;Link_Referencia;Email_Projeto;Responsavel;Situacao_ID;Data_Cadastro
1;IoT Project;5;Embedded Systems Laboratory;ESL;IoT monitoring system;http://example.com;project@email.com;John Smith;2;2024-01-15
2;AI Project;7;Artificial Intelligence Laboratory;AIL;Machine learning model;http://example2.com;ai@email.com;Mary Johnson;1;2024-02-20
```

## ⚙️ How It Works

### 1. Encoding Detection
The script uses the `chardet` library to automatically detect the CSV file encoding, ensuring correct reading of special characters and accents.

### 2. Data Reading
Tries multiple encodings in the following order:
- Automatically detected encoding
- Latin-1
- ISO-8859-1
- CP1252
- UTF-8

### 3. Data Cleaning
- **NaN (Not a Number)**: Replaced with empty strings or zero
- **Infinity values**: Converted to 999999
- **Numeric columns**: Ensures correct numeric type
- **Dates**: Formatted to Brazilian standard (DD/MM/YYYY)

### 4. Excel Generation

#### Main Header
- Report title: "🚀 RELATÓRIO DE PROJETOS - SC2C"
- Information: Generation date/time and total projects

#### Data Formatting
- **Column widths** automatically adjusted
- **Text wrapping** for long descriptions
- **Alternating colors** in rows for better readability
- **Special highlighting** for active projects (Situacao_ID = 2)

#### Statistics Section
At the end of the spreadsheet, automatic statistics are added:
- Total projects
- Active projects
- Projects with reference links
- Projects with assigned responsible person
- Number of unique laboratories

## 🎨 Styling Details

### Color Palette

- **Main Header**: 
  - Background: Dark blue (#1E3A8A)
  - Text: White, bold, size 14

- **Column Headers**:
  - Background: Blue (#3B82F6)
  - Text: White, bold, size 10

- **Even Rows**: 
  - Background: Very light blue (#EFF6FF)

- **Odd Rows**: 
  - Background: Light gray (#F8FAFC)

- **Active Projects** (Situacao_ID = 2):
  - Background: Light blue (#DBEAFE)
  - Text: Bold

### Cell Formatting

- **Numeric Columns** (ID_Projeto, TRL_ID, Situacao_ID):
  - Center alignment
  - Numeric format

- **Text Columns**:
  - Automatic word wrap
  - Left alignment

- **Dates**:
  - Brazilian format: DD/MM/YYYY

### Column Widths

| Column | Width |
|--------|-------|
| ID_Projeto | 8 |
| Nome_Projeto | 35 |
| TRL_ID | 8 |
| Nome_Laboratorio | 25 |
| Abreviação_Laboratorio | 12 |
| Descrição | 50 |
| Link_Referencia | 20 |
| Email_Projeto | 25 |
| Responsavel | 20 |
| Situacao_ID | 12 |
| Data_Cadastro | 15 |

## 📈 Generated Statistics

The report automatically includes the following statistics:

1. **📋 Total Projects**: Total count of projects in CSV
2. **✅ Active Projects**: Projects with Situacao_ID = 2
3. **🔗 Projects with Links**: Projects that have Link_Referencia filled (excluding "A preencher")
4. **👤 Projects with Responsible Person**: Projects with Responsavel field filled
5. **🏢 Laboratories with Projects**: Number of unique laboratories

## 🔧 Troubleshooting

### ❌ Error: "File 'project.csv' not found"

**Solution**: 
- Check if the file is in the same directory as the script
- Confirm the name is exactly `project.csv` (lowercase)
- The script will show available CSV files in the folder

### ❌ Encoding error or strange characters

**Solution**:
- The script automatically tries multiple encodings
- If it persists, open the CSV in a text editor and save as UTF-8

### ❌ Generated Excel is empty or has errors

**Solution**:
- Check if the CSV uses **semicolon (`;`)** as separator
- Confirm expected columns are present
- Check console for detailed error messages

### ❌ Error: "ModuleNotFoundError"

**Solution**:
```bash
pip install pandas xlsxwriter chardet numpy openpyxl
```

### ❌ Dates not formatted correctly

**Solution**:
- The script accepts various date formats
- Recommended formats: YYYY-MM-DD or DD/MM/YYYY
- Invalid dates will be kept as original text

## 💡 Usage Tips

1. **Backup**: Always keep a copy of the original CSV
2. **File names**: Generated Excel includes date/time to avoid overwriting
3. **Performance**: For very large CSVs (>10,000 rows), processing may take a few minutes
4. **Customization**: You can modify colors and styles by editing the formatting sections in the code

## 📝 Console Output Example

```
🎨 Gerando Excel bonito do project.csv...
🔍 Detectando encoding do arquivo...
📝 Encoding detectado: utf-8
📖 Tentando ler com encoding: utf-8...
✅ Sucesso com encoding: utf-8
✅ CSV lido com sucesso! 150 projetos encontrados
📋 Colunas: ['ID_Projeto', 'Nome_Projeto', 'TRL_ID', ...]
🧹 Limpando dados...
✅ Excel bonito gerado: relatorio_projetos_bonito_2025-11-11_14-30.xlsx
📊 Estatísticas: 85 ativos, 120 com links, 12 laboratórios
✨ Pronto! Verifique o arquivo .xlsx na pasta.
```

## 🤝 Contributing

Suggestions and improvements are welcome! Feel free to:
- Report bugs
- Suggest new features
- Improve documentation

## 📄 License

This script is provided "as is", without warranties. Use at your own risk.

---

**Developed for SC2C - Project Control System**

*Last updated: November 2025*
