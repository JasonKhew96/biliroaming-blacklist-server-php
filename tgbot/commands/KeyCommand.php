<?php

namespace Longman\TelegramBot\Commands\UserCommands;

use Qimo\BiliApi;
use Qimo\DBHelper;
use Qimo\Utils;
use Longman\TelegramBot\Commands\UserCommand;
use Longman\TelegramBot\Entities\ServerResponse;
use PDOException;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'biliapi.php';
require_once 'database.php';
require_once 'utils.php';

class KeyCommand extends UserCommand
{
    protected $name = 'key';
    protected $description = '通过 key 查询用户状态';
    protected $usage = '/key accesskey';
    protected $version = '1.0.0';
    protected $private_only = true;

    protected $show_in_help = true;

    public function execute(): ServerResponse
    {
        $message = $this->getMessage();
        $msg_id = $message->getMessageId();

        $access_key = $message->getText(true);

        if (strlen($access_key) <= 0) {
            return $this->replyToChat(
                '无效参数',
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

        if (!Utils::is_valid_key($access_key)) {
            return $this->replyToChat(
                '错误 key',
                [
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        $data = $db->get_user_key($access_key);
        $uid = 0;
        if (count($data) > 0) {
            $uid = $data[0]['uid'];
        } else {
            $data = BiliApi::get_user_info($access_key);
            if ($data->code != 0) {
                return $this->replyToChat(
                    $data->message,
                    [
                        'reply_to_message_id' => $msg_id
                    ]
                );
            }
            $uid = $data->data->mid;
            $db->insert_user_key($uid, $access_key);
        }

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

        // 检查是否是管理员
        $is_admin = false;
        $user_id = $message->getFrom()->getId();
        if ($this->telegram->isAdmin($user_id)) {
            $is_admin = true;
        }

        $uname = $info->data[0]->uname;

        $counter = $db->get_user_counter($uid);
        $data_ban = $db->get_user_ban($uid);

        $tail = $is_admin ? ('请求黑名单服务器次数: ' . ($counter ? $counter : 0)) : '';

        if (count($data_ban) > 0) {
            $data = $data_ban[0];
            $reason = $data['reason'] == '' ? '无' : $data['reason'];
            return $this->replyToChat(
                'UID: <code>' . $uid . '</code>' . PHP_EOL .
                    '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                    '该用户的封禁原因是: ' . $reason . PHP_EOL .
                    $tail,
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
                'UID: <code>' . $uid . '</code>' . PHP_EOL .
                    '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                    '白名单用户' . PHP_EOL .
                    $tail,
                [
                    'parse_mode' => 'HTML',
                    'reply_to_message_id' => $msg_id
                ]
            );
        }

        return $this->replyToChat(
            'UID: <code>' . $uid . '</code>' . PHP_EOL .
                '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                '该用户不是黑名单也不是白名单' . PHP_EOL .
                $tail,
            [
                'parse_mode' => 'HTML',
                'reply_to_message_id' => $msg_id
            ]
        );
    }
}
