<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Qimo\BiliApi;
use Qimo\DBHelper;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use PDOException;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'database.php';
require_once 'biliapi.php';

class WhiteCommand extends UserCommand
{
    protected $name = 'white';
    protected $description = '\[管理员] 白名单';
    protected $usage = '/white uid 原因';
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
        $texts = explode(' ', $text, 2);

        if (count($texts) <= 0) {
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

        $white = $db->get_user_white($uid);
        if (count($white) > 0) {
            return $this->replyToChat(
                '该用户已经是白名单 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                [
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => $msg_id
                ]
            );
        } else {
            if ($db->insert_user_white($uid, '', $user_id, $reason)) {
                return $this->replyToChat(
                    '已添加白名单 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    [
                        'parse_mode' => 'HTML',
                        'reply_to_message_id' => $msg_id
                    ]
                );
            }
        }


        return $this->replyToChat(
            '添加白名单失败',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
