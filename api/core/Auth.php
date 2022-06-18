<?php

namespace api\core;

use api\core\Message;
use api\core\JWTService;
use api\core\Response;
use api\models\User;

class Auth
{
    public static function checkAuthorization()
    {
        // Получаем все заголовки
        $header = getallheaders();
        // Достаем тип токена и проверяем его валидность
        if (!$header['Authorization'] && substr($header['Authorization'], 0, 7) !== 'Bearer ')
            Response::sendError(Message::$messages['IncorrectTokenFormat'], 400);
        // Достаем сам токен
        $token = trim(substr($header['Authorization'], 7));
        // Пробуем его декодировать, если удалось, то токен валидный
        $userDecode = JWTService::decode($token);
        return $userDecode;
    }

    public static function check_role(array $requiredRoles, string $currentRole, bool $activated)
    {
        if ($activated) {
            foreach ($requiredRoles as $role) {
                if ($currentRole == $role) {
                    return true;
                }
            }
            Response::sendError(Message::$messages['AccessDenied'], 403);
        } else {
            Response::sendError(Message::$messages['AccessDenied'], 403);
        }
    }
}
