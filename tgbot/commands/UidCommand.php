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

class UidCommand extends UserCommand
{
    protected $name = 'uid';
    protected $description = '通过 uid 查询用户状态';
    protected $usage = '/uid uid';
    protected $version = '1.0.0';
    protected $private_only = true;

    protected $show_in_help = true;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $msg_id = $message->getMessageId();

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
        $data_ban = $db->get_user_ban($uid);

        if (count($data_ban) > 0) {
            $data = $data_ban[0];
            $reason = $data['reason'] == '' ? '无' : $data['reason'];
            return $this->replyToChat(
                'UID: <code>' . $uid . '</code>' . PHP_EOL .
                    '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                    '该用户的封禁原因是: ' . $reason,
                [
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $data_white = $db->get_user_white($uid);
        if (count($data_white) > 0) {
            $data = $data_white[0];
            return $this->replyToChat(
                '白名单用户',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        return $this->replyToChat(
            '该用户不是黑名单也不是白名单',
            [
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
