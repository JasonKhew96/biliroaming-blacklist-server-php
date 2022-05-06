<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Qimo\BiliApi;
use Qimo\DBHelper;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PDOException;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'biliapi.php';
require_once 'database.php';

class LastCommand extends UserCommand
{
    protected $name = 'last';
    protected $description = '\[管理员] 获取最近 被拉黑用户 尝试请求 名单';
    protected $usage = '/last';
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

        $users = $db->get_last_requested_blacklist_users();
        $text = '';
        for ($i = 0; $i < count($users); $i++) {
            $text .= $users[$i]['updated_at'] . '|' . str_pad($users[$i]['uid'], 10, " ", STR_PAD_LEFT) . '|' . $users[$i]['reason'] . PHP_EOL;
        }

        return $this->replyToChat(
            '<code>' . $text . '</code>',
            [
                'parse_mode' => 'HTML',
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
