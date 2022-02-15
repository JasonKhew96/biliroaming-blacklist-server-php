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

class BanCommand extends UserCommand
{
    protected $name = 'ban';
    protected $description = '\[管理员] 封禁指令';
    protected $usage = '/ban';
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

        $text = $message->getText(true);
        if (strlen($text) <= 0) {
            return $this->replyToChat(
                '无效参数',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $texts = explode(' ', $text, 2);

        if (count($texts) != 2) {
            return $this->replyToChat(
                '无效参数',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $uid = $texts[0];
        $reason = count($texts) > 1 ? $texts[1] : '';

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

        if (count($info->data) <= 0) {
            return $this->replyToChat(
                '找不到此用户',
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

        $ban = $db->get_user_ban($uid);
        if (count($ban) > 0) {
            if ($db->update_user_ban($uid, $reason)) {
                Request::sendMessage([
                    'chat_id' => TG_CHANNEL_ID,
                    'text' => '更新封禁理由' . PHP_EOL .
                        'UID: <code>' . $uid . '</code>' . PHP_EOL .
                        '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                        '旧原因: ' . $ban[0]['reason'] . PHP_EOL .
                        '新原因: ' . $reason,
                    'parse_mode' => 'HTML',
                ]);
                return $this->replyToChat(
                    '已更新封禁理由 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    [
                        'parse_mode' => 'HTML',
                        'reply_to_message_id' => $msg_id,
                    ]
                );
            }
        } else {
            if ($db->insert_user_ban($uid, 'TG@' . $user_id, $reason)) {
                Request::sendMessage([
                    'chat_id' => TG_CHANNEL_ID,
                    'text' => '新封锁' . PHP_EOL .
                        'UID: <code>' . $uid . '</code>' . PHP_EOL .
                        '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                        '原因: ' . $reason,
                    'parse_mode' => 'HTML',
                ]);
                return $this->replyToChat(
                    '已封锁 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    [
                        'parse_mode' => 'HTML',
                        'reply_to_message_id' => $msg_id,
                    ]
                );
            }
        }

        return $this->replyToChat(
            '封禁失败',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
