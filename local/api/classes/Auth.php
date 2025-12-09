<?php

namespace Legacy\API;

class Auth
{
    public static function login($arRequest)
    {
        global $USER;

        $login = $arRequest['login'] ?? null;
        $password = $arRequest['password'] ?? null;

        if (!$login || !$password) {
            return [
                'success' => false,
                'error' => 'Login and password are required',
                'message' => 'Login and password are required'
            ];
        }

        // Авторизация пользователя
        // Третий параметр 'Y' означает "запомнить пользователя"
        $authResult = $USER->Login($login, $password, 'Y', 'Y');

        if ($authResult === true) {
            // Проверяем, что пользователь действительно авторизован
            if ($USER->IsAuthorized()) {
                // Получаем информацию о пользователе сразу после авторизации
                $userId = $USER->GetID();
                $rsUser = \CUser::GetByID($userId);
                $arUser = $rsUser->Fetch();
                
                // Получаем группы пользователя для определения роли
                $userGroups = $USER->GetUserGroupArray();
                $roleName = null;
                
                // Проверяем по константе группы студента
                $groupStudentId = (int)\Legacy\General\Constants::GROUP_STUDENT;
                if (in_array($groupStudentId, $userGroups)) {
                    $roleName = 'student';
                } elseif (\Legacy\General\Constants::GROUP_TEACHER && in_array((int)\Legacy\General\Constants::GROUP_TEACHER, $userGroups)) {
                    $roleName = 'teacher';
                } elseif (\Legacy\General\Constants::GROUP_ADMIN && in_array((int)\Legacy\General\Constants::GROUP_ADMIN, $userGroups)) {
                    $roleName = 'admin';
                }
                
                return [
                    'success' => true,
                    'message' => 'Successfully authenticated',
                    'userId' => $userId,
                    'user' => [
                        'id' => $arUser['ID'],
                        'login' => $arUser['LOGIN'],
                        'email' => $arUser['EMAIL'],
                        'firstName' => $arUser['NAME'],
                        'lastName' => $arUser['LAST_NAME'],
                        'role' => $roleName,
                        'groups' => $userGroups
                    ]
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Authorization failed',
                    'message' => 'Authorization failed'
                ];
            }
        } else {
            $errorMessage = is_array($authResult) && isset($authResult['MESSAGE']) 
                ? $authResult['MESSAGE'] 
                : 'Invalid login or password';
            
            return [
                'success' => false,
                'error' => $errorMessage,
                'message' => $errorMessage
            ];
        }
    }

    public static function logout($arRequest)
    {
        global $USER;
        $USER->Logout();

        return [
            'message' => 'Successfully logged out'
        ];
    }
}