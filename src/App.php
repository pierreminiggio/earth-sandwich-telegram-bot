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
            CURLOPT_URL => $channelApiUrl . '/' . $channelId
        ]);

        $updatesCurlResult = curl_exec($updatesCurl);
        $httpCode = curl_getinfo($updatesCurl)['http_code'];
        curl_close($updatesCurl);
        
        if ($httpCode !== 200) {
            throw new Exception('Updates request failed with code ' . $httpCode . ' : ' . $updatesCurlResult);
        }

        if ($updatesCurlResult === false) {
            throw new Exception('No body for updates request');
        }
        
        var_dump($updatesCurlResult);
        
        return 0;
    }
}
