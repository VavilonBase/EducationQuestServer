<?php

namespace api\controllers;

use api\core\Message;
use api\core\Response;
use api\core\Auth;
use api\core\Url;
use api\models\Answer;

class AnswerController
{
    public Answer $model;

    public function __construct()
    {
        $this->model = new Answer();
    }

    // Создание вопроса
    public function createAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем и проверяем данные
        $answer = $this->checkAddAnswerData($_POST);

        // Создаем ответ
        $newAnswer = $this->model->create(
            $user->id,
            $answer['questionId'],
            $answer['answer'],
            $answer['isText'],
            $answer['isRightAnswer']
        );

        // Отправляем ответ
        Response::sendSuccess($newAnswer);
    }

    // Обновление вопроса
    public function updateAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // получаем и проверяем данные
        $answer = $this->checkUpdateAnswerData($_POST);

        // Изменяем вопрос
        $changeAnswer = $this->model->update(
            $user->id,
            $answer['answerId'],
            $answer['answer'],
            $answer['isText'],
            $answer['isRightAnswer'],
        );

        // Отправляем ответ
        Response::sendSuccess($changeAnswer);
    }

    // Удаление ответа
    public function deleteAction()
    {
        // проверяем авторизован ли пользователь
        $user = Auth::checkAuthorization();

        // Проверяем, что он учитель
        Auth::check_role(array('TEACHER'), $user->role, $user->isActivated);

        // Получаем id ответа из url
        $answerId = Url::getParam('answerId');

        // Выполняем удаление
        $this->model->delete(
            $user->id,
            $answerId
        );

        // Если не произошло ошибки, возвращаем true
        Response::sendSuccess(true);
    }

    // Проверка данных для добавления вопроса
    private function checkAddAnswerData(array $answerData): array
    {
        // получаем данные
        $questionId = $answerData['questionId'];
        $isText = $answerData['isText'];
        $isRightAnswer = $answerData['isRightAnswer'];
        $answer = $answerData['answer'] ?? '';

        // Проверяем, что все данные переданы
        if (
            !isset($questionId) ||
            !isset($isText) ||
            !isset($isRightAnswer)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        return array(
            'questionId' => $questionId,
            'isText' => $isText == '1',
            'answer' => $answer,
            'isRightAnswer' => $isRightAnswer == '1'
        );
    }

    // Проверка данных для изменения вопроса
    private function checkUpdateAnswerData(array $answerData): array
    {
        // получаем данные
        $answerId = $answerData['answerId'];
        $isText = $answerData['isText'];
        $isRightAnswer = $answerData['isRightAnswer'];
        $answer = $answerData['answer'] ?? '';

        // Проверяем, что все данные переданы
        if (
            !isset($answerId) ||
            !isset($isText) ||
            !isset($isRightAnswer)
        ) Response::sendError(Message::$messages['NotFoundRequiredData'], 400);

        return array(
            'answerId' => $answerId,
            'isText' => $isText == '1',
            'answer' => $answer,
            'isRightAnswer' => $isRightAnswer == '1'
        );
    }
}
