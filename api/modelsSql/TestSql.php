<?php

namespace api\modelsSql;

class TestSql
{
// Запросы
    public static string $query_create_test =
        "INSERT INTO public.tests (group_id, title, is_opened, can_view_results)
            VALUES (:groupId, :title, :isOpened, :canViewResults)
            RETURNING *;";

    public static string $query_get_user_id_by_test_id =
        "SELECT user_id FROM public.tests AS t 
            LEFT JOIN public.groups AS g ON t.group_id = g.group_id 
            WHERE test_id = :testId
            LIMIT 1;";

    public static string $query_get_all_group_tests =
        "SELECT test_id AS testId, title, group_id AS groupId, is_opened AS isOpened,
            can_view_results AS canViewResults FROM public.tests
            WHERE group_id = :groupId;";

    public static string $query_get_test_by_id = "SELECT * FROM public.tests WHERE test_id = :testId";

    public static string $query_get_max_scores_for_test_by_test_id =
        "SELECT SUM(scores) as maxScores FROM public.questions
            WHERE test_id = :testId;";

    public static string $query_get_test_by_test_id_for_creator =
        "SELECT g.user_id, t.test_id, t.title, t.group_id, t.is_opened, t.can_view_results
            FROM public.tests AS t
            LEFT JOIN public.groups AS g ON t.group_id = g.group_id
            WHERE test_id = :testId
            LIMIT 1;";

    public static string $query_update_test =
        "UPDATE public.tests
            SET
                title = :title,
                can_view_results = :canViewResults
            WHERE
                test_id = :testId
            RETURNING *;";

    public static string $query_delete_test = "DELETE FROM public.tests WHERE test_id = :testId";

    public static string $query_open_test =
        "UPDATE public.tests
            SET
                is_opened = true
            WHERE
                test_id = :testId
            RETURNING *;";

    public static string $query_close_test =
        "UPDATE public.tests
            SET
                is_opened = false
            WHERE
                test_id = :testId
            RETURNING *;";

    public static string $query_get_test_with_questions_for_teacher =
        "SELECT q.question_id AS questionId, q.question AS question,
            q.is_text AS isTextQuestion, q.scores AS scores, a.answer_id AS answerId, a.answer AS answer,
            a.is_text AS isTextAnswer, a.is_right_answer AS isRightAnswer
            FROM public.tests AS t
            LEFT JOIN public.questions AS q ON t.test_id = q.test_id
            LEFT JOIN public.answers AS a ON q.question_id = a.question_id
            WHERE t.test_id = :testId AND q.question_id is not null
            ORDER BY q.question_id;";

    public static string $query_get_test_with_questions_for_student =
        "SELECT q.question_id AS questionId, q.question AS question,
            q.is_text AS isTextQuestion, q.scores AS scores, a.answer_id AS answerId, a.answer AS answer,
            a.is_text AS isTextAnswer, false as isRightAnswer
            FROM public.tests AS t
            LEFT JOIN public.questions AS q ON t.test_id = q.test_id
            LEFT JOIN public.answers AS a ON q.question_id = a.question_id
            WHERE t.test_id = :testId
            ORDER BY q.question_id;;";

    public static string $query_check_access_student_to_the_test =
        "SELECT ug.user_id FROM public.users_groups AS ug
            LEFT JOIN public.groups AS g ON ug.group_id = g.group_id
            LEFT JOIN public.tests AS t ON t.group_id = g.group_id
            WHERE t.test_id = :testId AND ug.user_id = :userId AND t.is_opened = true
            LIMIT 1;";

    public static string $query_check_view_results =
        "SELECT ug.user_id FROM public.users_groups AS ug
            LEFT JOIN public.groups AS g ON ug.group_id = g.group_id
            LEFT JOIN public.tests As t ON t.group_id = g.group_id
            WHERE t.test_id = :testId AND ug.user_id = :userId AND t.can_view_results = true
            LIMIT 1;";

    public static string $query_get_student_result =
        "SELECT result_id AS resultId, user_id AS userId, test_id AS testId, total_scores AS totalScores
            FROM public.results 
            WHERE user_id = :userId AND test_id = :testId";

    public static string $query_get_full_student_result =
        "SELECT r.result_id AS resultId, r.total_scores AS totalScores, 
            rq.question_id AS questionId, rq.answer_id AS answerId,
			q.question AS question, q.is_text AS isQuestionText,
			a.answer AS answer, a.is_text AS isAnswerText
            FROM public.results AS r 
            LEFT JOIN public.results_questions AS rq ON r.result_id = rq.result_id
			LEFT JOIN public.questions AS q ON q.question_id = rq.question_id
			LEFT JOIN public.answers AS a ON a.answer_Id = rq.answer_id
            WHERE r.result_id = :resultId;";

    public static string $query_get_right_answers_for_test =
        "SELECT a.answer_id AS answerId, a.answer as answer, a.is_text AS isText, q.question_id as questionId
            FROM public.tests AS t
            LEFT JOIN public.questions AS q ON t.test_id = q.test_id
            LEFT JOIN public.answers AS a ON q.question_id = a.question_id
            WHERE t.test_id = :testId AND a.is_right_answer = true;";

    public static string $query_get_all_results_for_test =
        "SELECT r.result_id AS resultId, r.user_id AS userId, r.test_id AS testId, r.total_scores AS totalScores, t.title AS title,
			u.last_name AS lastName, u.first_name AS firstName, u.middle_name AS middleName
            FROM public.results AS r
            LEFT JOIN public.tests AS t ON r.test_id = t.test_id
			LEFT JOIN public.users_groups AS ug ON ug.group_id = t.group_id AND ug.user_id = r.user_id
			LEFT JOIN public.users AS u ON u.user_id = r.user_id
			WHERE t.test_id = :testId AND ug.is_study = true;";

    public static string $query_check_test_exists_questions = "
        SELECT * FROM public.questions 
            WHERE test_id = :testId 
            LIMIT 1;
    ";
}