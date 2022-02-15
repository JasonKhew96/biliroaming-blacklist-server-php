<?php

use Qimo\BiliApi;
use Qimo\DBHelper;
use Qimo\Utils;

set_include_path('includes/');

require_once 'biliapi.php';
require_once 'database.php';
require_once 'utils.php';

$mode = @$_GET['mode'] ?: "white";

try {
    $db = new DBHelper();
} catch (PDOException $e) {
    exit('error');
}

if (isset($_GET['uid'])) {
    $uid = $_GET['uid'];
    if (Utils::is_valid_uid($uid)) {
        check_uid($db, $uid);
    }
} else if (isset($_GET['access_key'])) {
    $access_key = $_GET['access_key'];
    if (Utils::is_valid_key($access_key)) {
        check_key($db, $access_key);
    }
}


function check_uid(DBHelper $db, int $uid)
{
    global $mode;
    switch ($mode) {
        case 'white':
            $data = $db->get_user_white($uid);
            if (count($data)) {
                exit('pass');
            }
            exit('ban');
            break;

        default:
            $data = $db->get_user_ban($uid);
            if (count($data)) {
                exit('ban');
            }
            exit('pass');
            break;
    }
    exit('pass');
}

function check_key(DBHelper $db, string $access_key)
{
    $data = $db->get_user_key($access_key);
    $uid = 0;
    if (count($data) > 0) {
        $uid = $data[0]['uid'];
    } else {
        $data = BiliApi::get_user_info($access_key);
        if ($data->code != 0) {
            exit('error');
        }
        $uid = $data->data->mid;
        $db->insert_user_key($uid, $access_key);
    }
    check_uid($db, $uid);
}
