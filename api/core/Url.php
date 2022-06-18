<?php

namespace api\core;

use api\core\Response;
use api\core\Message;

class Url
{
    public static function getParam(string $param)
    {
        $query_string = $_SERVER['QUERY_STRING'];
        parse_str($query_string, $params);

        if (!isset($params[$param])) {
            Response::sendError(Message::$messages["NotFoundQueryParam"], 400);
        } else {
            return $params[$param];
        }
    }
}
