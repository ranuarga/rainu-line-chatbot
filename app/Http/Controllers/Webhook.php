<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use LINE\LINEBot;
use LINE\LINEBot\HTTPClient\CurlHTTPClient;
use LINE\LINEBot\MessageBuilder\MultiMessageBuilder;
use LINE\LINEBot\MessageBuilder\StickerMessageBuilder;
use LINE\LINEBot\MessageBuilder\TemplateBuilder\ButtonTemplateBuilder;
use LINE\LINEBot\MessageBuilder\TemplateMessageBuilder;
use LINE\LINEBot\MessageBuilder\TextMessageBuilder;
use LINE\LINEBot\TemplateActionBuilder\MessageTemplateActionBuilder;

class Webhook extends Controller
{
    /**
     * @var LINEBot
     */
    private $bot;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    public function __construct(Request $request, Response $response) 
    {
        $this->request = $request;
        $this->response = $response;

        // create bot object
        $httpClient = new CurlHTTPClient(getenv('CHANNEL_ACCESS_TOKEN'));
        $this->bot  = new LINEBot($httpClient, ['channelSecret' => getenv('CHANNEL_SECRET')]);
    }

    public function __invoke()
    {
        // if we want to add logger we can put it here
        // but dont forget to initiate it first at constructor
        
        return $this->handleEvents();
    }

    private function handleEvents()
    {
        $data = $this->request->all();
    
        if(is_array($data['events'])) {
            foreach ($data['events'] as $event)
            {
                // skip group and room event, probably in the future we will handle it
                if(!isset($event['source']['userId'])) continue;
    
                // respond event
                if($event['type'] == 'message'){
                    if(method_exists($this, $event['message']['type'].'Message')){
                        $this->{$event['message']['type'].'Message'}($event);
                    }
                } else {
                    if(method_exists($this, $event['type'].'Callback')){
                        $this->{$event['type'].'Callback'}($event);
                    }
                }
            }
        }
    
        $this->response->setContent("No events found!");
        $this->response->setStatusCode(200);
        return $this->response;
    }

    private function followCallback($event)
    {
        $res = $this->bot->getProfile($event['source']['userId']);
        if ($res->isSucceeded()) {
            $profile = $res->getJSONDecodedBody();
    
            // create welcome message
            $message  = "Salam kenal " . $profile['displayName'] . ", ";
            $message .= "r a i n u adalah chatbot untuk submission dicoding & berfungsi ";
            $message .= "membantu menemukan judul, episode & bahkan di detik kebarapa scene dari sebuah anime ";
            $message .= "hanya dengan mengirimkan ss nya ke ruang obrolan menggunakan https://soruly.github.io/trace.moe/#/ . ";
            $message .= "Sayangnya, API tsb membatasi penggunaan hanya 10 pencarian/menit dan 150 pencarian/hari. ";
            $message .= "Jika anda menemukan bug/tertarik melihat source code chatbot ini kunjungi ";
            $message .= "https://github.com/ranuarga/rainu-line-chatbot";
            $textMessageBuilder = new TextMessageBuilder($message);
    
            // create sticker message
            $stickerMessageBuilder = new StickerMessageBuilder(1, 3);
    
            // merge all message
            $multiMessageBuilder = new MultiMessageBuilder();
            $multiMessageBuilder->add($textMessageBuilder);
            $multiMessageBuilder->add($stickerMessageBuilder);
    
            // send reply message
            $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
        }
    }

    public function notSupportedMessage($event)
    {
        return 'Silahkan kirim gambar ss Anime, bukan ' . $event['message']['type'];
    }
    
    private function textMessage($event)
    {
        $userMessage = $event['message']['text'];
        
        // for now i dont want to response text message

        // create text message
        $textMessageBuilder = new TextMessageBuilder($this->notSupportedMessage($event));

        // send message
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
    }

    private function imageMessage($event)
    {
        // create text message
        $message = 'Ok, yang kamu kirim gambar. Tapi, fiturnya belum jadi. Sabar ya';
        $textMessageBuilder = new TextMessageBuilder($message);

        // send message
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
    }

    private function videoMessage($event)
    {
        // create text message
        $textMessageBuilder = new TextMessageBuilder($this->notSupportedMessage($event));

        // send message
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
    }

    private function audioMessage($event)
    {
        // create text message
        $textMessageBuilder = new TextMessageBuilder($this->notSupportedMessage($event));

        // send message
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
    }

    private function fileMessage($event)
    {
        // create text message
        $textMessageBuilder = new TextMessageBuilder($this->notSupportedMessage($event));

        // send message
        $this->bot->replyMessage($event['replyToken'], $textMessageBuilder);
    }

    private function stickerMessage($event)
    {
        // create sticker message
        $stickerMessageBuilder = new StickerMessageBuilder(1, 106);
    
        $textMessageBuilder = new TextMessageBuilder($this->notSupportedMessage($event));
    
        // merge all message
        $multiMessageBuilder = new MultiMessageBuilder();
        $multiMessageBuilder->add($stickerMessageBuilder);
        $multiMessageBuilder->add($textMessageBuilder);
    
        // send message
        $this->bot->replyMessage($event['replyToken'], $multiMessageBuilder);
    }
}