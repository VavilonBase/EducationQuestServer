<?php

namespace api\core;

use api\lib\Db;

abstract class Model
{

    public $db;

    public function __construct()
    {
        $this->db = new Db;
    }
}
