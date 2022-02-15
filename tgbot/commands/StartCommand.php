<?php

use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;

class StartCommand extends UserCommand
{
    protected $name = 'start';
    protected $description = 'Start 指令';
    protected $usage = '/start';
    protected $version = '1.0.0';
    protected $private_only = true;

    public function execute(): ServerResponse
    {
        $msg_id = $this->getMessage()->getMessageId();

        return $this->replyToChat(
            '您好！' . PHP_EOL .
                '请发送 /help 获取更多指令！',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
