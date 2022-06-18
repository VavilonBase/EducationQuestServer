<?php

namespace api\lib;

use PDO;
use api\core\Response;
use api\core\Message;

class Db
{

    protected $db;

    public function __construct()
    {
        $config = require 'api/config/db.php';
        $this->db = new PDO('pgsql:host=' . $config['host'] . ';dbname=' . $config['name'] . '', $config['user'], $config['password']);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    }

    public function query($sql, $params = [])
    {
        $stmt = $this->db->prepare($sql);
        if (!empty($params)) {
            foreach ($params as $key => $val) {
                $stmt->bindValue(':' . $key, $val);
            }
        }
        try {
            $stmt->execute();
        } catch (Exception $ex) {
            Response::sendError(Message::$messages['DBErrorExecute'], 500);
        }
        return $stmt;
    }

    public function row($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    public function column($sql, $params = [])
    {
        $result = $this->query($sql, $params);
        return $result->fetchColumn();
    }
}
