import pandas as pd
import os
import glob

# Directory containing the CSV files (Desktop)
csv_directory = r'C:\Users\Admin\Desktop'
output_file = 'merged_approvals.csv'

# Get all CSV files in the Desktop directory
csv_files = glob.glob(os.path.join(csv_directory, '[Pp]edro_classification_approval.csv')) + \
           glob.glob(os.path.join(csv_directory, '[Aa]ndre_classification_approval.csv'))
if not csv_files:
    print(f"No CSV files found in {csv_directory}. Please check the directory and filenames.")
    print("Available files in directory:")
    for file in os.listdir(csv_directory):
        print(file)
    exit()

# Read and concatenate all CSVs
dfs = []
for file in csv_files:
    try:
        print(f"Reading {file}...")
        df = pd.read_csv(file, encoding='utf-8')
        dfs.append(df)
    except Exception as e:
        print(f"Error reading {file}: {e}")

if not dfs:
    print("No valid CSV files to process.")
    exit()

# Combine all dataframes
merged_df = pd.concat(dfs, ignore_index=True)

# Define aggregation logic
aggregation = {
    'User': lambda x: '; '.join(x.dropna().astype(str)),
    'Approved Classification': lambda x: '; '.join(x.dropna().astype(str)),
    'Approved By': lambda x: '; '.join(x.dropna().astype(str)),
    'Approval Timestamp': lambda x: '; '.join(x.dropna().astype(str)),
    'Feedback': lambda x: '; '.join(x.dropna().astype(str)),
    'ACARE 1': lambda x: x.dropna().iloc[0] if not x.dropna().empty else '',
    'ACARE 2': lambda x: x.dropna().iloc[0] if not x.dropna().empty else '',
    'ACARE 3': lambda x: x.dropna().iloc[0] if not x.dropna().empty else '',
    'NASA 1': lambda x: x.dropna().iloc[0] if not x.dropna().empty else '',
    'NASA 2': lambda x: x.dropna().iloc[0] if not x.dropna().empty else '',
    'NASA 3': lambda x: x.dropna().iloc[0] if not x.dropna().empty else ''
}

# Group by Programa, Área, Linha de Pesquisa and aggregate
grouped_df = merged_df.groupby(['Programa', 'Área', 'Linha de Pesquisa']).agg(aggregation).reset_index()

# Save the unified table to a CSV
grouped_df.to_csv(os.path.join(csv_directory, output_file), index=False, encoding='utf-8', quoting=1)
print(f"Unified table saved to {os.path.join(csv_directory, output_file)}")

# Display a summary
print("\nSummary of Approvals per Programa:")
summary = grouped_df.groupby('Programa').size().reset_index(name='Approval Count')
print(summary)