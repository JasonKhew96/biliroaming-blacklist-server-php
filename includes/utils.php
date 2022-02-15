<?php

namespace Qimo;

class Utils
{
    public static function is_valid_uid(mixed $uid): bool
    {
        if (!is_numeric($uid)) {
            return false;
        }
        $uid = intval($uid);
        if ($uid <= 0) {
            return false;
        }
        return true;
    }

    public static function is_valid_key(mixed $access_key): bool
    {
        if (!is_string($access_key)) {
            return false;
        }
        $access_key = strval($access_key);
        if (!preg_match("/\w{32}/", $access_key)) {
            return false;
        }
        return true;
    }

    public static function write_json($code, $msg)
    {
        header('Content-Type: application/json; charset=utf-8');
        $resp['code'] = $code;
        $resp['message'] = $msg;
        echo json_encode($resp);
        exit();
    }

    public static function write_json_extra($uid, $reason, $is_blacklist, $is_whitelist)
    {
        header('Content-Type: application/json; charset=utf-8');
        $resp['code'] = 0;
        $resp['message'] = '0';
        $resp['data']['uid'] = $uid;
        $resp['data']['is_blacklist'] = $is_blacklist;
        $resp['data']['is_whitelist'] = $is_whitelist;
        $resp['data']['reason'] = $reason;
        echo json_encode($resp);
        exit();
    }
}
