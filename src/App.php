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
            throw new Exception('Updates request failed with code ' . $httpCode . ' : ' . $updatesCurlResponse);
        }

        if ($updatesCurlResponse === false) {
            throw new Exception('No body for updates request');
        }
        
        $updatesCurlJsonResponse = json_decode($updatesCurlResponse, true);
        
        if (! $updatesCurlJsonResponse) {
            throw new Exception('Bad JSON for updates request : ' . $updatesCurlResponse);
        }
        
        foreach ($updatesCurlJsonResponse as $fetchedUpdate) {
            var_dump($fetchedUpdate);
        }
        
        return 0;
    }
}
