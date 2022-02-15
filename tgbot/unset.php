<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'config.php';

if (!isset($_GET['auth']) || $_GET['auth'] != TG_SECRET) {
    echo '参数错误';
    return;
}

try {

    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram(TG_BOT_TOKEN, TG_USERNAME);

    // Unset / delete the webhook
    $result = $telegram->deleteWebhook();

    echo $result->getDescription();
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // echo $e->getMessage();
}
