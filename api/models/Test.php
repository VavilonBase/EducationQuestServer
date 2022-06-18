<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\core\YandexDisk;
use api\modelsSql\TestSql;
use PDO;

class Test extends Model
{
    // свойства объекта 
    public int $testId;
    public string $title;
    public int $groupId;
    public bool $isOpened;
    public bool $canViewResults;

    // Создание теста
    public function create(int $userId, int $groupId, string $title, bool $canViewResults): array
    {
        // Устанавливаем значения
        $this->title = $title;
        $this->groupId = $groupId;
        $this->canViewResults = $canViewResults;

        // Создаем новую группу
        $group = new Group();
        // Проверяем, что пользователь является создателем группы
        $group->checkAccessToGroupByGroupId($userId, $this->groupId);

        // Создаем тест
        $this->executeWithSetObjectData(TestSql::$query_create_test, array(
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => 0,
            'canViewResults' => $this->canViewResults ? 1 : 0,
        ), Message::$messages['DBErrorExecute'], 500);

        // Создаем папку на диске
        $path = sprintf('/educational quest/User%d/Group%d/Test%d', $userId, $this->groupId, $this->testId);
        YandexDisk::createFolder($path);

        return array(
            'testId' => $this->testId,
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => $this->isOpened,
            'canViewResults' => $this->canViewResults
        );
    }

    // Обновление теста
    public function update(int $testId, int $userId, string $title, bool $canViewResults): array
    {
        // Устанавливаем значения
        $this->testId = $testId;
        $this->title = $title;
        $this->canViewResults = $canViewResults;

        // Проверка на доступ к тесту
        $this->checkAccessToTest($userId);

        // Обновляем тест
        $this->executeWithSetObjectData(TestSql::$query_update_test, array(
            'title' => $this->title,
            'canViewResults' => $this->canViewResults ? 1 : 0,
            'testId' => $this->testId
        ), Message::$messages['DBErrorExecute'], 500);

        return array(
            'testId' => $this->testId,
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => $this->isOpened,
            'canViewResults' => $this->canViewResults
        );
    }

    // Удаление теста
    public function delete(int $testId, int $userId): bool
    {
        // Устанавливаем значения
        $this->testId = $testId;

        // Проверка на доступ к тесту и существование теста, а также его получение, в случае доступности
        $this->getTestByTestIdForCreatorWithSetData($userId, $testId);

        // Выполняем удаление
        $this->db->query(TestSql::$query_delete_test, array(
            'testId' => $this->testId
        ));

        // Удаляем папку теста
        $path = sprintf('/educational quest/User%d/Group%d/Test%d/', $userId, $this->groupId, $this->testId);
        YandexDisk::delete($path);

        return true;
    }

    // Получение всех тестов группы
    public function getAllGroupTests(int $groupId): array
    {
        // Устанавливаем значения
        $this->groupId = $groupId;

        // Получаем все тесты группы
        $rows = $this->db->row(TestSql::$query_get_all_group_tests, array(
            'groupId' => $this->groupId
        ));

        // Проверяем, что тесты есть в группе
        if (count($rows) > 0) {
            return $rows;
        } else {
            Response::sendError(Message::$messages['GroupHasNotTests'], 400);
            return array();
        }
    }

    // Получение максимального числа очков за тест
    public function getMaxScoresForTestByTestId(int $testId): int
    {
        // Устанавливаем значения
        $this->testId = $testId;

        // Получаем количество очков
        $rows = $this->db->row(TestSql::$query_get_max_scores_for_test_by_test_id, array(
            'testId' => $this->testId
        ));

        // Проверяем, что тест существует
        if (count($rows) > 0) {
            return $rows[0]['maxscores'];
        } else {
            Response::sendError(Message::$messages['TestNotExist'], 400);
            return 0;
        }
    }

    // Открытие теста
    public function openTest(int $testId, int $userId): array
    {
        // Устанавливаем значения
        $this->testId = $testId;

        // Проверка на доступ к тесту и существование теста
        $this->checkAccessToTest($userId);

        // Проверка на то, есть ли вопросы
        $this->checkTestExistsQuestions();

        // Если тест не был закрыт, то открываем его
        $this->executeWithSetObjectData(TestSql::$query_open_test, array(
            'testId' => $this->testId
        ), Message::$messages['DBErrorExecute'], 500);

        return array(
            'testId' => $this->testId,
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => $this->isOpened,
            'canViewResults' => $this->canViewResults
        );
    }

