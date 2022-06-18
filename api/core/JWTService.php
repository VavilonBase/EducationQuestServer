<?php

namespace api\core;

require $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';

use api\core\Message;
use api\config\Config;
use api\core\Response;
use DomainException;
use Exception;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

class JWTService
{
    public static function encode($payload)
    {
        try {
            $token = array(
                "iss" => Config::$iss,
                "aud" => Config::$aud,
                "exp" => time() + 10000,
                "iat" => Config::$iat,
                "nbf" => Config::$nbf,
                "data" => array(
                    "id" => $payload->id,
                    "firstName" => $payload->firstName,
                    "lastName" => $payload->lastName,
                    "login" => $payload->login,
                    "role" => $payload->role,
                    "isActivated" => $payload->isActivated
                )
            );

            // создание jwt 
            $jwt = JWT::encode($token, Config::$key, 'HS256');

            return $jwt;
        } catch (ExpiredException $e) {
            Response::sendError(Message::$messages['UnauthorizedUser'], 401);
        } catch (DomainException $e) {
            Response::sendError(Message::$messages['UnauthorizedUser'], 401);
        }
    }

    public static function decode($jwt)
    {
        try {
            // декодирование jwt 
            $decode = JWT::decode($jwt, new Key(Config::$key, 'HS256'));
            return $decode->data;
        } catch (ExpiredException $e) {
            Response::sendError(Message::$messages['UnauthorizedUser'], 401);
        } catch (DomainException $e) {
            Response::sendError(Message::$messages['UnauthorizedUser'], 401);
        } catch (Exception $e) {
            Response::sendError(Message::$messages['UnauthorizedUser'], 401);
        }
    }
}
