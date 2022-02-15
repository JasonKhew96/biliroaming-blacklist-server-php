<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Entities\InlineQuery\InlineQueryResultArticle;
use Longman\TelegramBot\Entities\InputMessageContent\InputTextMessageContent;
use Longman\TelegramBot\Entities\ServerResponse;
use PDOException;
use Qimo\DBHelper;
use Qimo\BiliApi;

set_include_path($_SERVER['DOCUMENT_ROOT'] . '/includes/');

require_once 'database.php';
require_once 'biliapi.php';

class InlinequeryCommand extends SystemCommand
{
    protected $name = 'inlinequery';
    protected $description = '内联';
    protected $version = '1.0.0';

    public function execute(): ServerResponse
    {
        $inline_query = $this->getInlineQuery();
        $query        = $inline_query->getQuery();

        $results = [];

        if (strlen($query) <= 0) {
            return $inline_query->answer($results);
        }

        if (!is_numeric($query)) {
            $results[] = new InlineQueryResultArticle([
                'id'                    => rand(),
                'title'                 => '非 UID',
                'input_message_content' => new InputTextMessageContent([
                    'message_text' => '非 UID',
                ]),
            ]);
            return $inline_query->answer($results);
        }
        $uid = intval($query);

        try {
            $db = new DBHelper();
        } catch (PDOException $e) {
            $results[] = new InlineQueryResultArticle([
                'id'                    => rand(),
                'title'                 => '数据库爆炸了',
                'input_message_content' => new InputTextMessageContent([
                    'message_text' => '数据库爆炸了',
                ]),
            ]);
            return $inline_query->answer($results);
        }

        $data_ban = $db->get_user_ban($uid);

        if (count($data_ban) > 0) {
            $info = BiliApi::get_uid_info($uid);
            if ($info->code != 0) {
                $results[] = new InlineQueryResultArticle([
                    'id'                    => rand(),
                    'title'                 => '未知错误',
                    'input_message_content' => new InputTextMessageContent([
                        'message_text' => '未知错误',
                    ]),
                ]);
                return $inline_query->answer($results);
            }

            if (count($info->data) <= 0) {
                $results[] = new InlineQueryResultArticle([
                    'id'                    => rand(),
                    'title'                 => '改用户不存在',
                    'input_message_content' => new InputTextMessageContent([
                        'message_text' => '改用户不存在',
                    ]),
                ]);
                return $inline_query->answer($results);
            }

            $uname = $info->data[0]->uname;
            
            $data = $data_ban[0];
            $reason = $data['reason'] == '' ? '无' : $data['reason'];
            $results[] = new InlineQueryResultArticle([
                'id'                    => $uid,
                'title'                 => '黑名单用户',
                'description'           => $reason,

                'input_message_content' => new InputTextMessageContent([
                    'parse_mode' => 'HTML',
                    'message_text' => 'UID: <code>' . $uid . '</code>' . PHP_EOL .
                        '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                        '该用户的封禁原因是: ' . $reason,
                ]),
            ]);
            return $inline_query->answer($results);
        }

        $data_white = $db->get_user_white($uid);
        if (count($data_white) > 0) {
            $data = $data_white[0];
            $results[] = new InlineQueryResultArticle([
                'id'                    => $uid,
                'title'                 => '白名单用户',

                'input_message_content' => new InputTextMessageContent([
                    'parse_mode' => 'HTML',
                    'message_text' => 'UID: <code>' . $uid . '</code>' . PHP_EOL .
                        '白名单用户',
                ]),
            ]);
            return $inline_query->answer($results);
        }

        $results[] = new InlineQueryResultArticle([
            'id'                    => $uid,
            'title'                 => '非黑白名单用户',

            'input_message_content' => new InputTextMessageContent([
                'parse_mode' => 'HTML',
                'message_text' => 'UID: <code>' . $uid . '</code>' . PHP_EOL .
                    '非黑白名单用户',
            ]),
        ]);

        return $inline_query->answer($results);
    }
}
