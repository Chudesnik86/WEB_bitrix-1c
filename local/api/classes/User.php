<?php

namespace Legacy\API;

use CUser;

class User
{
    public static function get($arRequest)
    {
        global $USER;

        if (!$USER->IsAuthorized()) {
            return ['error' => 'User not authenticated'];
        }

        $userId = $USER->GetID();
        $rsUser = CUser::GetByID($userId);
        $arUser = $rsUser->Fetch();

        // Получаем текст роли по ID UF_ROLE
        $roleName = null;

        if (!empty($arUser['UF_ROLE'])) {
            $enumRes = \CUserFieldEnum::GetList([], ['ID' => $arUser['UF_ROLE']]);
            if ($enum = $enumRes->Fetch()) {
                $roleName = $enum['VALUE']; // student / teacher / admin
            }
        }

        return [
            'id' => $arUser['ID'],
            'login' => $arUser['LOGIN'],
            'email' => $arUser['EMAIL'],
            'firstName' => $arUser['NAME'],
            'lastName' => $arUser['LAST_NAME'],
            'role' => $roleName
        ];
    }
}