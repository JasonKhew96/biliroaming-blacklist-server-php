<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Qimo\DBHelper;
use Qimo\BiliApi;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PDOException;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'database.php';
require_once 'biliapi.php';

class UnbanCommand extends UserCommand
{
    protected $name = 'unban';
    protected $description = '\[管理员] 解除封禁';
    protected $usage = '/unban uid';
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

        $uid = $message->getText(true);

        if (strlen($uid) <= 0) {
            return $this->replyToChat(
                '无效参数',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        if (!is_numeric($uid)) {
            return $this->replyToChat(
                '无效 ID',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }
        $uid = intval($uid);

        $info = BiliApi::get_uid_info($uid);
        if ($info->code != 0) {
            return $this->replyToChat(
                '未知错误: ' . $info->message,
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $uname = $info->data[0]->uname;

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

        if (count($db->get_user_ban($uid)) > 0) {
            if ($db->remove_user_ban($uid)) {
                Request::sendMessage([
                    'chat_id' => TG_CHANNEL_ID,
                    'text' => '已解除封禁' . PHP_EOL .
                        'UID: <code>' . $uid . '</code>' . PHP_EOL .
                        '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    'parse_mode' => 'HTML',
                ]);
                return $this->replyToChat(
                    '已解除封禁 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    [
                        'parse_mode' => 'HTML',
                        'reply_to_message_id' => $msg_id,
                    ]
                );
            }
        }

        return $this->replyToChat(
            '解除封禁失败',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
