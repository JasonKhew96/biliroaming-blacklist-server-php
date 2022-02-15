<?php

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'config.php';

if (!isset($_GET['auth']) || $_GET['auth'] != TG_SECRET) {
    echo '参数错误';
    return;
}

try {

    $telegram = new Longman\TelegramBot\Telegram(TG_BOT_TOKEN, TG_USERNAME);

    $result = $telegram->setWebhook(TG_WEBHOOK_URL, ['drop_pending_updates' => true]);
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // echo $e->getMessage();
}
