<?php

namespace api\modelsSql;

class AnswerSql
{
    // Запросы
    public static string $query_create_answer =
        "INSERT INTO public.answers (question_Id, answer, is_text, is_right_answer)
            VALUES (:questionId, :answer, :isText, :isRightAnswer)
            RETURNING *;";

    public static string $query_set_public_url_answer =
        "UPDATE public.answers
            SET
                answer = :answer
            WHERE
                answer_id = :answerId
            RETURNING *;";

    public static string $query_get_answer_by_answer_id =
        "SELECT * FROM public.answers
            WHERE answer_id = :answerId
            LIMIT 1";

    public static string $query_update_answer =
        "UPDATE public.answers
            SET
                answer = :answer,
                is_text = :isText,
                is_right_answer = :isRightAnswer
            WHERE
                answer_id = :answerId
            RETURNING *;";

    public static string $query_delete_answer = "DELETE FROM public.answers WHERE answer_id = :answerId;";
}
