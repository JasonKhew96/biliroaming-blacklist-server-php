<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'config.php';

try {
    $telegram = new Longman\TelegramBot\Telegram(TG_BOT_TOKEN, TG_USERNAME);

    $telegram->enableAdmins(TG_ADMIN);
    $telegram->addCommandsPath(__DIR__ . '/commands/');

    $telegram->handle();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // echo $e->getMessage();
    file_put_contents('logs.txt', $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
} catch (\Throwable $e) {
    file_put_contents('logs.txt', $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
}
