<?php

return [

    '' => [
        'controller' => 'main',
        'action' => 'index',
    ],
    // USERS
    'user/login' => [
        'controller' => 'user',
        'action' => 'login',
    ],

    'user/registration' => [
        'controller' => 'user',
        'action' => 'registration',
    ],

    'user/activateTeacher\?teacherId=\d+' => [
        'controller' => 'user',
        'action' => 'activateTeacher'
    ],

    'user/changePassword' => [
        'controller' => 'user',
        'action' => 'changePassword'
    ],

    'user/getAllUsers\?role=\S+' => [
        'controller' => 'user',
        'action' => 'getAllUsers'
    ],

    'user/refreshToken' => [
        'controller' => 'user',
        'action' => 'refreshToken'
    ],

    'user/update' => [
        'controller' => 'user',
        'action' => 'update'
    ],
    // GROUPS
    'group/create' => [
        'controller' => 'group',
        'action' => 'create'
    ],

    'group/delete\?groupId=\d+' => [
        'controller' => 'group',
        'action' => 'delete'
    ],

    'group/getAllTeacherGroups' => [
        'controller' => 'group',
        'action' => 'getAllTeacherGroups'
    ],

    'group/getAllGroupStudents\?groupId=\d+&isStudy=\d+' => [
        'controller' => 'group',
        'action' => 'getAllGroupStudents'
    ],

    'group/getAllStudentGroups' => [
        'controller' => 'group',
        'action' => 'getAllStudentGroups'
    ],

    'group/join' => [
        'controller' => 'group',
        'action' => 'join'
    ],

    'group/update' => [
        'controller' => 'group',
        'action' => 'update'
    ],
    'group/removeStudent' => [
        'controller' => 'group',
        'action' => 'removeStudentFromGroup'
    ],
    'group/leave' => [
        'controller' => 'group',
        'action' => 'leavingStudentFromGroup'
    ],
    // TESTS
    'test/create' => [
        'controller' => 'test',
        'action' => 'create'
    ],
    'test/getTestWithQuestion\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'getTestWithQuestionByTestId'
    ],
    'test/getAllGroupTests\?groupId=\d+' => [
        'controller' => 'test',
        'action' => 'getAllGroupTests'
    ],
    'test/update' => [
        'controller' => 'test',
        'action' => 'update'
    ],
    'test/delete\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'delete'
    ],
    'test/open\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'openTest'
    ],
    'test/close\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'closeTest'
    ],
    'test/getStudentTestResult\?studentId=\d+&testId=\d+' => [
        'controller' => 'test',
        'action' => 'getStudentTestResult'
    ],
    'test/getStudentTestResultWithRightAnswer\?resultId=\d+' => [
        'controller' => 'test',
        'action' => 'getStudentTestResultWithRightAnswer'
    ],
    'test/getAllResultsTestForStudents\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'getAllResultsTestForStudents'
    ],
    'test/getMaxScoresForTestByTestId\?testId=\d+' => [
        'controller' => 'test',
        'action' => 'getMaxScoresForTestByTestId'
    ],
    'question/create' => [
        'controller' => 'question',
        'action' => 'create'
    ],
    'question/update' => [
        'controller' => 'question',
        'action' => 'update'
    ],
    'question/delete\?questionId=\d+' => [
        'controller' => 'question',
        'action' => 'delete'
    ],
    'question/getQuestionWithAnswers\?questionId=\d+' => [
        'controller' => 'question',
        'action' => 'getQuestionWithAnswers'
    ],
    'answer/create' => [
        'controller' => 'answer',
        'action' => 'create'
    ],
    'answer/update' => [
        'controller' => 'answer',
        'action' => 'update'
    ],
    'answer/delete\?answerId=\d+' => [
        'controller' => 'answer',
        'action' => 'delete'
    ],
    'result/create' => [
        'controller' => 'result',
        'action' => 'create'
    ],
];
