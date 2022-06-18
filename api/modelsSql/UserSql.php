<?php

namespace api\modelsSql;

class UserSql
{
    // Запросы к базе данных
    public static string $query_select_user_by_id =
        "SELECT * FROM public.users 
            WHERE user_id = :userId
            LIMIT 1;";

    public static string $query_registration_user =
        "INSERT INTO public.users
            (last_name, first_name, middle_name, role, is_activated, login, password) 
            VALUES (:lastName, :firstName, :middleName, :role, :isActivated, :login, :password)
            RETURNING *;";

    public static string $query_for_check_login_exist =
        "SELECT *
            FROM public.users
            WHERE login = :login
            LIMIT 1;";

    public static string $query_update_user =
        "UPDATE public.users
            SET
                first_name = :firstName,
                last_name = :lastName,
                middle_name = :middleName,
                role = :role
            WHERE user_id = :userId
            RETURNING *;";

    public static string $query_select_all_user =
        "SELECT user_id AS id, first_name AS firstName, last_name AS lastName, 
            middle_name AS middleName, role, is_activated AS isActivated, login 
            FROM public.users;";

    public static string $query_select_all_user_by_role =
        "SELECT user_id AS id, first_name AS firstName, last_name AS lastName, 
        middle_name AS middleName, role, is_activated AS isActivated, login FROM public.users
        WHERE role = :role;";

    public static string $query_activate_teacher =
        "UPDATE public.users
            SET
                is_activated = true
            WHERE user_id = :teacherId
            RETURNING is_activated;";

    public static string $query_select_user_by_login =
        "SELECT * FROM public.users 
            WHERE login = :login
            LIMIT 1;";

    public static string $query_change_password =
        "UPDATE public.users
            SET 
            password = :password
            WHERE user_id = :userId;";
}