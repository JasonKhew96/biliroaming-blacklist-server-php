<?php

namespace Qimo;

use PDO;

require_once 'config.php';

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
        $query = "SELECT COUNT(*) FROM `ban`";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    function get_user_ban(int $uid): array
    {
        $query = "SELECT * FROM `ban` WHERE `uid` = ? ORDER BY `add_time` DESC LIMIT 1;";
        $stat = $this->conn->prepare($query);
        $stat->execute(array($uid));
        return $stat->fetchAll();
    }

    function insert_user_ban(int $uid, string $add_from, string $reason): bool
    {
        $query = "INSERT INTO `ban` (`add_time`, `uid`, `add_from`, `reason`) VALUES (now(), ?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, $add_from, $reason));
    }

    function update_user_ban(int $uid, string $reason): bool
    {
        $query = "UPDATE `ban` SET `reason` = ? WHERE `uid` = ?;";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($reason, $uid));
    }

    function remove_user_ban(int $uid): bool
    {
        $query = "DELETE FROM `ban` WHERE `uid` = ?;";
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

    function insert_user_white(int $uid, string $add_from, string $reason): bool
    {
        $query = "INSERT INTO `white` (`add_time`, `uid`, `add_from`, `reason`) VALUES (now(), ?, ?, ?);";
        $stat = $this->conn->prepare($query);
        return $stat->execute(array($uid, $add_from, $reason));
    }

    function remove_user_white(int $uid): bool
    {
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

    /*
    CREATE TABLE `reports` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `uid` BIGINT UNSIGNED NOT NULL,
    `source` VARCHAR(16) NOT NULL,
    `desc` VARCHAR(16) NOT NULL,
    `from_ip` VARCHAR(45) NOT NULL,
    `is_deleted` BOOLEAN NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL
    );
    */

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
}
