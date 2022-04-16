<?php

namespace Qimo;

use PDO;

require_once 'config.php';

/*
CREATE TABLE `ban` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `uid` int(20) DEFAULT NULL,
  `add_from` text DEFAULT NULL,
  `reason` text DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE `keys` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `add_time` datetime NOT NULL,
  `uid` int(20) DEFAULT NULL,
  `access_key` varchar(100) DEFAULT NULL,
  `login` tinyint(1) NOT NULL DEFAULT 1,
  `due_date` bigint(20) DEFAULT NULL
);

CREATE TABLE `reports` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `uid` bigint(20) UNSIGNED NOT NULL,
  `source` varchar(128) NOT NULL,
  `desc` varchar(128) NOT NULL,
  `from_ip` varchar(45) NOT NULL,
  `is_deleted` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE `audits` (
  `id` int(10) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
  `uid` bigint(20) UNSIGNED NOT NULL,
  `actions` tinyint(3) UNSIGNED NOT NULL,
  `from_ip` varchar(45) DEFAULT NULL,
  `from_tg` bigint(20) UNSIGNED DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);

CREATE TABLE `users` (
  `uid` bigint(20) UNSIGNED NOT NULL PRIMARY KEY,
  `counter` int(10) UNSIGNED NOT NULL DEFAULT 1,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
);
*/

abstract class Actions
{
    const unknown = 0;
    const ban = 1;
    const unban = 2;
    const white = 3;
    const unwhite = 4;
    const update = 5;
}

class DBHelper
{

    private ?PDO $conn;

    function __construct()
    {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME;
        $this->conn = new PDO($dsn, DB_USERNAME, DB_PASSWORD);
        $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    function __destruct()
    {
        $this->conn = null;
    }

    function get_total_user_ban(): int
    {
        $query = "SELECT COUNT(*) FROM `ban` WHERE `is_deleted` = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    function get_user_ban(int $uid): array
    {
        $query = "SELECT * FROM `ban` WHERE `uid` = ? AND `is_deleted` = 0 ORDER BY `updated_at` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        $stat->execute(array($uid));
        return $stat->fetchAll();
    }

    function insert_user_ban(int $uid, string $from_ip, int $from_tg, string $reason): bool
    {
        $this->insert_audit($uid, Actions::ban, $from_ip, $from_tg);
        $query = "INSERT INTO `ban` (`uid`, `add_from`, `reason`) VALUES (?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, 'TG@' . $from_tg, $reason));
    }

    function update_user_ban(int $uid, string $from_ip, int $from_tg, string $reason): bool
    {
        $this->insert_audit($uid, Actions::update, $from_ip, $from_tg);
        $query = "UPDATE `ban` SET `reason` = ? WHERE `uid` = ? AND `is_deleted` = 0 ORDER BY `updated_at` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($reason, $uid));
    }

    function remove_user_ban(int $uid, string $from_ip, int $from_tg): bool
    {
        $this->insert_audit($uid, Actions::unban, $from_ip, $from_tg);
        $query = "UPDATE `ban` SET `is_deleted` = 1 WHERE `uid` = ? AND `is_deleted` = 0 ORDER BY `updated_at` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid));
    }

    function get_user_white(int $uid): array
    {
        $query = "SELECT * FROM `white` WHERE `uid` = ? ORDER BY `add_time` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        $stat->execute(array($uid));
        return $stat->fetchAll();
    }

    function insert_user_white(int $uid, string $from_ip, int $from_tg, string $reason): bool
    {
        $this->insert_audit($uid, Actions::white, $from_ip, $from_tg);
        $query = "INSERT INTO `white` (`add_time`, `uid`, `add_from`, `reason`) VALUES (now(), ?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, 'TG@' . $from_tg, $reason));
    }

    function remove_user_white(int $uid, string $from_ip, int $from_tg): bool
    {
        $this->insert_audit($uid, Actions::unwhite, $from_ip, $from_tg);
        $query = "DELETE FROM `white` WHERE `uid` = ?;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid));
    }

    function get_user_key(string $access_key): array
    {
        $query = "SELECT * FROM `keys` WHERE `access_key` = ? ORDER BY `add_time` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        $stat->execute(array($access_key));
        return $stat->fetchAll();
    }

    function insert_user_key(int $uid, string $access_key): bool
    {
        $query = "INSERT INTO `keys` (`add_time`, `uid`, `access_key`) VALUES (now(), ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, $access_key));
    }

    function insert_report(int $uid, string $source, string $desc, string $from_ip): bool
    {
        $query = "INSERT INTO `reports` (`uid`, `source`, `desc`, `from_ip`) VALUES (?, ?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, $source, $desc, $from_ip));
    }

    function get_reports(int $uid, bool $is_deleted): array
    {
        $query = "SELECT * FROM `reports` WHERE `uid` = ? AND `is_deleted` = ? ORDER BY `created_at` DESC;";
        $stat = $this->conn->prepare($query);
        $stat->execute(array($uid, $is_deleted));
        return $stat->fetchAll();
    }

    function set_report_delete(int $uid, bool $is_deleted): bool
    {
        $query = "UPDATE `reports` SET `is_deleted` = ? WHERE `uid` = ?;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($is_deleted, $uid));
    }

    function get_total_pending_report(): int
    {
        $query = "SELECT COUNT(*) FROM `reports` WHERE `is_deleted` = 0;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    function get_total_report(): int
    {
        $query = "SELECT COUNT(*) FROM `reports`;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    function insert_audit(int $uid, int $actions, string $from_ip, int $from_tg): bool
    {
        $query = "INSERT INTO `audits` (`uid`, `actions`, `from_ip`, `from_tg`) VALUES (?, ?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, $actions, $from_ip, $from_tg));
    }

    function get_user(int $uid): int
    {
        $query = "SELECT COUNT(*) FROM `users` WHERE `uid` = ?;";
        $stmt = $this->conn->prepare($query);
        $stmt->execute(array($uid));
        return $stmt->fetchColumn();
    }

    function insert_user(int $uid): bool
    {
        $query = "INSERT INTO `users` (`uid`) VALUES (?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid));
    }

    function user_counter_increment(int $uid): bool
    {
        $query = "UPDATE `users` SET `counter` = `counter` + 1 WHERE `uid` = ?;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid));
    }
}
