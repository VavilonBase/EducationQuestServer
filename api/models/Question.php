<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\core\YandexDisk;
use api\modelsSql\QuestionSql;
use PDO;

class Question extends Model
{
    // свойства объекта 
    public int $questionId;
    public int $testId;
    public string $question;
    public bool $isText;
    public int $scores;

    // Создание вопроса
    public function create(int $userId, int $testId, string $question, bool $isText, int $scores): array
    {
        // Установка значений
        $this->testId = $testId;
        $this->question = $question;
        $this->isText = $isText;
        $this->scores = $scores;

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $testId);

        // Создаем вопрос
        $this->executeWithSetObjectData(QuestionSql::$query_create_question, array(
            'testId' => $this->testId,
            'question' => $this->question,
            'isText' => $this->isText ? 1 : 0,
            'scores' => $this->scores
        ), Message::$messages['DBErrorExecute'], 400);

        // Создаем папку с вопросом
        $this->createQuestionFolder($userId, $test);

        // Если вопрос не является текстовым, то добавляем файл на диск
        if (!$this->isText) {

            // Загружаем файл на диск и получаем public url
            $path = $this->loadFile($userId, $test);
            // Получаем на него публичную ссылку
            $publicUrl = $this->getPublicUrl($path);
            // Изменяем вопрос в БД на публичную ссылку
            $this->executeWithSetObjectData(QuestionSql::$query_set_public_url_question, array(
                'question' => $path,
                'questionId' => $this->questionId
            ), Message::$messages['DBErrorExecute'], 400);
        }

