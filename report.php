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

if (isset($_POST['report']) && isset($_POST['uid']) && $_POST['source'] && $_POST['desc'] && isset($_FILES['screenshot'])) {
    try {
        $db = new DBHelper();
    } catch (PDOException $e) {
        echo $twig->render('report.html', [
            'alert' => '数据库连接失败',
        ]);
        return;
    }

    $uid = $_POST['uid'];
    if (!Utils::is_valid_uid($uid)) {
        echo $twig->render('report.html', [
            'alert' => '无效 UID',
        ]);
        return;
    }

    if (is_user_ban($db, $uid)) {
        echo $twig->render('report.html', [
            'alert' => '用户已被拉黑',
        ]);
        return;
    }

    if (is_user_reported($db, $uid)) {
        echo $twig->render('report.html', [
            'alert' => '用户已被举报，待处理',
        ]);
        return;
    }

    $source = $_POST['source'];
    $desc = $_POST['desc'];

    if (strlen($source) == 0 || strlen($source) > 16) {
        echo $twig->render('report.html', [
            'alert' => '来源过短或过长',
        ]);
        return;
    }

    if (strlen($desc) == 0 || strlen($desc) > 32) {
        echo $twig->render('report.html', [
            'alert' => '说明过短或过长',
        ]);
        return;
    }

    $name = $_FILES['screenshot']['name'];
    $target_dir = "upload/";
    $target_file = $target_dir . basename($name);

    // Select file type
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Valid file extensions
    $extensions_arr = array("jpg", "jpeg", "png");

    $saved_path = $target_dir . time() . '.' . $imageFileType;

    // Check extension
    if (in_array($imageFileType, $extensions_arr)) {
        // Upload file
        if (move_uploaded_file($_FILES['screenshot']['tmp_name'], $saved_path)) {
            // strip image meta data
            try {
                $img = new Imagick($saved_path);
                $profiles = $img->getImageProfiles("icc", true);
                $img->stripImage();
                if (!empty($profiles)) {
                    $img->profileImage("icc", $profiles['icc']);
                }
                $img->writeImage($saved_path);
                $img->destroy();
            } catch (ImagickException $e) {
            }

            // get image dimension
            list($width, $height) = getimagesize($saved_path);
            if ($width == false || $height == false) {
                // delete
                unlink($saved_path);
                echo $twig->render('report.html', ['alert' => '未知错误']);
                return;
            }
            // Insert record
            if ($db->insert_report($uid, $source, $desc, $_SERVER['REMOTE_ADDR'])) {
                try {
                    $telegram = new Longman\TelegramBot\Telegram(TG_BOT_TOKEN, TG_USERNAME);
                    $telegram->enableAdmins(TG_ADMIN);

                    $inline_keyboard = new InlineKeyboard([
                        ['text' => '拉黑', 'callback_data' => 'ban_' . $uid . '_' . $desc],
                        ['text' => '忽略', 'callback_data' => 'ignore_' . $uid],
                    ]);

                    $info = BiliApi::get_uid_info($uid);
                    if ($info->code != 0) {
                        echo $twig->render('report.html', ['alert' => '未知错误']);
                    }

                    if (count($info->data) <= 0) {
                        echo $twig->render('report.html', ['alert' => '找不到此用户']);
                    }

                    $uname = $info->data[0]->uname;

                    $ratio = 0.0;
                    if ($width > $height) {
                        $ratio = $width / $height;
                    } else {
                        $ratio = $height / $width;
                    }

                    if ($ratio > 2.5) {
                        Request::sendDocument([
                            'chat_id' => TG_REPORTS_CHAT,
                            'document' => Request::encodeFile($saved_path),
                            'reply_markup' => $inline_keyboard,
                            'caption' => '新封锁' . PHP_EOL .
                                'UID: <code>' . $uid . '</code>' . PHP_EOL .
                                '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                                '来源: ' . $_POST['source'] . PHP_EOL .
                                '说明: ' . $_POST['desc'],
                            'parse_mode' => 'HTML',
                        ]);
                    } else {
                        Request::sendPhoto([
                            'chat_id' => TG_REPORTS_CHAT,
                            'photo' => Request::encodeFile($saved_path),
                            'reply_markup' => $inline_keyboard,
                            'caption' => '新封锁' . PHP_EOL .
                                'UID: <code>' . $uid . '</code>' . PHP_EOL .
                                '用户空间: <a href="https://space.bilibili.com/' . $uid . '">' . $uname . '</a>' . PHP_EOL .
                                '来源: ' . $_POST['source'] . PHP_EOL .
                                '说明: ' . $_POST['desc'],
                            'parse_mode' => 'HTML',
                        ]);
                    }

                    // delete
                    unlink($saved_path);

                    echo $twig->render('report.html', [
                        'alert' => '已成功举报',
                    ]);
                    return;
                } catch (Longman\TelegramBot\Exception\TelegramException $e) {
                    file_put_contents('logs.txt', $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
                } catch (\Throwable $e) {
                    file_put_contents('logs.txt', $e->getMessage() . PHP_EOL, FILE_APPEND | LOCK_EX);
                }
            } else {
                echo $twig->render('report.html', ['alert' => '数据库出错']);
                return;
            }
        } else {
            echo $twig->render('report.html', ['alert' => '未知错误']);
            return;
        }
    }
}

echo $twig->render('report.html');
