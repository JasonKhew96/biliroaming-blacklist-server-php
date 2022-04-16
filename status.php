<?php

use Qimo\BiliApi;
use Qimo\DBHelper;
use Qimo\Utils;

set_include_path('includes/');

require_once 'biliapi.php';
require_once 'database.php';
require_once 'utils.php';

try {
    $db = new DBHelper();
} catch (PDOException $e) {
    Utils::write_json(500, $e->getMessage());
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

Utils::write_json(500, "参数错误");

function check_uid(DBHelper $db, int $uid)
{
    $is_blacklist = false;
    $is_whitelist = false;
    $reason = '';

    $data = $db->get_user_ban($uid);
    if (count($data)) {
        $is_blacklist = true;
        $reason = $data[0]['reason'];
    }

    $data = $db->get_user_white($uid);
    if (count($data)) {
        $is_whitelist = true;
    }

    if ($db->get_user($uid) > 0) {
        $db->user_counter_increment($uid);
    } else {
        $db->insert_user($uid);
    }

    Utils::write_json_extra($uid, $reason, $is_blacklist, $is_whitelist);
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
            Utils::write_json(500, $data->message);
        }
        $uid = $data->data->mid;
        $db->insert_user_key($uid, $access_key);
    }
    check_uid($db, $uid);
}