        return array(
            'questionId' => $this->questionId,
            'testId' => $this->testId,
            'question' => $publicUrl,
            'isText' => $this->isText,
            'scores' => $this->scores
        );
    }

    // Обновление вопроса
    public function update(int $userId, int $questionId, string $question, bool $isText, int $scores): array
    {

        // Устанавливаем данные
        $this->questionId = $questionId;

        // Получаем вопрос по его ID
        $this->getQuestionByQuestionIdWSD();

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $this->testId);

        // Проверяем, был ли вопрос текстом
        if (!$this->isText) {
            // Если вопрос был картинкой, удаляем картинку из диска
            $this->deleteQuestionFromDisk($userId, $test);
        }
        $this->updateQuestion($userId, $test, $question, $isText, $scores);
        $publicUrl = $this->getPublicUrl($this->question);
        return array(
            'questionId' => $this->questionId,
            'testId' => $this->testId,
            'question' => $publicUrl,
            'isText' => $this->isText,
            'scores' => $this->scores
        );
    }

    // Получение вопроса с ответами
    public function getQuestionWithAnswers(int $userId, int $questionId = null): array
    {
        // Устанавливаем данные
        $this->questionId = $questionId;

        // Получаем вопрос по его ID
        $this->getQuestionByQuestionIdWSD();

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $this->testId);

        // Выполняем запрос
        $result = $this->db->query(QuestionSql::$query_get_question_with_answers, array(
            'questionId' => $this->questionId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если строка меньше или равно нулю, то у вопроса нет ответов
        if ($num <= 0) Response::sendError(Message::$messages['QuestionHasNotAnswers'], 400);

        // Если ответы есть, то помещаем ответ от БД в массив
        $res = $result->fetchAll(PDO::FETCH_ASSOC);
        $publicUrl = $this->isText ? $this->question : $this->getPublicUrl($this->question);
        $response = array(
            'questionId' => $this->questionId,
            'testId' => $this->testId,
            'question' => $publicUrl,
            'isText' => $this->isText,
            'scores' => $this->scores,
            'answers' => array()
        );

        // Проверяем, есть ли ответы
        if ($res[0]['answerid'] != null) {
            foreach ($res as $row) {
                $publicUrlAnswer = $row['istext'] ? $row['answer'] : $this->getPublicUrl($row['answer']);
                $response['answers'][] = array(
                    'answerId' => $row['answerid'],
                    'answer' => $publicUrlAnswer,
                    'isText' => $row['istext'],
                    'isRightAnswer' => $row['isrightanswer']
                );
            }
        }

        return $response;
    }

    public function delete(int $userId, int $questionId): void
    {
        // Устанавливаем данные
        $this->questionId = $questionId;

        // Получаем вопрос по его ID
        $this->getQuestionByQuestionIdWSD();

        // Создаем новый тест
        $test = new Test();

        // Проверяем, что тест существует, и что пользователь является его создателем и получаем его
        $test->getTestByTestIdForCreatorWithSetData($userId, $this->testId);

        // Выполняем удаление вопроса
        $this->db->query(QuestionSql::$query_delete_question, array(
            'questionId' => $questionId
        ));

        // Удаляем папку с вопросом
        $this->deleteQuestionFolder($userId, $test);
    }

    // Получение вопроса по его ID
    public function getQuestionByQuestionIdWSD(int $questionId = null): void
    {
        // Устанавливаем значения
        if ($questionId !== null) {
            $this->questionId = $questionId;
        }

        $this->executeWithSetObjectData(QuestionSql::$query_get_question_by_question_id, array(
            'questionId' => $this->questionId
        ), Message::$messages['NotFoundQuestion'], 400);
    }

    // Получение публичной ссылки на вопрос
    private function getPublicUrl(string $path): string
    {
        return YandexDisk::setPublish($path, 'Question');
    }

    // Обновление вопроса в БД
    private function updateQuestion(int $userId, Test $test, string $question, bool $isText, int $scores)
    {
        $this->executeWithSetObjectData(QuestionSql::$query_update_question, array(
            'question' => $question,
            'isText' => $isText ? 1 : 0,
            'questionId' => $this->questionId,
            'scores' => $scores
        ), Message::$messages['DBErrorExecute'], 500);

        if (!$isText) {
            // Если вопрос в виде картинки, то загружаем картинку на диск и получаем ссылку на нее
            $path = $this->loadFile($userId, $test);
            // Изменяем вопрос в БД
            $this->executeWithSetObjectData(QuestionSql::$query_set_public_url_question, array(
                'question' => $path,
                'questionId' => $this->questionId
            ), Message::$messages['DBErrorExecute'], 500);
        }
    }


    // Загрузка файла на диск
    private function loadFile(int $userId, Test $test): string
    {
        // Задаем путь до файла
        $path = sprintf('/educational quest/User%d/Group%d/Test%d/Question%d/', $userId, $test->groupId, $test->testId, $this->questionId);
        // Получаем сам файл из временной папки
        $file = $_FILES['question']['tmp_name'];
        // Загружаем файл на диск
        YandexDisk::loadFile($path, $file, 'Question');
        // Делаем его публичным и получаем на него публичную ссылку
        return $path;
    }

    // Метод по удалению картинки из диска
    private function deleteQuestionFromDisk(int $userId, Test $test): void
    {
        $path = sprintf(
            '/educational quest/User%d/Group%d/Test%d/Question%d/Question',
            $userId,
            $test->groupId,
            $test->testId,
            $this->questionId
        );
        YandexDisk::delete($path);
    }

    // Метод по удалению папки с вопросом
    private function deleteQuestionFolder(int $userId, Test $test): void
    {
        $path = sprintf(
            '/educational quest/User%d/Group%d/Test%d/Question%d/',
            $userId,
            $test->groupId,
            $test->testId,
            $this->questionId
        );
        YandexDisk::delete($path);
    }

    // Метод по создание папки с вопросом
    private function createQuestionFolder(int $userId, Test $test): void
    {
        // Задаем путь до файла
        $path = sprintf('/educational quest/User%d/Group%d/Test%d/Question%d', $userId, $test->groupId, $test->testId, $this->questionId);

        // Создаем папку с вопросом
        YandexDisk::createFolder($path);
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

            // заполняем объект данныеми из БД
            $this->setQuestionByRow($row);
        } else {
            Response::sendError($error, $httpCode);
        }
    }

    // Установка вопроса по данным из БД
    private function setQuestionByRow($row)
    {
        // присвоим значения свойствам объекта 
        $this->questionId = $row['question_id'];
        $this->testId = $row['test_id'];
        $this->question = $row['question'];
        $this->isText = $row['is_text'];
        $this->scores = $row['scores'];
    }
}
