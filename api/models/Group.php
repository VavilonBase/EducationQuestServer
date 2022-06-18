<?php

namespace api\models;

use api\core\Message;
use api\core\Model;
use api\core\Response;
use api\core\YandexDisk;
use api\modelsSql\GroupSql;
use Ramsey\Uuid\Uuid;
use PDO;

class Group extends Model
{
    // свойства объекта 
    public int $groupId;
    public int $userId;
    public string $title;
    public string $codeWord;

    // Создание группы
    public function create(int $userId, string $title): array
    {
        // Устанавливаем значение
        $this->userId = $userId;
        $this->title = $title;

        // Генерация кодового слова
        $this->codeWord = Uuid::uuid4();

        // Создаем группу
        $this->executeWithSetObjectData(GroupSql::$query_create_group, array(
            'userId' => $this->userId,
            'title' => $this->title,
            'codeWord' => $this->codeWord
        ), Message::$messages['DBErrorExecute'], 500);

        // Создаем папку на диске для группы
        $path = sprintf('/educational quest/User%d/Group%d/', $userId, $this->groupId);
        YandexDisk::createFolder($path);

        // Возвращаем ответ
        return array(
            'groupId' => $this->groupId,
            'userId' => $this->userId,
            'title' => $this->title,
            'codeWord' => $this->codeWord
        );
    }

    // Обновление группы
    public function update(int $groupId, int $userId, string $title): array
    {
        // Устанавливаем значения
        $this->groupId = $groupId;
        $this->userId = $userId;
        $this->title = $title;

        // Проверяем доступ пользователя к группе
        $this->checkAccessToGroupByGroupId();

        // Обновляем группу
        $this->executeWithSetObjectData(GroupSql::$query_update_group, array(
            'title' => $this->title,
            'groupId' => $this->groupId
        ), Message::$messages['DBErrorExecute'], 500);

        // Возвращаем ответ
        return array(
            'groupId' => $this->groupId,
            'userId' => $this->userId,
            'title' => $this->title,
            'codeWord' => $this->codeWord
        );
    }

    // Удаление группы
    public function delete(int $userId, int $groupId): bool
    {
        // Устанавливаем значения
        $this->groupId = $groupId;
        $this->userId = $userId;

        // Проверяем доступ пользователя к группе
        $this->checkAccessToGroupByGroupId();

        // Если группа существует
        // Выполняем обновление группы
        $this->db->query(GroupSql::$query_delete_group, array(
            'groupId' => $this->groupId
        ));

        // Удаляем папку группы
        $path = sprintf('/educational quest/User%d/Group%d/', $userId, $this->groupId);
        YandexDisk::delete($path);

        return true;
    }

    // Удаление ученика из группы
    public function removeStudentFromGroup(int $userId, int $studentId, int $groupId): bool
    {
        // Устанавливаем значения
        $this->groupId = $groupId;
        $this->userId = $userId;

        // Проверяем доступ пользователя к группе
        $this->checkAccessToGroupByGroupId();

        // Если доступ есть, то выполняем удаление
        $this->db->query(GroupSql::$query_remove_student_from_group, array(
            'userId' => $studentId,
            'groupId' => $this->groupId
        ));

        return true;
    }

    // Выход ученика из группы
    public function leavingStudentFromGroup($studentId, $groupId): bool
    {
        // Устанавливаем значения
        $this->groupId = $groupId;

        // Проверяем, что ученик обучается в этой группе
        $this->studentIsInAGroup($studentId);

        // Если ученик в группе состоит, то выполняем удаление
        $this->db->query(GroupSql::$query_remove_student_from_group, array(
            'userId' => $studentId,
            'groupId' => $this->groupId
        ));

        return true;
    }

    // Получение всех групп учителя
    public function getAllTeacherGroups(int $userId): array
    {
        // Устанавливаем значения
        $this->userId = $userId;

        // Получаем все группы
        $rows = $this->db->row(GroupSql::$query_get_all_teacher_groups, array(
            'userId' => $this->userId
        ));

        // Если групп нет, то выводим ошибку
        if (count($rows) == 0) Response::sendError(Message::$messages['TeacherHasNotGroups'], 400);

        // Если группы есть, возвращаем ответ
        return $rows;
    }

    // Получение всех учеников группы
    public function getAllGroupStudents(int $groupId, bool $is_study): array
    {
        // Устанавливаем значения
        $this->groupId = $groupId;

        // Смотрим каких учеников надо получать и выбираем запрос
        if ($is_study) $query = GroupSql::$query_get_all_group_studying_students;
        else $query = GroupSql::$query_get_all_group_students;

        // Получаем всех учеников
        $rows = $this->db->row($query, array(
            'groupId' => $this->groupId
        ));

        // Если учеников нет, то выводим ошибку
        if (count($rows) == 0) Response::sendError(Message::$messages['GroupHasNotStudents'], 400);

        // Если группы есть, возвращаем ответ
        return $rows;
    }