    // Закрытие теста
    public function closeTest(int $testId, int $userId): array
    {
        // Устанавливаем значения
        $this->testId = $testId;

        // Проверка на доступ к тесту и существование теста, а также его получение, в случае доступности
        $this->getTestByTestIdForCreatorWithSetData($userId, $testId);

        // Проверяем, что тест открыт
        if (!$this->isOpened) Response::sendError(Message::$messages['TestIsNotOpened'], 400);

        // Если тест открыт, то закрываем его
        $this->executeWithSetObjectData(TestSql::$query_close_test, array(
            'testId' => $this->testId
        ), Message::$messages['DBErrorExecute'], 500);

        return array(
            'testId' => $this->testId,
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => $this->isOpened,
            'canViewResults' => $this->canViewResults
        );
    }

    // Проверка на доступ к тесту по его Id
    public function checkAccessToTest(int $userId, int $testId = null)
    {
        // Если значения переданы, то указываем их
        if ($testId !== null) {
            $this->testId = $testId;
        }

        // Получаем ID пользователя по ID теста
        $result = $this->db->query(TestSql::$query_get_user_id_by_test_id, array(
            'testId' => $this->testId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если теста нет
        if ($num <= 0) Response::sendError(Message::$messages['TestNotExist'], 400);

        // Получаем данные из запроса
        $res = $result->fetch(PDO::FETCH_ASSOC);

        // Если пользователь не является создателем теста
        if ($userId != $res['user_id']) Response::sendError(Message::$messages['UserIsNotCreatorTest'], 400);
    }

    // Получение теста по его ID
    public function getTestByTestId(int $testId = null): array
    {
        // Устанавливаем значения
        if ($testId !== null) {
            $this->testId = $testId;
        }

        // Получаем тест
        $this->executeWithSetObjectData(TestSql::$query_get_test_by_id, array(
            'testId' => $this->testId
        ), Message::$messages['TestNotExist'], 400);

        return array(
            'testId' => $this->testId,
            'groupId' => $this->groupId,
            'title' => $this->title,
            'isOpened' => $this->isOpened,
            'canViewResults' => $this->canViewResults
        );
    }

    // Получение теста с вопросами для учителя и ученика
    public function getTestByTestIdWithQuestions(int $userId, int $testId, string $role): array
    {
        // Установка значений
        $this->testId = $testId;

        if ($role === "TEACHER") return $this->getTestWithQuestionForTeacher($userId);
        if ($role === "STUDENT") return $this->getTestWithQuestionForStudent($userId);

        // Если роль какая-то другая, то выдаем ошибку
        Response::sendError(Message::$messages['AccessDenied'], 403);
        return array();
    }

    // Получение результатов теста для ученика
    public function getStudentTestResult(int $userId, int $studentId, int $testId): array
    {
        // Установка значений
        $this->testId = $testId;

        // Проверяем, что пользователь и ученик один и тот же человек
        if ($userId != $studentId) Response::sendError(Message::$messages['AccessDenied'], 403);

        // Если ученик не имеет право посмотреть результаты теста, то возвращаем ему только количество набранных очков
        $result = $this->db->query(TestSql::$query_get_student_result, array(
            'userId' => $userId,
            'testId' => $testId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если нет строк, то пользователь не проходил тест
        if ($num <= 0) Response::sendError(Message::$messages['NotFoundResult'], 400);

        // Преобразуем результат в массив и возвращаем его
        return $result->fetchAll(PDO::FETCH_ASSOC);
    }

    // Получение результатов теста для учителя и ученика с правильными ответами
    public function getStudentTestResultWithRightAnswer(int $userId, string $role, int $resultId): array
    {
        // Создаем результат
        $result = new Result();

        // Получаем данные результата
        $result->getResultByResultId($resultId);

        // Установка значений
        $this->testId = $result->testId;

        if ($role === "TEACHER") {
            // Проверяем, что учитель имеет доступ к тесту
            $this->checkAccessToTest($userId);
            return $this->getStudentFullTestResult($result->userId, $this->testId, $resultId);
        }
        if ($role === "STUDENT") {
            // Проверяем, что студент имеет право посмотреть результаты теста
            $this->checkViewResultTest($userId, $result->userId);
            return $this->getStudentFullTestResult($result->userId, $this->testId, $resultId);
        }

        // Если роль какая-то другая, то выдаем ошибку
        Response::sendError(Message::$messages['AccessDenied'], 403);
        return array();
    }

    // Получение учителем результаты всех учеников за тест
    public function getAllResultsTestForStudents(int $userId, int $testId): array
    {
        // Устанавливаем значения
        $this->testId = $testId;

        // Проверяем, что учитель имеет доступ к тесту
        $this->checkAccessToTest($userId);

        // Получаем результаты
        $result = $this->db->query(TestSql::$query_get_all_results_for_test, array(
            'testId' => $testId
        ));

        // Получаем количество вопросов
        $num = $result->rowCount();

        // Если нет вопросов, то выдаем ошибку
        if ($num <= 0) Response::sendError(Message::$messages['NotFoundResult'], 400);

        // Если все найдено, то получаем результаты в виде массива
        $results = $result->fetchAll(PDO::FETCH_ASSOC);

        // Результаты пользователе
        $usersResults = array();

        // Предыдущий id пользователя делаем первый элемент
        $prevUserId = $results[0]['userid'];
        // Текущим пользователем делаем первый элемент
        $currentUser = array(
            'userId' => $results[0]['userid'],
            'lastName' => $results[0]['lastname'],
            'firstName' => $results[0]['firstname'],
            'middleName' => $results[0]['middlename'],
            'results' => array()
        );

        // Проходимся по каждой строке
        // Так как пользователи упорядочены по id, то можно запоминать предыдущего пользователя, и если id совпадает
        // с новым, то добавлять результаты к нему же
        foreach ($results as $row) {
            // Запоминаем текущий id пользователя
            $currentUserId = $row['userid'];
            // Проверяем, совпадает ли текущим id пользователя с предыдущим, для первого элемента они совпадают
            if ($prevUserId == $currentUserId) {
                // Если пользователи одинаковы, то добавляем к пользователю результат
                // Создаем данные, которые будут храниться в results
                $data = array(
                    'resultId' => $row['resultid'],
                    'totalScores' => $row['totalscores']
                );
                // Добавляем ответ к текущему пользователю
                $currentUser['results'][] = $data;
            } else {
                // Если вопросы разные, то значит, добавляем прошлый вопрос с данными в массив test и создаем новый вопрос
                $usersResults[] = $currentUser;
                // Назначаем предыдущему id текущего вопроса
                $prevUserId = $currentUserId;

                // Создаем данные, которые будут храниться в results
                $data = array(
                    'resultId' => $row['resultid'],
                    'totalScores' => $row['totalscores']
                );

                // Создаем данные, которые будут храниться в массиве Users
                $currentUser = array(
                    'userId' => $results[0]['userid'],
                    'lastName' => $results[0]['lastname'],
                    'firstName' => $results[0]['firstname'],
                    'middleName' => $results[0]['middlename'],
                    'results' => array($data)
                );

            }
        }

        // Последний вопрос не будет добавлен, поэтому добавляем здесь
        $usersResults[] = $currentUser;
        return $usersResults;
    }

    // Получение данных о тесте для создателя с проверкой, что он имеет доступ
    public function getTestByTestIdForCreatorWithSetData(int $userId, int $testId): void
    {
        // Устанавливаем значения
        if ($testId !== null) $this->testId = $testId;

        // Получаем тест
        $result = $this->db->query(TestSql::$query_get_test_by_test_id_for_creator,
            array(
                'testId' => $this->testId
            ));

        // Получаем количество вернувшихся строк
        $num = $result->rowCount();

        // Если нет данных, то тест не существует
        if ($num <= 0) Response::sendError(Message::$messages['TestNotExist'], 400);

        // Если тест существует, то проверяем на то, что пользователь является создателем теста
        $row = $result->fetch(PDO::FETCH_ASSOC);
        if ($userId != $row['user_id']) Response::sendError(Message::$messages['UserIsNotCreatorTest'], 400);

        // Если пользователь создатель теста, то устанавливаем значения
        $this->setTestByRow($row);
    }

    // Проверка на наличие в тесте вопросов
    private function checkTestExistsQuestions(): void
    {
        // Получаем тест
        $result = $this->db->query(TestSql::$query_check_test_exists_questions,
            array(
                'testId' => $this->testId
            ));

        // Получаем количество вернувшихся строк
        $num = $result->rowCount();

        // Если нет данных, то тест не существует
        if ($num <= 0) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);
    }

    // Получение полного результата студента
    private function getStudentFullTestResult(int $studentId, int $testId, int $resultId): array
    {
        // Получаем результаты пользователя
        $result = $this->db->query(TestSql::$query_get_full_student_result, array(
            'resultId' => $resultId
        ));

        // Получаем количество результатов пользователя
        $num = $result->rowCount();

        // Если нет результатов, то выводим ошибку
        if ($num <= 0) Response::sendError(Message::$messages['NotFoundResult'], 400);

        // Получаем правильные ответы на вопросы
        $rightQuestionsResult = $this->db->query(TestSql::$query_get_right_answers_for_test, array(
            'testId' => $testId
        ));

        // Получаем количество вопросов
        $rightQuestionsResultNum = $rightQuestionsResult->rowCount();

        // Если нет вопросов, то выдаем ошибку
        if ($rightQuestionsResultNum <= 0) Response::sendError(Message::$messages['NotFoundQuestion'], 400);

        // Если все найдено, то получаем результаты в виде массива
        $rightQuestions = $rightQuestionsResult->fetchAll(PDO::FETCH_ASSOC);
        $results = $result->fetchAll(PDO::FETCH_ASSOC);

        // Правильные ответы, сгруппированные по вопросам
        $rightAnswers = array();
        // Группируем правильные ответы по id вопроса, для удобного доступа к ним
        foreach ($rightQuestions as $rightQuestion) {
            $publicUrlRightAnswer = $rightQuestion['istext'] ? $rightQuestion['answer'] :
                $this->getPublicUrlAnswer($rightQuestion['answer'], $rightQuestion['answerid']);
            $rightAnswers[$rightQuestion['questionid']] = array(
                'answerId' => $rightQuestion['answerid'],
                'answer' => $publicUrlRightAnswer,
                'isText' => $rightQuestion['istext'],
            );
        }

        // Выходные результаты
        $outputResult = array(
            'userId' => $studentId,
            'testId' => $testId,
            'resultId' => $results[0]['resultid'],
            'totalScores' => $results[0]['totalscores'],
            'questions' => array()
        );

        // Проходимся по каждой строке с вопросами и ответами
        foreach ($results as $row) {

            // Получаем правильный ответ на вопрос
            $rightAnswer = $rightAnswers[$row['questionid']];
            $publicUrlQuestion = $row['isquestiontext'] ? $row['question'] : $this->getPublicUrlQuestion($row['question']);
            $publicUrlAnswer = $row['isanswertext'] ? $row['answer'] : $this->getPublicUrlQuestion($row['answer']);
            // Получаем вопрос и ответ на него (с правильным ответом)
            $question = array(
                'questionId' => $row['questionid'],
                'question' => $publicUrlQuestion,
                'isQuestionText' => $row['isquestiontext'],
                'answerId' => $row['answerid'],
                'answer' => $publicUrlAnswer,
                'isAnswerText' => $row['isanswertext'],
                'rightAnswerId' => $rightAnswer['answerId'],
                'rightAnswer' => $rightAnswer['answer'],
                'isRightAnswerText' => $rightAnswer['isText']
            );

            // Добавляем результат в выходной массив
            $outputResult['questions'][] = $question;
        }

        return $outputResult;
    }

    // Проверяем, что ученик имеет доступ к этому тесту и можно посмотреть его результаты
    private function checkViewResultTest(int $userId, int $studentId)
    {
        // Проверяем, что пользователь и ученик один и тот же человек
        if ($userId != $studentId) Response::sendError(Message::$messages['AccessDenied'], 403);

        // Выполняем проверку
        $result = $this->db->query(TestSql::$query_check_view_results, array(
            'testId' => $this->testId,
            'userId' => $userId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если количество строк меньше или равно нулю, то у пользователя нет доступа к просмотру результатов
        if ($num <= 0) Response::sendError(Message::$messages['AccessDenied'], 403);
    }

    // Получение теста с вопросами для учителя
    private function getTestWithQuestionForTeacher(int $userId): array
    {
        // Проверяем, что учитель имеет доступ к тесту
        $this->checkAccessToTest($userId);

        // Если доступ есть, получаем тест с вопросами
        $result = $this->db->query(TestSql::$query_get_test_with_questions_for_teacher, array(
            'testId' => $this->testId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если количество строк меньше или равно нулю, то у теста нет вопросов
        if ($num <= 0) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);

        // Если строки есть, то получаем их в виде массива
        $res = $result->fetchAll(PDO::FETCH_ASSOC);

        // Упаковываем вопросы в удобный вид
        return $this->packingTestWithQuestion($res);

    }

    // Получение теста с вопросами для ученика
    private function getTestWithQuestionForStudent(int $userId): array
    {
        // Проверяем, что ученик имеет доступ к тесту
        $this->checkStudentAccessToTest($userId);

        // Если доступ есть, получаем тест с вопросами
        $result = $this->db->query(TestSql::$query_get_test_with_questions_for_student, array(
            'testId' => $this->testId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если количество строк меньше или равно нулю, то у теста нет вопросов
        if ($num <= 0) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);

        // Если строки есть, то получаем их в виде массива
        $res = $result->fetchAll(PDO::FETCH_ASSOC);

        return $this->packingTestWithQuestion($res);
    }

    // Проверка на то, что ученик имеет доступ к этому тесту
    private function checkStudentAccessToTest(int $userId)
    {
        // Выполняем запрос на проверку доступа пользователя к тесту
        $result = $this->db->query(TestSql::$query_check_access_student_to_the_test, array(
            'testId' => $this->testId,
            'userId' => $userId
        ));

        // Получаем количество строк
        $num = $result->rowCount();

        // Если количество строк меньше или равно нулю, то доступа нет
        if ($num <= 0) Response::sendError(Message::$messages['AccessDenied'], 403);
    }

    // Упаковываем данные с вопросами по тестам в более удобный вид
    /*Входной массив (должен быть упорядочен по question_id):
        [
            {
                "questionid": 1,
                "question" : "question",
                "scores": 5,
                "answerid": 2,
                "answer": "answer",
                "istextanswer: true,
                "isrightanswer: true
            },
            {
                "questionid": 3,
                "question" : "question",
                "scores": 5,
                "answerid": 4,
                "answer": "answer",
                "istextanswer: true,
                "isrightanswer: true
            },
        ]
    */
    /*  Выходной массив выглядит следующим образом{
            "testId": 29,
            "questions": [
                {
                    "questionId": 52,
                    "isText": false,
                    "scores": 0,
                    "answers": [
                        {
                        "answerId": null,
                        "isText": null,
                        "isRightAnswer": null
                        }
                    ]
                },
                {
                    "questionId": 53,
                    "isText": false,
                    "scores": 0,
                    "answers": [
                        {
                            "answerId": null,
                            "isText": null,
                            "isRightAnswer": null
                        }
                    ]
                },
                {
                    "questionId": 54,
                    "isText": false,
                    "scores": 5,
                    "answers": [
                        {
                            "answerId": null,
                            "isText": null,
                            "isRightAnswer": null
                        }
                    ]
                }
            ]
        }*/
    private function packingTestWithQuestion(array $res): array
    {
        // Выходной массив
        $test = array();
        $test['testId'] = $this->testId;
        $test['questions'] = array();

        // Проверяем, что вопросы есть
        if (count($res) <= 0) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);

        // Предыдущий id вопроса делаем первый элемент
        $prevQuestionId = $res[0]['questionid'];
        $publicUrl = $res[0]['istextquestion'] ? $res[0]['question'] : $this->getPublicUrlQuestion($res[0]['question']);
        // Текущим вопросом делаем первый элемент
        $currentQuestion = array(
            'questionId' => $res[0]['questionid'],
            'isText' => $res[0]['istextquestion'],
            'question' => $publicUrl,
            'scores' => $res[0]['scores'],
            'answers' => array()
        );

        // Проходимся по каждой строке
        // Так как вопросы упорядочены по id вопроса, то можно запоминать предыдущий вопрос, и если id совпадает
        // с новым, то добавлять ответы к нему же
        foreach ($res as $row) {
            // Если id вопроса null, то вопросов нет
            if ($row['questionid'] == null) Response::sendError(Message::$messages['TestHasNotQuestions'], 400);

            // Запоминаем текущий id вопроса
            $currentQuestionId = $row['questionid'];
            // Проверяем, совпадает ли текущим id вопроса с предыдущим, для первого элемента они совпадают
            if ($prevQuestionId == $currentQuestionId) {
                // Если вопросы одинаковы, то добавляем к вопросу ответ
                if ($row['answerid'] == null) {
                    // Если нет ответов, то ответов нет
                    $currentQuestion['answers'] = null;
                } else {
                    // Создаем данные, которые будут храниться в answer
                    if ($row['istextanswer']) {
                        $publicUrlAnswer = $row['answer'];
                    } else {
                        $publicUrlAnswer = $this->getPublicUrlAnswer($row['answer'], $row['answerid']);
                    }
                    $answer = array(
                        'answerId' => $row['answerid'],
                        'isText' => $row['istextanswer'],
                        'answer' => $publicUrlAnswer,
                        'isRightAnswer' => $row['isrightanswer']
                    );

                    // Добавляем ответ к текущему вопросу
                    $currentQuestion['answers'][] = $answer;
                }
            } else {
                // Если вопросы разные, то значит, добавляем прошлый вопрос с данными в массив test и создаем новый вопрос
                $test['questions'][] = $currentQuestion;
                // Назначаем предыдущему id текущего вопроса
                $prevQuestionId = $currentQuestionId;
                $publicUrl = $row['istextquestion'] ? $row['question'] : $this->getPublicUrlQuestion($row['question']);

                // Если нет ответов
                if ($row['answerid'] == null) {
                    // Создаем данные, которые будут храниться в массиве question
                    $currentQuestion = array(
                        'questionId' => $row['questionid'],
                        'question' => $publicUrl,
                        'isText' => $row['istextquestion'],
                        'scores' => $row['scores'],
                        'answers' => null
                    );
                } else {
                    if ($row['istextanswer']) {
                        $publicUrlAnswer = $row['answer'];
                    } else {
                        $publicUrlAnswer = $this->getPublicUrlAnswer($row['answer'], $row['answerid']);
                    }
                    // Создаем данные, которые будут храниться в answer
                    $answer = array(
                        'answerId' => $row['answerid'],
                        'isText' => $row['istextanswer'],
                        'answer' => $publicUrlAnswer,
                        'isRightAnswer' => $row['isrightanswer']
                    );

                    // Создаем данные, которые будут храниться в массиве question
                    $currentQuestion = array(
                        'questionId' => $row['questionid'],
                        'question' => $publicUrl,
                        'isText' => $row['istextquestion'],
                        'scores' => $row['scores'],
                        'answers' => array($answer)
                    );
                }


            }
        }
        // Последний вопрос не будет добавлен, поэтому добавляем здесь
        $test['questions'][] = $currentQuestion;

        return $test;
    }

    // Получение публичной ссылки на вопрос
    private function getPublicUrlQuestion(string $path): string
    {
        return YandexDisk::setPublish($path, 'Question');
    }

    // Получение публичной ссылки на ответ
    private function getPublicUrlAnswer(string $path, int $answerId): string
    {
        return YandexDisk::setPublish($path, 'Answer' . $answerId);
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
            $this->setTestByRow($row);
        } else {
            Response::sendError($error, $httpCode);
        }
    }

    // Установка теста по данным из БД
    private function setTestByRow($row)
    {
        // присвоим значения свойствам объекта 
        $this->testId = $row['test_id'];
        $this->groupId = $row['group_id'];
        $this->title = $row['title'];
        $this->isOpened = $row['is_opened'];
        $this->canViewResults = $row['can_view_results'];
    }
}
