<?php
require_once 'vendor/autoload.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\InlineKeyboard;
use Qimo\DBHelper;
use Qimo\Utils;
use Qimo\BiliApi;

set_include_path('includes/');

require_once 'biliapi.php';
require_once 'database.php';
require_once 'utils.php';
require_once 'config.php';


function is_user_ban(DBHelper $db, int $uid): bool
{
    $data = $db->get_user_ban($uid);
    if (count($data)) {
        return true;
    }
    return false;
}

function is_user_reported(DBHelper $db, int $uid): bool
{
    $data = $db->get_reports($uid, false);
    if (count($data)) {
        return true;
    }
    return false;
}

$loader = new \Twig\Loader\FilesystemLoader('templates');
$twig = new \Twig\Environment($loader, [
    'cache' => 'cache',
]);

if (isset($_POST['check']) && isset($_POST['uid'])) {
    try {
        $db = new DBHelper();
    } catch (PDOException $e) {
        echo $twig->render('check.html', [
            'alert' => '数据库连接失败',
        ]);
        return;
    }

    $uid = $_POST['uid'];
    if (!Utils::is_valid_uid($uid)) {
        echo $twig->render('check.html', [
            'alert' => '无效 UID',
        ]);
        return;
    }

    $info = BiliApi::get_uid_info($uid);
    if ($info->code != 0) {
        echo $twig->render('report.html', ['alert' => '未知错误']);
        return;
    }

    if (count($info->data) <= 0) {
        echo $twig->render('report.html', ['alert' => '找不到此用户']);
        return;
    }

    if (is_user_ban($db, $uid)) {
        echo $twig->render('check.html', [
            'alert' => '用户 [' . $uname = $info->data[0]->uname . '] 已被拉黑',
        ]);
        return;
    }

    if (is_user_reported($db, $uid)) {
        echo $twig->render('check.html', [
            'alert' => '用户 [' . $uname = $info->data[0]->uname . '] 已被举报，待处理',
        ]);
        return;
    }
    echo $twig->render('check.html', ['alert' => '无记录']);
    return;
}

echo $twig->render('check.html');
