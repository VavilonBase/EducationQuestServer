<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\core\JWTService;
use api\core\YandexDisk;
use api\modelsSql\UserSql;
use PDO;

class User extends Model
{
    // свойства объекта 
    public int $id;
    public string $firstName;
    public string $lastName;
    public string $middleName;
    public string $role;
    public bool $isActivated;
    public string $login;
    public string $password;

    // Вход
    public function login(string $login, string $password): array
    {
        // Присваиваем значения
        $this->login = $login;
        $this->password = $password;

        // Проверяем на существование пользователя и получаем данные о нем, если он существует
        $this->loginExistWithSetUser(true);

        // Проверяем пароль
        if (!password_verify($password, $this->password)) Response::sendError(Message::$messages['IncorrectPassword'], 400);

        // Возвращаем ответ
        return array(
            "user" => array(
                "id" => $this->id,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "middleName" => $this->middleName,
                "role" => $this->role,
                "isActivated" => $this->isActivated,
                "login" => $this->login
            ),
            "jwt" => JWTService::encode($this)
        );
    }

    // Регистрация
    public function registration(string $lastName, string $firstName, string $middleName,
                                 string $role, string $login, string $password): array
    {
        // Устанавливаем поля
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->middleName = $middleName;
        $this->login = $login;
        $this->password = $password;
        $this->role = $role;

        // Проверяем на существование пользователя
        if ($this->loginExistWithSetUser(false)) Response::sendError(Message::$messages['UserExist'], 400);

        // Хэшируем пароль
        $password_hash = password_hash($this->password, PASSWORD_BCRYPT);

        // Смотрим роль, и если это ученик, то активируем ему роль сразу, иначе делаем ее false
        if ($this->role == "STUDENT") $isActivated = true;
        else $isActivated = false;

        // Выполняем запрос на регистрацию
        $this->executeWithSetObjectData(
            UserSql::$query_registration_user,
            array(
                'login' => $this->login,
                'password' => $password_hash,
                'lastName' => $this->lastName,
                'firstName' => $this->firstName,
                'middleName' => $this->middleName,
                'role' => $this->role,
                'isActivated' => $isActivated ? 1 : 0
            ),
            Message::$messages['DBErrorExecute'], 500);

        // Создаем папку учителя на диске
        if ($this->role == "TEACHER") {
            $path = sprintf('/educational quest/User%d', $this->id);
            YandexDisk::createFolder($path);
        }

        // Возвращаем ответ
        return array(
            "user" => array(
                "id" => $this->id,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "middleName" => $this->middleName,
                "role" => $this->role,
                "isActivated" => $this->isActivated,
                "login" => $this->login
            ),
            "jwt" => JWTService::encode($this)
        );
    }

    // Обновление токена
    public function refreshToken(int $userId): array
    {
        // Устанавливаем параметры
        $this->id = $userId;

        // Получаем пользователя по Id
        $this->getUserById();

        // Возвращаем ответ
        return array(
            "user" => array(
                "id" => $this->id,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "middleName" => $this->middleName,
                "role" => $this->role,
                "isActivated" => $this->isActivated,
                "login" => $this->login
            ),
            "jwt" => JWTService::encode($this)
        );
    }

    // Активация учителя
    public function activateTeacher(int $teacherId): array
    {
        // Устанавливаем ID
        $this->id = $teacherId;

        // Получаем данные об учителе
        $this->getUserById();

        // Проверяем, что он и правда учитель
        if ($this->role != "TEACHER") Response::sendError(Message::$messages['IsNotTeacher'], 400);

        // Выполняем запрос
        $result = $this->db->query(UserSql::$query_activate_teacher, array('teacherId' => $this->id));

        // Получаем количество записей
        $num = $result->rowCount();

        // Проверяем, что учитель активирован
        if ($num <= 0) Response::sendError(Message::$messages['DBErrorExecute'], 500);

        // Если учитель активирован, получаем поле is_activated
        $dbData = $result->fetch(PDO::FETCH_ASSOC);
        $this->isActivated = $dbData['is_activated'];

        // Возвращаем ответ
        return array(
            "id" => $this->id,
            "firstName" => $this->firstName,
            "lastName" => $this->lastName,
            "middleName" => $this->middleName,
            "role" => $this->role,
            "isActivated" => $this->isActivated,
            "login" => $this->login

        );
    }

