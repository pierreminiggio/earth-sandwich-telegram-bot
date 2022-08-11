<?php

$projectFolder = __DIR__ . DIRECTORY_SEPARATOR;
  
require $projectFolder . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

use PierreMiniggio\ConfigProvider\ConfigProvider;
use PierreMiniggio\DatabaseConnection\DatabaseConnection;
use PierreMiniggio\DatabaseFetcher\DatabaseFetcher;
use PierreMiniggio\EarthSandwichTelegramBot\App;

$configProvider = new ConfigProvider($projectFolder);
$config = $configProvider->get();

if (! isset($config['bot'])) {
    throw new Exception('Missing bot config');
}

if (! isset($config['db'])) {
    throw new Exception('Missing DB config');
}

$dbConfig = $config['db'];

try {
    exit((new App(
        $config['bot'],
        new DatabaseFetcher(new DatabaseConnection(
            $dbConfig['host'],
            $dbConfig['database'],
            $dbConfig['username'],
            $dbConfig['password'],
            DatabaseConnection::UTF8_MB4
        ))
    ))->run());
} catch (Throwable $e) {
    echo get_class($e) . ' : ' . $e->getMessage() . ' ' . $e->getTraceAsString();
    exit;
}
