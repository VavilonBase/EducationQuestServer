<?php

namespace api\core;

use api\core\Message;

class Response
{
    public static function createResponse(bool $isError, int $message, $data)
    {
        return array(
            "isError" => $isError,
            "message" => $message,
            "data" => $data
        );
    }

    public static function sendError(int $message, $status)
    {
        // код ответа 
        http_response_code($status);

        // сказать пользователю что войти не удалось из-за ошибки выполнения запроса в базе данных
        echo json_encode(Response::createResponse(true, $message, null));
        exit();
    }

    public static function sendSuccess($data)
    {
        // код ответа 
        http_response_code(200);

        // сказать пользователю что войти не удалось из-за ошибки выполнения запроса в базе данных
        echo json_encode(Response::createResponse(false, Message::$messages["NotError"], $data));
        exit();
    }
}
