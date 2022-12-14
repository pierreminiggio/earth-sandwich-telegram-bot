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
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/getupdates?limit=10' . (
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
            if (
                ! isset(
                    $fetchedUpdate['update_id'],
                    $fetchedUpdate['message']
                )
            ) {
                continue;
            }
                
            $updateId = $fetchedUpdate['update_id'];
            $messageData = $fetchedUpdate['message'];
            
            $fetchedMessages = $this->getQueryResultsMessagesDatabaseIdsByUpdateId($updateId);
            
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
        
        if (
            str_contains(strtolower($text), 'fuck')
            && (
                str_contains(strtolower($text), 'give')
                || str_contains(strtolower($text), 'gave')
            )
        ) {
            $this->sendGivenFucks($updateId, $messageData);
            
            return;
        }
        
        $explodedWords = explode(' ', strtolower($text));
        
        if (
            in_array('like', $explodedWords)
            || in_array('likes', $explodedWords)
            || in_array('enjoy', $explodedWords)
            || in_array('enjoys', $explodedWords)
            || in_array('love', $explodedWords)
            || in_array('loves', $explodedWords)
            || in_array('despise', $explodedWords)
            || in_array('despises', $explodedWords)
            || in_array('hate', $explodedWords)
            || in_array('hates', $explodedWords)
        ) {
            $this->sendAppreciation($updateId, $messageData);
            
            return;
        }
        
        if (str_contains(strtolower($text), 'duck')) {
            $this->sendDuck($updateId, $messageData);
            
            return;
        }
        
        if (
            in_array('rip', $explodedWords)
        ) {
            $this->sendRip($updateId, $messageData);
            
            return;
        }

        if (
            in_array('wtf', $explodedWords)
            || in_array('wtf?', $explodedWords)
            || in_array('wtf?!', $explodedWords)
            || in_array('wtf??', $explodedWords)
        ) {
            $this->sendWTF($updateId, $messageData);
            
            return;
        }

        if (
            in_array('laik', $explodedWords)
            || in_array('laiks', $explodedWords)
        ) {
            $this->sendLaik($updateId, $messageData);
            
            return;
        }
        
        if (
            in_array('uno', $explodedWords)
        ) {
            $this->sendUno($updateId, $messageData);
            
            return;
        }
        
        if (
            (
                in_array('git', $explodedWords)
                || in_array('get', $explodedWords)
            )
            && (
                in_array('gud', $explodedWords)
                || in_array('good', $explodedWords)
            )
        ) {
            $this->sendGitgud($updateId, $messageData);
            
            return;
        }
        
        
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
        if (strtolower($firstwordAndRemainingMessage[0]) === $fuckTrigger) {
            if (count($firstwordAndRemainingMessage) === 2) {
                $remainingMessage = $firstwordAndRemainingMessage[1];
                $message = 'fuck u ' . $remainingMessage;
                
                $this->clapBack($updateId, $messageData, 'fuck', $message);
            }
        }
    }
    
    private function sendGivenFucks(string $updateId, array $messageData): void
    {
        $fetcher = $this->fetcher;
        $fetchedCounts = $fetcher->rawQuery(
            'SELECT count(id) as given_fucks FROM fuck_message'
        );
        
        if (! $fetchedCounts) {
            return;
        }
        
        $fucksGivenCount = $fetchedCounts[0]['given_fucks'];

        $messages = [
            'I gave ' . $fucksGivenCount . ' fucks.',
            'I gave ' . $fucksGivenCount . ' fucks !',
            'But I did give ' . $fucksGivenCount . ' fucks, so what ?',
            'What do you mean ? I already have given a whole ' . $fucksGivenCount . ' of fucks.',
            'I don\'t know how many fucks your mom gave, but I gave ' . $fucksGivenCount . ' of my own.',
            'Same, I don\'t care !',
            'Yeah, right ? Who gives a damn fuck anyway ?',
            'Even a bot would never give a fuck about that. And that\'s a bot who gave ' . $fucksGivenCount . ' fucks that tells you that !'
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'given_fucks', $message);
    }
    
    private function sendAppreciation(string $updateId, array $messageData): void
    {
        $messages = [
            'I like it',
            'I enjoy it',
            'I don\'t like it',
            'Your mom enjoyed it as well',
            'I appreciate it as well !',
            'That\'s cool !!!',
            'I don\'t care about what you like or dislike',
            '????',
            '????',
            '??????',
            '??????',
            '??????',
            '??????',
            '??????',
            '????????????',
            '??????????????????'
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'appreciation', $message);
    }
    
    private function sendDuck(string $updateId, array $messageData): void
    {
        $messages = [
            '????',
            '????',
            '????',
            '????',
            '????',
            '????',
            '????',
            '????',
            '????',
            '????',
            '????????????',
            'Are you ducking kidding me ???',
            'Can you stop being a duck for like 2 seconds, or is too much to ask for ?'
        ];
        
        $message = $messages[array_rand($messages)];
        $this->clapBack($updateId, $messageData, 'duck', $message);
    }
    
    private function sendRip(string $updateId, array $messageData): void
    {
        $messages = [
            'RIP.',
            'RIP in RIP',
            'Rest In Pieces ????',
            'Press F in the chat',
            '????'
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'rip', $message);
    }

    private function sendWTF(string $updateId, array $messageData): void
    {
        $messages = [
            'Yeah, what the fuck dude ?',
            'Are you ok ?',
            'Right ! What the fuck indeed !',
            'Yo, what happened ?',
            'What the fuck ?',
            'What the actual fuck ?',
            'I don\'t know what fuck you are looking for, but please know that I\'m here to give fucks ???'
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'wtf', $message);
    }

    private function sendLaik(string $updateId, array $messageData): void
    {
        $messages = [
            'It\'s "like"',
            '*like',
            'Yo @papuna85, you can do better than using "like" in every single sentence.'
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'laik', $message);
    }
    
    private function sendUno(string $updateId, array $messageData): void
    {
        $messages = [
            'Uno ! https://pierre.uno',
            'Contre Uno ! https://pierre.uno/contre',
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'uno', $message);
    }
    
    private function sendGitgud(string $updateId, array $messageData): void
    {
        $messages = [
            'https://gitgud.fr',
        ];
        
        $message = $messages[array_rand($messages)];
        
        $this->clapBack($updateId, $messageData, 'gitgud', $message);
    }
    
    private function clapBack(string $updateId, array $messageData, string $messageType, string $messageContent): void
    {
        $chatId = $this->getChatIdFromMessageData($messageData);
        
        if (! $chatId) {
            return;
        }
        
        $clapBackMessageId = $this->sendMessageToChat($chatId, $messageContent);
        $messageId = $this->findMessageDataBaseIdByUpdateId($updateId);

        $this->insertPostedMessage($messageType, $messageId, $clapBackMessageId, $messageContent);
    }
    
    private function insertPostedMessage(string $messageType, int $messageId, string $telegramMessageId, string $content): void
    {
        $telegramIdColumnName = $messageType . '_message_id';
        
        $params = [
            'message_id' => $messageId,
            'content' => $content
        ];
        
        $params[$telegramIdColumnName] = $telegramMessageId;
        
        $fetcher = $this->fetcher;
        $fetcher->exec(
            $fetcher->createQuery(
                $messageType . '_message'
            )->insertInto(
                'message_id, ' . $telegramIdColumnName . ', content',
                ':message_id, :' . $telegramIdColumnName . ', :content'
            ),
            $params
        );
    }
    
    private function getChatIdFromMessageData(array $messageData): ?string
    {
        if (! isset($messageData['chat'])) {
            return null;
        }

        $chat = $messageData['chat'];

        if (! isset($chat['id'])) {
            return null;
        }

        return $chat['id'];
    }
    
    private function findMessageDataBaseIdByUpdateId(string $updateId): int
    {
        
        $fetchedMessages = $this->getQueryResultsMessagesDatabaseIdsByUpdateId($updateId);

        if (! count($fetchedMessages)) {
            throw new Exception('Update ' . $updateId . ' was not saved !');
        }

        $messageId = (int) $fetchedMessages[0]['id'];
        
        return $messageId;
    }
    
    private function getQueryResultsMessagesDatabaseIdsByUpdateId(string $updateId): array
    {
        $fetcher = $this->fetcher;
        
        return $fetcher->query(
            $fetcher->createQuery(
                'message'
            )->select(
                'id',
            )->where(
                'update_id = :update_id'
            ),
            ['update_id' => $updateId]
        );
    }
    
    private function sendMessageToChat(string $chatId, string $message): string
    {
        $bot = $this->bot;
        
        $sendMessageCurl = curl_init();
        curl_setopt_array($sendMessageCurl, [
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => 'https://api.telegram.org/bot' . $bot . '/sendMessage?chat_id=' . $chatId . '&text=' . $message
        ]);
        $sendMessageCurlResponse = curl_exec($sendMessageCurl);
        $httpCode = curl_getinfo($sendMessageCurl)['http_code'];
        curl_close($sendMessageCurl);

        if ($httpCode !== 200) {
            throw new Exception('fuck request failed with code ' . $httpCode . ' : ' . $sendMessageCurlResponse);
        }

        if ($sendMessageCurlResponse === false) {
            throw new Exception('No body for fuck request');
        }

        $sendMessageCurlJsonResponse = json_decode($sendMessageCurlResponse, true);

        if (! $sendMessageCurlJsonResponse) {
            throw new Exception('Bad JSON for fuck request : ' . $sendMessageCurlResponse);
        }

        if (empty($sendMessageCurlJsonResponse['ok'])) {
            throw new Exception('fuck request not ok : ' . $sendMessageCurlResponse);
        }

        if (! isset($sendMessageCurlJsonResponse['result'])) {
            throw new Exception('fuck request missing result key : ' . $sendMessageCurlResponse);
        }

        $fetchedMessage = $sendMessageCurlJsonResponse['result'];

        if (! isset($fetchedMessage['message_id'])) {
            throw new Exception('fuck request missing result->message_id key : ' . $sendMessageCurlResponse);
        }

        return $fetchedMessage['message_id'];
    }
}
