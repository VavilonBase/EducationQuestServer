<?php

namespace api\core;

use api\core\Response;
use api\core\Message;

class Router
{

    protected $routes = [];
    protected $params = [];

    public function __construct()
    {
        $arr = require 'api/config/routes.php';
        foreach ($arr as $key => $val) {
            $this->add($key, $val);
        }
    }

    public function add($route, $params)
    {
        $route = '#^' . $route . '$#';
        $this->routes[$route] = $params;
    }

    public function match()
    {
        $url = trim($_SERVER['REQUEST_URI'], '/');
        //$url = substr($_SERVER['REQUEST_URI'], 18);
        foreach ($this->routes as $route => $params) {
            if (preg_match($route, $url, $matches)) {
                $this->params = $params;
                return true;
            }
        }
        return false;
    }

    public function run()
    {
        if ($this->match()) {
            $path = 'api\controllers\\' . ucfirst($this->params['controller']) . 'Controller';
            if (class_exists($path)) {
                $action = $this->params['action'] . 'Action';
                if (method_exists($path, $action)) {
                    $controller = new $path($this->params);
                    $controller->$action();
                } else {
                    Response::sendError(Message::$messages['PageNotFound'], 404);
                }
            } else {
                Response::sendError(Message::$messages['PageNotFound'], 404);
            }
        } else {
            Response::sendError(Message::$messages['PageNotFound'], 404);
        }
    }
}
