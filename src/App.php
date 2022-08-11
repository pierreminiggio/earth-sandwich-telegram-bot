<?php

namespace PierreMiniggio\EarthSandwichTelegramBot;

use Exception;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;

class App
{
    public function __construct(
        private string $bot,
        private DatabaseFetcher $fetcher
    )
    {
    }
  
    public function run(): int
    {
        $fetcher = $this->fetcher;
        $lastMessages = $fetcher->query(
            $fetcher->createQuery(
                'message'
            )->select(
                'update_id',
            )->orderBy(
                'id',
                'DESC'
            )->limit(
                1
            )
        );
        
        $hasALastMessage = count($lastMessages) > 0;
        $lastUpdateId = $hasALastMessage ? $lastMessages[0]['update_id'] : null;
        
        $bot = $this->bot;
        
        $updatesCurl = curl_init();
        curl_setopt_array($updatesCurl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/getupdates?limit=20' . (
                $lastUpdateId ? ('&offset=' . $lastUpdateId) : ''
            )
        ]);

        $updatesCurlResponse = curl_exec($updatesCurl);
        $httpCode = curl_getinfo($updatesCurl)['http_code'];
        curl_close($updatesCurl);
        
        if ($httpCode !== 200) {
            throw new Exception('getUpdates request failed with code ' . $httpCode . ' : ' . $updatesCurlResponse);
        }

        if ($updatesCurlResponse === false) {
            throw new Exception('No body for getUpdates request');
        }
        
        $updatesCurlJsonResponse = json_decode($updatesCurlResponse, true);
        
        if (! $updatesCurlJsonResponse) {
            throw new Exception('Bad JSON for getUpdates request : ' . $updatesCurlResponse);
        }
        
        if (empty($updatesCurlJsonResponse['ok'])) {
            throw new Exception('getUpdates request not ok : ' . $updatesCurlResponse);
        }
            
        if (! isset($updatesCurlJsonResponse['result'])) {
            throw new Exception('getUpdates request missing result key : ' . $updatesCurlResponse);
        }
        
        $fetchedUpdates = $updatesCurlJsonResponse['result'];
        
        foreach ($fetchedUpdates as $fetchedUpdate) {
            if (! isset(
                $fetchedUpdate['update_id'],
                $fetchedUpdate['message']
            )) {
                continue;
            }
                
            $updateId = $fetchedUpdate['update_id'];
            $messageData = $fetchedUpdate['message'];
            
            $fetchedMessages = $fetcher->query(
                $fetcher->createQuery(
                    'message'
                )->select(
                    'id',
                )->where(
                    'update_id = :update_id'
                ),
                ['update_id' => $updateId]
            );
            
            if (count($fetchedMessages)) {
                continue;
            }
            
            $fetcher->exec(
                $fetcher->createQuery(
                    'message'
                )->insertInto(
                    'update_id, message',
                    ':update_id, :message'
                ),
                [
                    'update_id' => $updateId,
                    'message' => json_encode($messageData)
                ]
            );
            
            $this->newUpdateHandler($updateId, $messageData);
        }
        
        return 0;
    }
    
    private function newUpdateHandler(string $updateId, array $messageData): void
    {
        if (! isset($messageData['text'])) {
            return;
        }
        
        $text = $messageData['text'];
        
        $botname = '@EarthSandwichBot';
        
        if (str_starts_with($text, $botname)) {
            $this->botTaggedHandler($botname, $updateId, $messageData, $text);
            
            return;
        }
        
        $this->sendFucksIfNeeded($updateId, $messageData, $text);
    }
            
    private function botTaggedHandler(string $botname, string $updateId, array $messageData, string $messageText): void
    {
        $messageAfterTag = trim(substr($messageText, strlen($botname)));
        $this->sendFucksIfNeeded($updateId, $messageData, $messageAfterTag);
    }
    
    private function sendFucksIfNeeded(string $updateId, array $messageData, string $messageText): void
    {
        $firstwordAndRemainingMessage = explode(' ', $messageText, 2);
        $fuckTrigger = 'fuck';
        if ($firstwordAndRemainingMessage[0] === $fuckTrigger) {
            if (count($firstwordAndRemainingMessage) === 2) {
                $remainingMessage = $firstwordAndRemainingMessage[1];
                $fuckMessage = 'fuck u ' . $remainingMessage;
                
                if (! isset($messageData['chat'])) {
                    return;
                }
                
                $chat = $messageData['chat'];
                
                if (! isset($chat['id'])) {
                    return;
                }
                
                $chatId = $chat['id'];
                
                $bot = $this->bot;
        
                $fuckCurl = curl_init();
                curl_setopt_array($fuckCurl, [
                    CURLOPT_RETURNTRANSFER => 1,
                    CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/sendMessage?chat_id=' . $chatId . '&text=' . $fuckMessage
                ]);
                $fuckCurlResponse = curl_exec($fuckCurl);
                $httpCode = curl_getinfo($fuckCurl)['http_code'];
                curl_close($fuckCurl);

                if ($httpCode !== 200) {
                    throw new Exception('fuck request failed with code ' . $httpCode . ' : ' . $fuckCurlResponse);
                }

                if ($fuckCurlResponse === false) {
                    throw new Exception('No body for fuck request');
                }

                $fuckCurlJsonResponse = json_decode($fuckCurlResponse, true);

                if (! $fuckCurlJsonResponse) {
                    throw new Exception('Bad JSON for fuck request : ' . $fuckCurlJsonResponse);
                }

                if (empty($fuckCurlJsonResponse['ok'])) {
                    throw new Exception('fuck request not ok : ' . $fuckCurlJsonResponse);
                }

                if (! isset($fuckCurlJsonResponse['result'])) {
                    throw new Exception('fuck request missing result key : ' . $fuckCurlJsonResponse);
                }

                $fetchedFuck = $fuckCurlJsonResponse['result'];
                
                if (! isset($fetchedFuck['message_id'])) {
                    throw new Exception('fuck request missing result->message_id key : ' . $fuckCurlJsonResponse);
                }
                
                $fuckMessageId = $fetchedFuck['message_id'];
                
                $fetcher = $this->fetcher;
                
                $fetchedMessages = $fetcher->query(
                    $fetcher->createQuery(
                        'message'
                    )->select(
                        'id',
                    )->where(
                        'update_id = :update_id'
                    ),
                    ['update_id' => $updateId]
                );

                if (! count($fetchedMessages)) {
                    throw new Eception('Update ' . $updateId . ' was not saved !');
                }
                
                $messageId = (int) $fetchedMessages[0]['id'];
                
                $fetcher->exec(
                    $fetcher->createQuery(
                        'fuck'
                    )->insertInto(
                        'message_id, fuck_message_id, content',
                        ':message_id, :fuck_message_id, :content'
                    ),
                    [
                        'message_id' => $messageId,
                        'fuck_message_id' => $fuckMessageId,
                        'content' => $fuckMessage
                    ]
                );
            }
        }
    }
}
