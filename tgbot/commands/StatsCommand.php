<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Qimo\DBHelper;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use PDOException;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'database.php';

class StatsCommand extends UserCommand
{
    protected $name = 'stats';
    protected $description = '\[管理员] 封禁指令';
    protected $usage = '/stats';
    protected $version = '1.0.0';
    protected $private_only = true;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $msg_id = $message->getMessageId();


        // 检查是否是管理员
        $user_id = $message->getFrom()->getId();
        if (!$this->telegram->isAdmin($user_id)) {
            return $this->replyToChat(
                '不好意思，您并不是管理员，请不要乱尝试，谢谢',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        try {
            $db = new DBHelper();
        } catch (PDOException $e) {
            return $this->replyToChat(
                '未知错误',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $count = $db->get_total_user_ban();
        $text = '现在共有 ' . $count . ' 个用户被封禁';
        return $this->replyToChat($text, [
            'reply_to_message_id' => $msg_id
        ]);
    }
}
