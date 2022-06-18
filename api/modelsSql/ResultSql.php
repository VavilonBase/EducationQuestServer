<?php

namespace api\modelsSql;

class ResultSql
{
    // Запросы
    public static string $query_check_access =
        "SELECT ug.user_id FROM public.users_groups AS ug 
            LEFT JOIN public.groups AS g ON ug.group_id = g.group_id
            LEFT JOIN public.tests AS t ON g.group_id = t.group_id
            WHERE ug.user_id = :userId AND test_id = :testId AND is_study = true AND t.is_opened = true
            LIMIT 1;
         ";

    public static string $query_get_result_by_result_id =
        "
        SELECT * FROM public.results
            WHERE result_id = :resultId
            LIMIT 1;
    ";

    public static string $query_get_all_right_answers_for_test =
        "SELECT q.question_id, q.scores, a.answer_id FROM public.questions AS q
            LEFT JOIN public.answers AS a ON q.question_id = a.question_id
            WHERE test_id = :testId AND is_right_answer = true;";

    public static string $query_create_result =
        "INSERT INTO public.results (user_Id, test_id, total_scores) VALUES (:userId, :testId, 0) RETURNING *;";

    public static string $query_create_result_question =
        "INSERT INTO public.results_questions (result_id, question_id, answer_id) VALUES
            (:resultId, :questionId, :answerId);";

    public static string $query_add_total_score =
        "UPDATE public.results
            SET
                total_scores = :totalScores
            WHERE
                result_id = :resultId
            RETURNING *";
}
