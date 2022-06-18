<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\core\YandexDisk;
use api\modelsSql\AnswerSql;
use api\modelsSql\QuestionSql;
use PDO;

class Answer extends Model
{
    public int $answerId;
    public int $questionId;
    public string $answer;
    public bool $isText;
    public bool $isRightAnswer;

    // Создание вопроса
    public function create(int $userId, int $questionId, string $answer, bool $isText, bool $isRightAnswer): array
    {
        // Устанавливаем значения
        $this->questionId = $questionId;
        $this->answer = $answer;
        $this->isText = $isText;
        $this->isRightAnswer = $isRightAnswer;

        // Создаем новый вопрос
        $question = new Question();

        // Получаем данные вопроса по его ID
        $question->getQuestionByQuestionIdWSD($questionId);

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $question->testId);

        // Создаем ответ
        $this->executeWithSetObjectData(AnswerSql::$query_create_answer, array(
            'questionId' => $this->questionId,
            'answer' => $this->answer,
            'isText' => $this->isText ? 1 : 0,
            'isRightAnswer' => $this->isRightAnswer ? 1 : 0
        ), Message::$messages['DBErrorExecute'], 400);

        // Если вопрос не является текстовым, то добавляем файл на диск
        if (!$this->isText) {

            // Загружаем файл на диск и получаем public url
            $path = $this->loadFile($userId, $test);
            $this->answer = $path;
            $publicUrl = $this->getPublicUrl();
            // Изменяем вопрос в БД на публичную ссылку
            $this->executeWithSetObjectData(AnswerSql::$query_set_public_url_answer, array(
                'answer' => $path,
                'answerId' => $this->answerId
            ), Message::$messages['DBErrorExecute'], 400);
        }

        return array(
            'answerId' => $this->answerId,
            'questionId' => $this->questionId,
            'answer' => $publicUrl,
            'isText' => $this->isText,
            'isRightAnswer' => $this->isRightAnswer
        );
    }

    public function update(int $userId, int $answerId, string $answer, bool $isText, bool $isRightAnswer): array
    {
        // Устанавливаем значения
        $this->answerId = $answerId;

        // Получаем вопрос по его ID
        $this->getAnswerByAnswerIdWSD();

        // Создаем новый вопрос
        $question = new Question();

        // Получаем данные вопроса по его ID
        $question->getQuestionByQuestionIdWSD($this->questionId);

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $question->testId);

        // Проверяем, был ли ответ текстом
        if ($this->isText) {
            // Если ответ был текстовым
            $this->updateAnswer($userId, $test, $answer, $isText, $isRightAnswer);
        } else {
            // Если ответ был картинкой, удаляем картинку из диска
            $this->deleteAnswerFromDisk($userId, $test);
            $this->updateAnswer($userId, $test, $answer, $isText, $isRightAnswer);
        }

        // Проверяем, ответ текстовый или нет
        $publicUrl = $this->isText ? $this->answer : $this->getPublicUrl();

        return array(
            'answerId' => $this->answerId,
            'questionId' => $this->questionId,
            'answer' => $publicUrl,
            'isText' => $this->isText,
            'isRightAnswer' => $this->isRightAnswer
        );

    }

    public function delete(int $userId, int $answerId): void
    {
        // Устанавливаем значения
        $this->answerId = $answerId;

        // Получаем вопрос по его ID
        $this->getAnswerByAnswerIdWSD();

        // Создаем новый вопрос
        $question = new Question();

        // Получаем данные вопроса по его ID
        $question->getQuestionByQuestionIdWSD($this->questionId);

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $question->testId);

        // Выполняем удаление вопроса
        $this->db->query(AnswerSql::$query_delete_answer, array(
            'answerId' => $this->answerId
        ));

        // Удаляем папку с вопросом
        $this->deleteAnswerFromDisk($userId, $test);
    }

    // Метод по удалению картинки из диска
    private function deleteAnswerFromDisk(int $userId, Test $test): void
    {
        $path = sprintf('/educational quest/User%d/Group%d/Test%d/Question%d/Answer%d', $userId,
            $test->groupId, $test->testId, $this->questionId, $this->answerId);
        YandexDisk::delete($path);
    }

    // Получение ответа по его ID
    private function getAnswerByAnswerIdWSD(): void
    {
        $this->executeWithSetObjectData(AnswerSql::$query_get_answer_by_answer_id, array(
            'answerId' => $this->answerId
        ), Message::$messages['AnswerNotFound'], 400);
    }

    // Обновление ответа в БД
    private function updateAnswer(int $userId, Test $test, string $answer, bool $isText, bool $isRightAnswer): void
    {
        if ($isText) {
            // Если вопрос текстовый, то просто меняем вопрос в БД
            $this->executeWithSetObjectData(AnswerSql::$query_update_answer, array(
                'answer' => $answer,
                'isText' => 1,
                'isRightAnswer' => $isRightAnswer ? 1 : 0,
                'answerId' => $this->answerId
            ), Message::$messages['DBErrorExecute'], 500);
        } else {
            // Если вопрос в виде картинки, то загружаем картинку на диск и получаем ссылку на нее
            $path = $this->loadFile($userId, $test);
            // Изменяем вопрос в БД
            $this->executeWithSetObjectData(AnswerSql::$query_update_answer, array(
                'answer' => $path,
                'isText' => 0,
                'isRightAnswer' => $isRightAnswer ? 1 : 0,
                'answerId' => $this->answerId
            ), Message::$messages['DBErrorExecute'], 500);
        }
    }

    // Получение публичной ссылки на ответ
    private function getPublicUrl(): string
    {
        return YandexDisk::setPublish($this->answer, 'Answer' . $this->answerId);
    }

    // Загрузка файла на диск
    private function loadFile(int $userId, Test $test): string
    {
        // Потом добавляем картинку в Яндекс диск
        // Задаем путь до файла
        $path = sprintf('/educational quest/User%d/Group%d/Test%d/Question%d/',
            $userId, $test->groupId, $test->testId, $this->questionId);
        // Получаем сам файл из временной папки
        $file = $_FILES['answer']['tmp_name'];
        // Загружаем файл на диск
        YandexDisk::loadFile($path, $file, 'Answer' . $this->answerId);
        return $path;
    }

    // Метод по выполнению запроса в базу данных с последующим заполнение объекта
    private function executeWithSetObjectData(string $query, array $arrayData, int $error, int $httpCode): void
    {
        // выполняем запрос
        $result = $this->db->query($query, $arrayData);

        // получаем количество строк 
        $num = $result->rowCount();

        // если запрос прошел успешно
        if ($num > 0) {
            // получаем значения 
            $row = $result->fetch(PDO::FETCH_ASSOC);

            // заполняем объект данными из БД
            $this->setAnswerByRow($row);
        } else {
            Response::sendError($error, $httpCode);
        }
    }

    // Установка вопроса по данным из БД
    private function setAnswerByRow($row): void
    {
        // присвоим значения свойствам объекта
        $this->answerId = $row['answer_id'];
        $this->questionId = $row['question_id'];
        $this->answer = $row['answer'];
        $this->isText = $row['is_text'];
        $this->isRightAnswer = $row['is_right_answer'];
    }
}
