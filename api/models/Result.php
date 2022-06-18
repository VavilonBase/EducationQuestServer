<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\modelsSql\ResultSql;
use PDO;

class Result extends Model
{
    // свойства объекта
    public int $resultId;
    public int $userId;
    public int $testId;
    public int $totalScores;

    // Создание результата
    public function create(int $userId, int $testId, array $answers): array
    {
        // Установка значений
        $this->userId = $userId;
        $this->testId = $testId;

        // Проверяем, что ученик имеет право проходить этот тест
        $this->checkAccessToCompleteTest();

        // Создаем результат в БД
        $this->executeWithSetObjectData(ResultSql::$query_create_result, array(
            'userId' => $this->userId,
            'testId' => $this->testId
        ), Message::$messages['DBErrorExecute'], 500);

        // Если ученик имеет право проходить тест, то начинаем подсчет результатов
        // Получаем все вопросы этого теста с ответами
        $right_answers = $this->getAllRightAnswersForTest();

        // Считаем количество баллов за тест
        $scores = $this->calculateScoresForTest($answers, $right_answers);

        // Добавляем количество очков в БД
        $this->executeWithSetObjectData(ResultSql::$query_add_total_score, array(
            'totalScores' => $scores,
            'resultId' => $this->resultId
        ), Message::$messages['DBErrorExecute'], 500);

        return array(
            'resultId' => $this->resultId,
            'userId' => $this->userId,
            'testId' => $this->testId,
            'totalScores' => $this->totalScores
        );
    }

    // Получение результата по id
    public function getResultByResultId(int $resultId): void
    {
        $this->executeWithSetObjectData(ResultSql::$query_get_result_by_result_id,
            array(
                "resultId" => $resultId
            ),
            Message::$messages["NotFoundResult"], 400
        );
    }

    // Проверка доступа к тесту для ученика
    private function checkAccessToCompleteTest(): void
    {
        // Выполняем запрос на поиск пользователя
        $result = $this->db->query(ResultSql::$query_check_access, array(
            'userId' => $this->userId,
            'testId' => $this->testId
        ));

        // Получаем количество возвращаемых строк
        $num = $result->rowCount();

        // Если больше нуля, то пользователь имеет доступ, иначе нет
        if ($num <= 0) Response::sendError(Message::$messages['AccessDenied'], 403);
    }

    // Получение всех правильных ответов на вопросы по тесту
    private function getAllRightAnswersForTest(): array
    {
        // Получаем все правильные ответы
        $result = $this->db->query(ResultSql::$query_get_all_right_answers_for_test, array(
            'testId' => $this->testId
        ));

        // Получаем количество возвращаемых строк
        $num = $result->rowCount();

        // Если меньше нуля, то тест не имеет вопросов
        if ($num <= 0) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);

        // Если ответы есть, возвращаем их
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    // Считаем количество баллов за тест
    private function calculateScoresForTest(array $answers, array $right_answers): int
    {
        $scores = 0;
        // Проходимся по всем ответам пользователя
        foreach ($answers as $answer) {
            // Ищем правильный ответ на текущий вопрос
            foreach ($right_answers as $right_answer) {
                // Если нашли текущий вопрос
                if ($right_answer['question_id'] == $answer->questionId) {
                    // Смотрим правильно ли ответил пользователь
                    if ($right_answer['answer_id'] == $answer->answerId) {
                        // Подсчитываем очки
                        $scores += $right_answer['scores'];
                    }
                    // Создаем результат в БД
                    $this->createResultQuestion($answer->questionId, $answer->answerId);
                    break;
                }
            }
        }
        return $scores;
    }

    // Заносим результат ответа на вопрос в БД
    private function createResultQuestion($questionId, $answerId)
    {
        // Создаем результат ответ на вопрос
        $this->db->query(ResultSql::$query_create_result_question, array(
            'resultId' => $this->resultId,
            'questionId' => $questionId,
            'answerId' => $answerId
        ));
    }

    // Метод по выполнению запроса в базу данных с последующим заполнение объекта
    private function executeWithSetObjectData(string $query, array $arrayData, int $error, int $httpCode)
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
            $this->setResultByRow($row);
        } else {
            Response::sendError($error, $httpCode);
        }
    }

    // Установка вопроса по данным из БД
    private function setResultByRow($row)
    {
        // присвоим значения свойствам объекта
        $this->resultId = $row['result_id'];
        $this->userId = $row['user_id'];
        $this->testId = $row['test_id'];
        $this->totalScores = $row['total_scores'];
    }
}
