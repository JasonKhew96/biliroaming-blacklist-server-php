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

class UnwhiteCommand extends UserCommand
{
    protected $name = 'unwhite';
    protected $description = '\[管理员] 解除白名单';
    protected $usage = '/unwhite uid';
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

        if (count($db->get_user_white($uid)) > 0) {
            if ($db->remove_user_white($uid, '', $user_id)) {
                return $this->replyToChat(
                    '已解除白名单 <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>',
                    [
                        'parse_mode' => 'HTML',
                        'reply_to_message_id' => $msg_id,
                    ]
                );
            }
        }

        return $this->replyToChat(
            '解除白名单失败',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
