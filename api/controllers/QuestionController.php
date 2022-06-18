<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\core\Url;
use api\models\Question;

class QuestionController
{
    public Question $model;

    public function __construct()
    {
        $this->model = new Question();
    }

    // Создание вопроса
    public function createAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем и проверяем данные
        $question = $this->checkAddQuestionData($_POST);

        // Создаем вопрос
        $newQuestion = $this->model->create(
            $user->id,
            $question['testId'],
            $question['question'],
            $question['isText'],
            $question['scores']
        );

        // Отправляем ответ
        Response::sendSuccess($newQuestion);
    }

    // Обновление вопроса
    public function updateAction()
    {

        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем и проверяем данные
        $question = $this->checkUpdateQuestionData($_POST);

        // Изменяем вопрос
        $changedQuestion = $this->model->update(
            $user->id,
            $question['questionId'],
            $question['question'],
            $question['isText'],
            $question['scores']
        );

        // Отправляем ответ
        Response::sendSuccess($changedQuestion);
    }

    // Получение вопроса с ответами
    public function getQuestionWithAnswersAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id вопроса из url
        $questionId = Url::getParam('questionId');

        // Выполняем получение
        Response::sendSuccess($this->model->getQuestionWithAnswers(
            $user->id,
            $questionId
        ));
    }

    // Удаление вопроса
    public function deleteAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id вопроса из url
        $questionId = Url::getParam('questionId');

        // Выполняем удаление
        $this->model->delete(
            $user->id,
            $questionId
        );

        // Если не произошло ошибки, возвращаем true
        Response::sendSuccess(true);
    }

    // Проверка данных для добавления вопроса
    private function checkAddQuestionData($questionData)
    {
        // получаем данные 
        $testId = $questionData['testId'];
        $isText = $questionData['isText'];
        $scores = $questionData['scores'];
        if (isset($questionData['question'])) $question = $questionData['question'];
        else $question = '';

        // Проверяем, что все данные переданы
        if (
            !isset($testId) ||
            !isset($isText) ||
            !isset($scores)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        return array(
            'testId' => $testId,
            'isText' => $isText == '1',
            'question' => $question,
            'scores' => $scores
        );
    }

    // Проверка данных для изменения вопроса
    private function checkUpdateQuestionData($questionData)
    {
        // получаем данные
        $questionId = $questionData['questionId'];
        $isText = $questionData['isText'];
        $scores = $questionData['scores'];

        if (isset($questionData['question'])) $question = $questionData['question'];
        else $question = '';

        // Проверяем, что все данные переданы
        if (
            !isset($questionId) ||
            !isset($isText) ||
            !isset($scores)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        return array(
            'questionId' => $questionId,
            'isText' => $isText == '1',
            'question' => $question,
            'scores' => $scores
        );
    }
}
