<?php

namespace Qimo;

class BiliApi
{
    public static function get_user_info(string $access_key): mixed
    {
        $ts = time();
        $appkey = "1d8b6e7d45233436";
        $appsec = "560c52ccd288fed045859ed18bffd973";
        $sign = md5("access_key=" . $access_key . "&appkey=" . $appkey . "&ts=" . $ts . $appsec);
        $url = "https://app.bilibili.com/x/v2/account/myinfo?access_key=" . $access_key . "&appkey=" . $appkey . "&ts=" . $ts . "&sign=" . $sign;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);

        return json_decode($result);
    }

    public static function get_uid_info(int $uid)
    {
        $testurl = "https://api.vc.bilibili.com/account/v1/user/infos?uids=" . $uid;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result);
    }
}
