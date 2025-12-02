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
                'message' => 'Login and password are required'
            ];
        }

        $authResult = $USER->Login($login, $password, 'Y');

        if ($authResult === true) {
            return [
                'message' => 'Successfully authenticated'
            ];
        } else {
            return [
                'message' => 'Invalid login or password'
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