# Gerador de Relatório Excel Estilizado a partir de CSV

Um script Python que converte arquivos CSV de projetos em relatórios Excel lindamente formatados com estilização automática, estatísticas e limpeza de dados.

## 📋 Índice

- [Funcionalidades](#funcionalidades)
- [Pré-requisitos](#pré-requisitos)
- [Instalação](#instalação)
- [Como Usar](#como-usar)
- [Estrutura do CSV](#estrutura-do-csv)
- [Como Funciona](#como-funciona)
- [Detalhes da Estilização](#detalhes-da-estilização)
- [Estatísticas Geradas](#estatísticas-geradas)
- [Solução de Problemas](#solução-de-problemas)

## ✨ Funcionalidades

- 🔍 **Detecção automática de encoding** para arquivos CSV
- 🧹 **Limpeza inteligente de dados** (trata valores NaN, infinito e inconsistências)
- 🎨 **Excel estilizado profissionalmente** com cores e formatação
- 📊 **Geração automática de estatísticas** e resumos
- 📅 **Formatação de datas** (converte para formato brasileiro DD/MM/AAAA)
- ✅ **Destaque visual** para projetos ativos
- 🏢 **Resumos** de laboratórios e projetos
- 🔄 **Tratamento robusto de erros** com múltiplas tentativas de leitura

## 📦 Pré-requisitos

- Python 3.6 ou superior
- Bibliotecas Python necessárias:
  - pandas
  - xlsxwriter
  - chardet
  - numpy
  - openpyxl

## 🚀 Instalação

1. **Instale o Python** (se ainda não tiver): [python.org](https://www.python.org/downloads/)

2. **Instale as dependências** executando no terminal:

```bash
pip install pandas xlsxwriter chardet numpy openpyxl
```

## 📖 Como Usar

### Passo 1: Prepare seu arquivo CSV

- Nomeie o arquivo como `project.csv`
- Coloque-o no mesmo diretório do script
- Certifique-se de que usa **ponto e vírgula (`;`)** como separador
- O arquivo pode estar em qualquer encoding (UTF-8, Latin-1, etc.)

### Passo 2: Execute o script

No terminal, navegue até a pasta do script e execute:

```bash
python nome_do_script.py
```

### Passo 3: Verifique o resultado

O script irá gerar um arquivo Excel com nome no formato:
```
relatorio_projetos_bonito_2025-11-11_14-30.xlsx
```

O arquivo incluirá:
- Todos os dados do CSV formatados
- Estatísticas resumidas
- Estilização profissional

## 📊 Estrutura do CSV

O script espera um arquivo CSV com as seguintes colunas:

| Coluna | Descrição | Tipo |
|--------|-----------|------|
| `ID_Projeto` | Identificador único do projeto | Número |
| `Nome_Projeto` | Nome do projeto | Texto |
| `TRL_ID` | Nível de Prontidão Tecnológica (1-9) | Número |
| `Nome_Laboratorio` | Nome completo do laboratório | Texto |
| `Abreviação_Laboratorio` | Sigla do laboratório | Texto |
| `Descrição` | Descrição detalhada do projeto | Texto |
| `Link_Referencia` | URL de referência do projeto | Texto |
| `Email_Projeto` | Email de contato do projeto | Texto |
| `Responsavel` | Nome do responsável pelo projeto | Texto |
| `Situacao_ID` | Status do projeto (2 = Ativo) | Número |
| `Data_Cadastro` | Data de cadastro do projeto | Data |

### Exemplo de CSV:

```csv
ID_Projeto;Nome_Projeto;TRL_ID;Nome_Laboratorio;Abreviação_Laboratorio;Descrição;Link_Referencia;Email_Projeto;Responsavel;Situacao_ID;Data_Cadastro
1;Projeto IoT;5;Laboratório de Sistemas Embarcados;LSE;Sistema IoT para monitoramento;http://exemplo.com;projeto@email.com;João Silva;2;2024-01-15
2;Projeto AI;7;Laboratório de Inteligência Artificial;LIA;Modelo de machine learning;http://exemplo2.com;ai@email.com;Maria Santos;1;2024-02-20
```

## ⚙️ Como Funciona

### 1. Detecção de Encoding
O script usa a biblioteca `chardet` para detectar automaticamente o encoding do arquivo CSV, garantindo leitura correta de caracteres especiais e acentos.

### 2. Leitura de Dados
Tenta múltiplos encodings na seguinte ordem:
- Encoding detectado automaticamente
- Latin-1
- ISO-8859-1
- CP1252
- UTF-8

### 3. Limpeza de Dados
- **NaN (Not a Number)**: Substituídos por strings vazias ou zero
- **Valores infinitos**: Convertidos para 999999
- **Colunas numéricas**: Garantia de tipo numérico correto
- **Datas**: Formatadas para o padrão brasileiro (DD/MM/AAAA)

### 4. Geração do Excel

#### Cabeçalho Principal
- Título do relatório: "🚀 RELATÓRIO DE PROJETOS - SC2C"
- Informações: Data/hora de geração e total de projetos

#### Formatação de Dados
- **Larguras de coluna** ajustadas automaticamente
- **Quebra de texto** para descrições longas
- **Cores alternadas** nas linhas para melhor legibilidade
- **Destaque especial** para projetos ativos (Situacao_ID = 2)

#### Seção de Estatísticas
No final da planilha, são adicionadas estatísticas automáticas:
- Total de projetos
- Projetos ativos
- Projetos com links de referência
- Projetos com responsável definido
- Número de laboratórios únicos

## 🎨 Detalhes da Estilização

### Paleta de Cores

- **Cabeçalho Principal**: 
  - Fundo: Azul escuro (#1E3A8A)
  - Texto: Branco, negrito, tamanho 14

- **Cabeçalhos das Colunas**:
  - Fundo: Azul (#3B82F6)
  - Texto: Branco, negrito, tamanho 10

- **Linhas Pares**: 
  - Fundo: Azul muito claro (#EFF6FF)

- **Linhas Ímpares**: 
  - Fundo: Cinza claro (#F8FAFC)

- **Projetos Ativos** (Situacao_ID = 2):
  - Fundo: Azul claro (#DBEAFE)
  - Texto: Negrito

### Formatação de Células

- **Colunas Numéricas** (ID_Projeto, TRL_ID, Situacao_ID):
  - Alinhamento centralizado
  - Formato numérico

- **Colunas de Texto**:
  - Quebra de linha automática
  - Alinhamento à esquerda

- **Datas**:
  - Formato brasileiro: DD/MM/AAAA

### Larguras de Coluna

| Coluna | Largura |
|--------|---------|
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

## 📈 Estatísticas Geradas

O relatório inclui automaticamente as seguintes estatísticas:

1. **📋 Total de Projetos**: Contagem total de projetos no CSV
2. **✅ Projetos Ativos**: Projetos com Situacao_ID = 2
3. **🔗 Projetos com Links**: Projetos que possuem Link_Referencia preenchido (excluindo "A preencher")
4. **👤 Projetos com Responsável**: Projetos com campo Responsavel preenchido
5. **🏢 Laboratórios com Projetos**: Número de laboratórios únicos

## 🔧 Solução de Problemas

### ❌ Erro: "Arquivo 'project.csv' não encontrado"

**Solução**: 
- Verifique se o arquivo está no mesmo diretório do script
- Confirme se o nome é exatamente `project.csv` (minúsculas)
- O script mostrará os arquivos CSV disponíveis na pasta

### ❌ Erro de encoding ou caracteres estranhos

**Solução**:
- O script tenta múltiplos encodings automaticamente
- Se persistir, abra o CSV em um editor de texto e salve como UTF-8

### ❌ Excel gerado está vazio ou com erros

**Solução**:
- Verifique se o CSV usa **ponto e vírgula (`;`)** como separador
- Confirme se as colunas esperadas estão presentes
- Verifique o console para mensagens de erro detalhadas

### ❌ Erro: "ModuleNotFoundError"

**Solução**:
```bash
pip install pandas xlsxwriter chardet numpy openpyxl
```

### ❌ Datas não formatadas corretamente

**Solução**:
- O script aceita diversos formatos de data
- Formatos recomendados: YYYY-MM-DD ou DD/MM/YYYY
- Datas inválidas serão mantidas como texto original

## 💡 Dicas de Uso

1. **Backup**: Sempre mantenha uma cópia do CSV original
2. **Nomes de arquivo**: O Excel gerado inclui data/hora para evitar sobrescrita
3. **Performance**: Para CSVs muito grandes (>10.000 linhas), o processamento pode levar alguns minutos
4. **Personalização**: Você pode modificar as cores e estilos editando as seções de formatação no código

## 📝 Exemplo de Saída no Console

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

## 🤝 Contribuindo

Sugestões e melhorias são bem-vindas! Sinta-se livre para:
- Reportar bugs
- Sugerir novas funcionalidades
- Melhorar a documentação

## 📄 Licença

Este script é fornecido "como está", sem garantias. Use por sua conta e risco.

---

**Desenvolvido para SC2C - Sistema de Controle de Projetos**

*Última atualização: Novembro 2025*
