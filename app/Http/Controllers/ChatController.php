<?php


namespace App\Http\Controllers;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OpenAI\Laravel\Facades\OpenAI;


class ChatController extends Controller
{
    public function chat(Request $request)
    {
        if ($request->input == null) {
            return "Vazio.";
        }

        $message = $request->input;        

        $result = OpenAI::chat()->create([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);

        echo $result->choices[0]->message->content; // Hello! How can I assist you today?
        return "FOI";
        
       $result = $client->completions()->create([
            "model" => "gpt-3.5-turbo",
            "temperature" => 0.5,
            'max_tokens' => 4096,
            'prompt' => sprintf('Write article about: %s', $message),
        ]);
    /*
        $content = trim($result['choices'][0]['text']);
    
        */
        return "OK";
    }
}
