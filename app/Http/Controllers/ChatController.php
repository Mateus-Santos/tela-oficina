<?php


namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;


class ChatController extends Controller
{
    public function chat(Request $request)
    {
        if ($request->input == null) {
            return "Desculpe mas a sua resposta está vazia.";
        }

        // Obtenha a chave da API do ambiente
        $apiKey = env('OPENAI_API_KEY');


        $apiEndpoint = 'https://api.openai.com/v1/chat/completions';
        $model = 'gpt-3.5-turbo';

        // Parâmetros da API
        $apiParams = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente útil.'],
                ['role' => 'user', 'content' => $request->input],
            ],
        ];
    
        $client = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
        ]);

        $response = $client->post($apiEndpoint, [
            'json' => $apiParams,
        ]);

        // Obtenha o conteúdo da resposta
        $apiResponse = $response->getBody()->getContents();
        // Decodifica o JSON para um objeto PHP
        $objetoPHP = json_decode($apiResponse);

        // Acessa a palavra "queijo"
        $resposta = $objetoPHP->choices[0]->message->content;
        return $resposta;
    }
}