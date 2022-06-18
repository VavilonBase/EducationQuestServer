<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\core\Url;
use api\models\User;

class UserController
{
    private User $model;

    public function __construct()
    {
        $this->model = new User;
    }

    // Вход
    public function loginAction()
    {
        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // проверяем все ли данные есть
        if (empty($data->login) || empty($data->password))
            Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // выполняем вход
        Response::sendSuccess($this->model->login($data->login, $data->password));
    }

    // Регистрация
    public function registrationAction()
    {
        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));
        // проверяем все ли данные есть
        if (
            empty($data->firstName) ||
            empty($data->lastName) ||
            empty($data->login) ||
            empty($data->password) ||
            empty($data->role)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Обрабатываем данные
        $middleName = $data->middleName ?: '';
        // Выполняем регистрацию
        Response::sendSuccess($this->model->registration($data->lastName, $data->firstName,
            $middleName, $data->role, $data->login, $data->password));
    }

    // Обновление токена
    public function refreshTokenAction()
    {
        // Проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Выполняем обновление и возвращаем ответ
        Response::sendSuccess($this->model->refreshToken($user->id));
    }

    // Активация учителя
    public function activateTeacherAction()
    {
        // Проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что роль равна админу
        Auth::check_role(array("ADMIN"), $user->role, $user->isActivated);

        // Получаем id учителя из url
        $teacherId = Url::getParam('teacherId');

        // Если параметр передан, и пользователь активированный админ, вызываем активацию
        Response::sendSuccess($this->model->activateTeacher($teacherId));
    }

    // Смена пароля
    public function changePasswordAction()
    {
        // Проверяем авторизован ли пользователь
        $userDecode = Auth::checkAuthorization();

        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что были отправлены все данные
        if (!$data->lastPassword || !$data->password) Response::sendError(Message::$messages['NotFountRequiredData'], 400);

        // Если все данные переданы и пользователь авторизован, то меняем пароль и возвращаем ответ
        Response::sendSuccess($this->model->changePassword($userDecode->id, $data->lastPassword, $data->password));
    }

    // Получение всех пользователей
    public function getAllUsersAction()
    {
        // Проверяем авторизован ли пользователь
        $userDecode = Auth::checkAuthorization();

        // Проверяем, что роль равна админу
        Auth::check_role(array("ADMIN"), $userDecode->role, $userDecode->isActivated);

        // Получаем роли для поиска из url
        $role = Url::getParam('role');

        // Если все данные переданны и пользователь авторизован, то меняем пароль и возвращаем ответ
        Response::sendSuccess($this->model->getAllUsers($role));
    }

    // Обновление пользователя
    public function updateAction()
    {
        // Проверяем авторизован ли пользователь
        $userDecode = Auth::checkAuthorization();
        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));
        // проверяем все ли данные есть
        if (
            empty($data->firstName) ||
            empty($data->lastName) ||
            empty($data->middleName) ||
            empty($data->role)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        Response::sendSuccess($this->model->update($userDecode->id, $data->lastName, $data->firstName, $data->middleName, $data->role));
    }
}
