from flask import Flask, request, jsonify
from flask_cors import CORS
import os
from datetime import datetime

app = Flask(__name__)
CORS(app) 

BASE_DIR = "/volume1/web/OwerWath_RecoRdeR"
SAVE_FOLDER = os.path.join(BASE_DIR, "FoxShield_Vault")

if not os.path.exists(SAVE_FOLDER):
    os.makedirs(SAVE_FOLDER)

@app.route('/upload', methods=['POST'])
def upload_file():
    if 'file' not in request.files:
        return jsonify({"error": "No file"}), 400
    
    file = request.files['file']
    case_id = request.form.get('case_id', 'UNKNOWN')
    nome = request.form.get('nome', 'SCONOSCIUTO')
    cognome = request.form.get('cognome', 'SCONOSCIUTO')
    
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    case_folder = os.path.join(SAVE_FOLDER, f"CASO_{case_id}")
    
    if not os.path.exists(case_folder):
        os.makedirs(case_folder)
        
    file_path = os.path.join(case_folder, f"{nome}_{cognome}_{timestamp}_{file.filename}")
    file.save(file_path)
    
    info_path = os.path.join(case_folder, f"Dati_Anagrafici_{timestamp}.txt")
    with open(info_path, "w") as f:
        f.write(f"ID CASO: {case_id}\n")
        f.write(f"NOME: {nome}\n")
        f.write(f"COGNOME: {cognome}\n")
        f.write(f"DATA ACQUISIZIONE: {timestamp}\n")
        f.write(f"FILE MULTIMEDIALE: {file.filename}\n")
        
    return jsonify({"message": "OK"}), 200

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5005)