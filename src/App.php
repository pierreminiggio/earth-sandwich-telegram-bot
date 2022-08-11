<?php

namespace PierreMiniggio\EarthSandwichTelegramBot;

use DateTime;
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
        var_dump($this->bot);
    }
}
