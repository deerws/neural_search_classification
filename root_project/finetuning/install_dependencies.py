import subprocess
import sys

def install_packages():
    packages = [
        'sentence-transformers',
        'torch',
        'pandas',
        'numpy',
        'scikit-learn',
        'tqdm'
    ]
    
    for package in packages:
        subprocess.check_call([sys.executable, "-m", "pip", "install", package])

if __name__ == "__main__":
    install_packages()
    print("✅ Dependências instaladas com sucesso!")
