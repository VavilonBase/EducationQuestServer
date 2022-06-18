<?php

namespace api\modelsSql;

class QuestionSql
{
    // Запросы
    public static string $query_create_question =
        "INSERT INTO public.questions (test_id, question, is_text, scores)
            VALUES (:testId, :question, :isText, :scores)
            RETURNING *;";

    public static string $query_set_public_url_question =
        "UPDATE public.questions
            SET
                question = :question
            WHERE
                question_id = :questionId
            RETURNING *;";

    public static string $query_get_question_by_question_id =
        "SELECT * FROM public.questions 
            WHERE question_id = :questionId
            LIMIT 1;";

    public static string $query_update_question =
        "UPDATE public.questions
            SET
                question = :question,
                is_text = :isText,
                scores = :scores
            WHERE question_id = :questionId
            RETURNING *;";

    public static string $query_delete_question = "DELETE FROM public.questions WHERE question_id = :questionId";

    public static string $query_get_question_with_answers =
        "SELECT a.answer_id AS answerId, a.answer AS answer, a.is_text as isText, 
            a.is_right_answer AS isRightAnswer 
            FROM public.questions AS q
            LEFT JOIN public.answers AS a ON q.question_id = a.question_id
            WHERE q.question_id = :questionId;";
}