    // Смена пароля
    public function changePassword(int $userId, string $lastPassword, string $newPassword): array
    {
        // Устанавливаем ID
        $this->id = $userId;

        // Получаем пользователя по ID
        $this->getUserById();

        // Если пароли не совпадают, выдаем ошибку
        if (!password_verify($lastPassword, $this->password))
            Response::sendError(Message::$messages['PasswordNotEquals'], 400);

        // Если пароли совпадают
        // Хэшируем пароль
        $password_hash = password_hash($newPassword, PASSWORD_BCRYPT);
        // Меняем пароль
        $this->db->query(UserSql::$query_change_password, array(
            'password' => $password_hash,
            'userId' => $this->id
        ));

        // Возвращаем ответ
        return array(
            "user" => array(
                "id" => $this->id,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "middleName" => $this->middleName,
                "role" => $this->role,
                "isActivated" => $this->isActivated,
                "login" => $this->login
            ),
            "jwt" => JWTService::encode($this)
        );
    }

    // Получение всех пользователей
    public function getAllUsers(string $role): array
    {
        if ($role == 'ALL') {
            return $this->db->row(UserSql::$query_select_all_user);
        } else {
            return $this->db->row(UserSql::$query_select_all_user_by_role, array('role' => $role));
        }
    }

    // Обновление пользователя
    public function update(int $userId, string $lastName, string $firstName, string $middleName, string $role): array
    {
        // Устанавливаем поля
        $this->id = $userId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->middleName = $middleName;
        $this->role = $role;

        $this->executeWithSetObjectData(
            UserSql::$query_update_user,
            array(
                'userId' => $this->id,
                'firstName' => $this->firstName,
                'lastName' => $this->lastName,
                'middleName' => $this->middleName,
                'role' => $this->role
            ),
            Message::$messages['DBErrorExecute'], 500);

        // Возвращаем ответ
        return array(
            "user" => array(
                "id" => $this->id,
                "firstName" => $this->firstName,
                "lastName" => $this->lastName,
                "middleName" => $this->middleName,
                "role" => $this->role,
                "isActivated" => $this->isActivated,
                "login" => $this->login
            ),
            "jwt" => JWTService::encode($this)
        );
    }

    // Получение пользователя по id
    private function getUserById(): void
    {
        $this->executeWithSetObjectData(
            UserSql::$query_select_user_by_id,
            array('userId' => $this->id),
            Message::$messages['UserNotExist'], 400);
    }

    // Проверка на существование логина
    private function loginExistWithSetUser(bool $isSendError): bool
    {
        return $this->executeWithSetObjectData(
            UserSql::$query_for_check_login_exist,
            array('login' => $this->login),
            Message::$messages['UserNotExist'], 400, $isSendError);
    }

    // Метод по выполнению запроса в базу данных с последующим заполнение объекта или выдаче ошибки
    private function executeWithSetObjectData(string $query, array $arrayData, int $error,
                                              int    $httpCode, bool $isSendError = true): bool
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
            $this->setUserByRow($row);

            return true;
        } else {
            // Если надо выдавать ошибку, то выдаем, иначе возвращаем false
            if ($isSendError) Response::sendError($error, $httpCode);
            return false;
        }
    }

    // Установка пользователя по данным из БД
    private function setUserByRow(array $row): void
    {
        // присвоим значения свойствам объекта 
        $this->id = $row['user_id'];
        $this->firstName = $row['first_name'];
        $this->lastName = $row['last_name'];
        $this->middleName = $row['middle_name'];
        $this->role = $row['role'];
        $this->isActivated = $row['is_activated'];
        $this->login = $row['login'];
        $this->password = $row['password'];
    }
}
