<?php

namespace api\config;

// показывать сообщения об ошибках 
error_reporting(E_ALL);

// установить часовой пояс по умолчанию 
date_default_timezone_set('Europe/Moscow');

class Config
{
    // переменные, используемые для JWT 
    public static $key = "vsdf2f2h9feu2fh923d###@eafhi";
    public static $iss = "http://any-site.org";
    public static $aud = "http://any-site.com";
    public static $iat = 1356999524;
    public static $nbf = 1357000000;
};
