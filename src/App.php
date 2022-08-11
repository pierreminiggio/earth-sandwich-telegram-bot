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
        $bot = $this->bot;
        
        $updatesCurl = curl_init();
        curl_setopt_array($updatesCurl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/getupdates'
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
            var_dump($fetchedUpdate);
        }
        
        return 0;
    }
}
