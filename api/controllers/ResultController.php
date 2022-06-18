<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\models\Result;

class ResultController
{
    private Result $model;

    public function __construct()
    {
        $this->model = new Result();
    }

    // Создание результата
    public function createAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('STUDENT'), $user->role, $user->isActivated);

        // Получаем данные
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (!isset($data->answers) || !isset($data->testId))
            Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Создаем вопрос
        $newResult = $this->model->create(
            $user->id,
            $data->testId,
            $data->answers
        );

        // Отправляем ответ
        Response::sendSuccess($newResult);
    }
}
