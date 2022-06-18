<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\core\Url;
use api\models\Test;

class TestController
{
    private Test $model;

    public function __construct()
    {
        $this->model = new Test();
    }

    // Создание теста
    public function createAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (
            empty($data->title) &&
            !isset($data->groupId) &&
            !isset($data->canViewResults)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Создаем тест
        $test = $this->model->create($user->id, $data->groupId, $data->title, $data->canViewResults);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($test);
    }

    // Обновление теста
    public function updateAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем данные 
        $data = json_decode(file_get_contents("php://input"));

        // Проверяем, что все данные переданы
        if (
            empty($data->title) &&
            !isset($data->testId) &&
            !isset($data->canViewResults)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->update($data->testId, $user->id, $data->title, $data->canViewResults));
    }

    // Удаление теста
    public function deleteAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        // Если все данные переданы, то выполняем создание группы и возвращаем ответ
        Response::sendSuccess($this->model->delete($testId, $user->id));
    }

    // Получение всех тестов группы
    public function getAllGroupTestsAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Получаем id группы из url
        $groupId = Url::getParam('groupId');

        Response::sendSuccess(
            $this->model->getAllGroupTests($groupId)
        );
    }

    // Получение максимального числа очков за тест
    public function getMaxScoresForTestByTestIdAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        Response::sendSuccess($this->model->getMaxScoresForTestByTestId($testId));
    }

    // Получение теста с вопросами по его ID
    public function getTestWithQuestionByTestIdAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        Response::sendSuccess(
            $this->model->getTestByTestIdWithQuestions($user->id, $testId, $user->role)
        );
    }

    // Открытие теста
    public function openTestAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        Response::sendSuccess(
            $this->model->openTest($testId, $user->id)
        );
    }

    // Открытие теста
    public function closeTestAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        Response::sendSuccess(
            $this->model->closeTest($testId, $user->id)
        );
    }

    // Получение результатов теста студента
    public function getStudentTestResultAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Получаем id студента и id теста из url
        $studentId = Url::getParam('studentId');
        $testId = Url::getParam('testId');

        Response::sendSuccess(
            $this->model->getStudentTestResult($user->id, $studentId, $testId)
        );
    }

    // Получение результатов теста студента с правильными ответами
    public function getStudentTestResultWithRightAnswerAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Получаем id результата из url
        $resultId = Url::getParam('resultId');

        Response::sendSuccess(
            $this->model->getStudentTestResultWithRightAnswer($user->id, $user->role, $resultId)
        );
    }

    // Получение результатов теста студентов
    public function getAllResultsTestForStudentsAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id теста из url
        $testId = Url::getParam('testId');

        Response::sendSuccess(
            $this->model->getAllResultsTestForStudents($user->id, $testId)
        );
    }
}
