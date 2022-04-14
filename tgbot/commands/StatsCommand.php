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
    protected $description = '\[管理员] 显示统计数据指令';
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
        $pending_report = $db->get_total_pending_report();
        $total_report = $db->get_total_report();
        $text = '被封禁用户: ' . $count . PHP_EOL . '待处理网页举报: ' . $pending_report . PHP_EOL . '总网页举报: ' . $total_report;
        return $this->replyToChat($text, [
            'reply_to_message_id' => $msg_id
        ]);
    }
}
