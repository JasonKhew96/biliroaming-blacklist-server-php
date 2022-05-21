<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Request;
use PDOException;

use Qimo\DBHelper;
use Qimo\Utils;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'biliapi.php';
require_once 'database.php';
require_once 'utils.php';
require_once 'config.php';

class CallbackqueryCommand extends SystemCommand
{
    protected $name = 'callbackquery';
    protected $description = 'callback';
    protected $version = '1.0.0';
    public function execute(): ServerResponse
    {
        // Callback query data can be fetched and handled accordingly.
        $callback_query = $this->getCallbackQuery();
        $callback_data  = $callback_query->getData();

        // 检查是否是管理员
        $user_id = $callback_query->getFrom()->getId();
        if (!$this->telegram->isAdmin($user_id)) {
            return $callback_query->answer([
                'text'       => '不好意思，您并不是管理员，请不要乱尝试，谢谢',
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        }

        try {
            $db = new DBHelper();
        } catch (PDOException $e) {
            return $callback_query->answer([
                'text'       => '数据库连接失败',
                'show_alert' => true,
                'cache_time' => 5,
            ]);
        }

        $msg = $callback_query->getMessage();

        try {
            if (str_starts_with($callback_data, 'ban_')) {
                $data = explode('_', $callback_data);
                if (count($data) == 3) {
                    $uid = $data[1];
                    $reason = $data[2];

                    if (Utils::is_valid_uid($uid) && $db->set_report_delete($uid, true) && count($db->get_user_ban($uid)) == 0 && $db->insert_user_ban($uid, '', $user_id, $reason) && Request::editMessageReplyMarkup([
                        'chat_id' => $msg->getChat()->getId(),
                        'message_id' => $msg->getMessageId(),
                    ])->isOk()) {
                        $msg = $callback_query->getMessage();
                        Request::copyMessage([
                            'chat_id' => TG_CHANNEL_ID,
                            'from_chat_id' => $msg->getChat()->getId(),
                            'message_id' => $msg->getMessageId(),
                        ]);
                        Request::deleteMessage([
                            'chat_id' => $msg->getChat()->getId(),
                            'message_id' => $msg->getMessageId(),
                        ]);
                        return $callback_query->answer([
                            'text'       => '成功封禁',
                            'show_alert' => false,
                            'cache_time' => 5,
                        ]);
                    } else {
                        Request::deleteMessage([
                            'chat_id' => $msg->getChat()->getId(),
                            'message_id' => $msg->getMessageId(),
                        ]);
                        return $callback_query->answer([
                            'text'       => '封禁失败',
                            'show_alert' => true,
                            'cache_time' => 5,
                        ]);
                    }
                } else {
                    return $callback_query->answer([
                        'text'       => '封禁失败',
                        'show_alert' => true,
                        'cache_time' => 5,
                    ]);
                }
            } else if (str_starts_with($callback_data, 'ignore_')) {
                $data = explode('_', $callback_data);
                if (count($data) == 2) {
                    $uid = $data[1];
                    if (Utils::is_valid_uid($uid) && $db->set_report_delete($uid, true) && Request::editMessageReplyMarkup([
                        'chat_id' => $msg->getChat()->getId(),
                        'message_id' => $msg->getMessageId(),
                        'inline_message_id' => $callback_query->getInlineMessageId(),
                    ])->isOk()) {
                        Request::deleteMessage([
                            'chat_id' => $msg->getChat()->getId(),
                            'message_id' => $msg->getMessageId(),
                        ]);
                        return $callback_query->answer([
                            'text'       => '已忽略',
                            'show_alert' => false,
                            'cache_time' => 5,
                        ]);
                    } else {
                        return $callback_query->answer([
                            'text'       => '忽略失败',
                            'show_alert' => true,
                            'cache_time' => 5,
                        ]);
                    }
                }
            } else if (str_starts_with($callback_data, 'query_')) {
                $data = explode('_', $callback_data);
                if (count($data) == 2) {
                    $uid = $data[1];
                    $user = $db->get_user_from_uid($uid);
                    if (Utils::is_valid_uid($uid)) {
                        return $callback_query->answer([
                            'text'       => '请求黑名单服务器次数: ' . ($user['counter'] ? $user['counter'] : 0) . PHP_EOL .
                                '上一次请求: ' . $user['updated_at'],
                            'show_alert' => true,
                            'cache_time' => 5,
                        ]);
                    }
                }
            }
        } catch (\Throwable $th) {
            return $callback_query->answer([
                'text'       => $th->getMessage(),
                'show_alert' => true,
                'cache_time' => 60,
            ]);
        }

        return $callback_query->answer([
            'text'       => '?',
            'show_alert' => true,
            'cache_time' => 60,
        ]);
    }
}
