<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\core\Url;
use api\models\Group;

class GroupController
{
    private Group $model;

    public function __construct()
    {
        $this->model = new Group();
    }

    // Создание группы
    public function createAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (empty($data->title))
            Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->create($user->id, $data->title));
    }

    // Обновление группы
    public function updateAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (empty($data->title) && !isset($data->groupId))
            Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->update($data->groupId, $user->id, $data->title));
    }

    // Удаление группы
    public function deleteAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id группы из url
        $groupId = Url::getParam('groupId');

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->delete($user->id, $groupId));
    }

    // Удаление ученика из группы
    public function removeStudentFromGroupAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        $data = json_decode(file_get_contents('php://input'));

        // Проверяем, что все данные переданы
        if (!isset($data->studentId) || !isset($data->groupId)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->removeStudentFromGroup($user->id, $data->studentId, $data->groupId));
    }

    // Выход ученика из группы
    public function leavingStudentFromGroupAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('STUDENT'), $user->role, $user->isActivated);

        $data = json_decode(file_get_contents('php://input'));

        // Проверяем, что все данные переданы
        if (!isset($data->groupId)) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->leavingStudentFromGroup($user->id, $data->groupId));
    }

    // Получение всех групп учителя
    public function getAllTeacherGroupsAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        Response::sendSuccess(
            $this->model->getAllTeacherGroups($user->id)
        );
    }

    // Получение всех учеников группы
    public function getAllGroupStudentsAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id группы из url
        $groupId = Url::getParam('groupId');
        // Получаем is_study из url
        $isStudy = Url::getParam('isStudy') == '1';

        Response::sendSuccess(
            $this->model->getAllGroupStudents($groupId, $isStudy)
        );
    }

    // Получение всех групп студента
    public function getAllStudentGroupsAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('STUDENT'), $user->role, $user->isActivated);

        Response::sendSuccess($this->model->getAllStudentGroups($user->id));
    }

    // Присоединение к группе
    public function joinAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('STUDENT'), $user->role, $user->isActivated);

        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (empty($data->codeWord)) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        Response::sendSuccess($this->model->joinToTheGroup($user->id, $data->codeWord));
    }
}
