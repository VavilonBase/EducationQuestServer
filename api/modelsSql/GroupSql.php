<?php

namespace api\modelsSql;

class GroupSql
{
    // Запросы к БД
    public static string $query_create_group =
        "INSERT INTO public.groups (user_id, title, code_word)
             VALUES (:userId, :title, :codeWord)
             RETURNING *;";

    public static string $query_get_user_id_by_group_id =
        "SELECT user_id FROM public.groups 
            WHERE group_id = :groupId
            LIMIT 1;";

    public static string $query_get_all_teacher_groups =
        "SELECT group_id AS groupId, user_id AS userId, title, code_word AS codeWord 
            FROM public.groups
            WHERE user_id = :userId";

    public static string $query_get_group_id_by_code_word = "SELECT group_id FROM public.groups WHERE code_word = :codeWord;";

    public static string $query_student_is_in_a_group =
        "SELECT group_id, is_study FROM public.users_groups 
            WHERE user_id = :userId AND group_id = :groupId
            LIMIT 1;";

    public static string $query_student_join_to_the_group =
        "INSERT INTO public.users_groups (group_id, user_id) 
            VALUES (:groupId, :userId);";

    public static string $query_student_rejoin_to_the_group =
        "UPDATE public.users_groups
            SET
                is_study = true
            WHERE
                group_id = :groupId AND
                user_id = :userId";

    public static string $query_get_all_student_groups =
        "SELECT g.group_id AS groupId, g.title AS title, u.user_id AS creatorId, 
			u.last_name AS lastName, u.first_name AS firstName, u.middle_name AS middleName
			FROM public.groups AS g 
            LEFT JOIN public.users_groups AS ug ON g.group_id = ug.group_id 
			LEFT JOIN public.users AS u ON g.user_id = u.user_id
            WHERE ug.user_id = :userId AND is_study = true;";

    public static string $query_get_all_group_students =
        "SELECT u.user_id AS userId, u.last_name AS lastName, 
            u.first_name AS firstName, u.middle_name AS middleName, u.login AS login
            FROM public.users_groups AS ug LEFT JOIN public.users AS u ON ug.user_id = u.user_id
            WHERE ug.group_id = :groupId;";

    public static string $query_get_all_group_studying_students =
        "SELECT u.user_id AS userId, u.last_name AS lastName, 
            u.first_name AS firstName, u.middle_name AS middleName, u.login AS login
            FROM public.users_groups AS ug LEFT JOIN public.users AS u ON ug.user_id = u.user_id
            WHERE ug.group_id = :groupId AND ug.is_study = true;";

    public static string $query_update_group = "UPDATE public.groups
                                    SET
                                        title = :title
                                    WHERE group_id = :groupId
                                    RETURNING *;";

    public static string $query_delete_group = "DELETE FROM public.groups WHERE group_id = :groupId";

    public static string $query_remove_student_from_group =
        "UPDATE public.users_groups
            SET
                is_study = false
            WHERE
                user_id = :userId AND
                group_id = :groupId";
}