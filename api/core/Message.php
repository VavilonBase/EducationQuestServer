<?php

namespace api\core;

class Message
{
    public static $messages = array(
        // ОБЩИЕ
        "NotError" => 0, // Нет ошибки
        "JWTError" => 100, // Ошибка с jwt
        "IncorrectTokenFormat" => 101, // Токена не было вообще или был, но неправильного формата
        "UnauthorizedUser" => 102, // Пользователь не авторизован
        "AccessDenied" => 103, // Нет доступа
        "NotFoundQueryParam" => 104, // Не передан query параметр в url
        "NotFoundRequiredData" => 105, // Нет обязательных данных
        "PageNotFound" => 106, // Страница не найдена
        "CanNotCreateFolder" => 107, // Не получилось создать папку учителя
        "CanNotPublishFile" => 108, // Не удалось сделать файл публичным
        "CanNotGetPublicUrl" => 109, // Не удалось получить публичный ключ
        "CanNotLoadFile" => 110, // Не удалось загрузить файл
        "CanNotDeleteFileOrFolder" => 111, // Не удалось удалить папку
        // БД
        "DBErrorExecute" => 200, // Ошибка при выполнении запроса в базе данных
        // ПОЛЬЗОВАТЕЛИ
        "UserExist" => 300, // Ошибка при регистрации пользователя, когда такой пользователь уже существует
        "UserNotExist" => 301, // Ошибка при обращении к несуществующему пользователю
        "IncorrectPassword" => 302, // Неверный пароль
        "IsNotTeacher" => 303, // Пользователь не является учителем
        "PasswordNotEquals" => 304, // При смене пароля, пароли не совпали
        // ГРУППЫ
        "TeacherHasNotGroups" => 400, // У учителя нет групп
        "GroupNotFound" => 401, // Группа не найдена
        "StudentIsInAGroup" => 402, // Ученик уже находится в группе
        "StudentIsNotInAGroup" => 403, // Ученик не состоит в этой группе
        "StudentHasNotGroups" => 404, // У ученика нет групп
        "GroupHasNotStudents" => 405, // У группы нет учеников
        "UserIsNotCreatorGroup" => 406, // Пользователь не является создателем группы
        // ТЕСТЫ
        "GroupHasNotTests" => 501, // В группе нет тестов
        "TestNotExist" => 502, // Такого теста не существует
        "TestCanNotOpenedAndClosedTogether" => 504, // Тесте не может быть одновременно открыт и закрыт
        "UserIsNotCreatorTest" => 505, // Пользователь не является создателем теста
        "TestIsClosed" => 506, // Тест закрыт
        "TestIsNotOpened" => 507, // Тест не открыт
        "TestHasNotQuestions" => 510, // Тест не имеет вопросов
        // ВОПРОСЫ
        "NotFoundQuestion" => 600, // Вопрос не найден
        "QuestionHasNotAnswers" => 601, // Нет ответов на вопрос
        // ОТВЕТЫ
        "AnswerNotFound" => 700, // Ответ не найден
        // РЕЗУЛЬТАТЫ
        "NotFoundResult" => 800, // Результат не найден
    );
}