    // Получаем все группы ученика
    public function getAllStudentGroups(int $studentId): array
    {
        // Получаем все группы
        $rows = $this->db->row(GroupSql::$query_get_all_student_groups, array(
            'userId' => $studentId
        ));

        // Если групп нет, то выводим ошибку
        if (count($rows) == 0) Response::sendError(Message::$messages['StudentHasNotGroups'], 400);

        // Если группы есть, возвращаем ответ
        return $rows;
    }

    // Присоединение к группе ученика
    public function joinToTheGroup(int $studentId, string $codeWord): bool
    {
        // Устанавливаем значения
        $this->codeWord = $codeWord;

        // Получаем ID группы
        $groupId = $this->getGroupIdByCodeWord();

        // Устанавливаем значения
        $this->groupId = $groupId;

        //Если нет ошибки, то смотрим, нет ли уже этого студента в группе
        $studentIsInAGroup = $this->studentIsInAGroup($studentId, false);
        // Если ученик в группе и обучается
        if ($studentIsInAGroup != null && $studentIsInAGroup['isStudy'] == true)
            Response::sendError(Message::$messages['StudentIsInAGroup'], 400);
        // Если ученик был когда-то в группе
        if ($studentIsInAGroup != null && $studentIsInAGroup['isStudy'] == false) {
            $this->db->query(GroupSql::$query_student_rejoin_to_the_group, array(
                'userId' => $studentId,
                'groupId' => $this->groupId
            ));
        } else {
            // Если такого ученика нет в группе, то добавляем его
            $this->db->query(GroupSql::$query_student_join_to_the_group, array(
                'userId' => $studentId,
                'groupId' => $this->groupId
            ));
        }

        // Если не возникло никакой ошибки, то возвращаем true
        return true;
    }

    // Проверка на доступ к группе по ее ID
    public function checkAccessToGroupByGroupId(int $userId = null, int $groupId = null)
    {
        // Если были переданы значения, то используем их
        if ($userId !== null && $groupId !== null) {
            $this->userId = $userId;
            $this->groupId = $groupId;
        }

        // Ищем группу с текущим пользователем и группой
        $result = $this->db->query(GroupSql::$query_get_user_id_by_group_id, array(
            'groupId' => $this->groupId
        ));

        // получаем количество строк
        $num = $result->rowCount();

        // если нет строк, то такой группы не существует
        if ($num <= 0) {
            Response::sendError(Message::$messages['GroupNotFound'], 400);
        } else {
            $res = $result->fetch(PDO::FETCH_ASSOC);
            // Сравниваем пользователей
            if ($res['user_id'] != $this->userId) {
                Response::sendError(Message::$messages['UserIsNotCreatorGroup'], 400);
            }
        }
    }

    // Пытаемся получить группу его кодовому слову
    private function getGroupIdByCodeWord(): int
    {
        // Выполняем поиск
        $result = $this->db->query(GroupSql::$query_get_group_id_by_code_word, array(
            'codeWord' => $this->codeWord
        ));

        // получаем количество строк 
        $num = $result->rowCount();

        // если такая группа есть
        if ($num > 0) {

            // получаем значения 
            $row = $result->fetch(PDO::FETCH_ASSOC);

            return $row['group_id'];
        } else {
            Response::sendError(Message::$messages['GroupNotFound'], 400);
            return 0;
        }
    }

    // Проверяем нет ли уже ученика в этой группе
    private function studentIsInAGroup(int $studentId, bool $isSendError = true): array
    {
        // Поиск ученика
        $result = $this->db->query(GroupSql::$query_student_is_in_a_group, array(
            'userId' => $studentId,
            'groupId' => $this->groupId
        ));

        // получаем количество строк 
        $num = $result->rowCount();

        // если такой ученик уже находится в группе
        if ($num > 0) {
            // Получаем данные
            $row = $result->fetch(PDO::FETCH_ASSOC);
            return array(
                'groupId' => $row['group_id'],
                'isStudy' => $row['is_study']
            );
        } else {
            // Проверяем надо ли отправлять ошибку
            if ($isSendError) Response::sendError(Message::$messages['StudentIsNotInAGroup'], 400);
            return array();
        }
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

            // заполняем объект данными из БД
            $this->setGroupByRow($row);
        } else {
            Response::sendError($error, $httpCode);
        }
    }

    // Установка группы по данным из БД
    private function setGroupByRow(array $row)
    {
        // присвоим значения свойствам объекта 
        $this->userId = $row['user_id'];
        $this->groupId = $row['group_id'];
        $this->title = $row['title'];
        $this->codeWord = $row['code_word'];
    }
}
