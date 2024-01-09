from senha import OPENAI_API_KEY
import requests
import json

from openai import OpenAI

link = "https://api.openai.com/v1/chat/completions"
id_modelo = "gpt-3.5-turbo"
headers = {"Authorization": f"Bearer {OPENAI_API_KEY}", "Content-Type": "application/json"}

body_message = {
    "model": id_modelo,
    "messages": [{"role": "user", "content": "Carro não liga."}]
}

body_message = json.dumps(body_message)

requisicao = requests.post(link, headers=headers, data=body_message)

print(requisicao)
print(requisicao.text)