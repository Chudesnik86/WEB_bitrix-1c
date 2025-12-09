<?php

namespace Legacy\API;

use CUser;
use Legacy\General\Constants;

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

        // Получаем группы пользователя через GetUserGroupArray() (как в документации Bitrix)
        $userGroups = $USER->GetUserGroupArray();
        
        // Нормализуем массив групп
        if (!is_array($userGroups)) {
            $userGroups = [];
        }
        
        $roleName = null;

        // Проверяем группы пользователя по ID через константы
        $groupNames = [];
        $groupCodes = [];
        $groupDetails = []; // Для отладки
        
        // Проверяем по константам групп (как в примере из документации Bitrix)
        // in_array($groupID, $USER->GetUserGroupArray())
        $groupStudentId = (int)Constants::GROUP_STUDENT; // Преобразуем строку '7' в число 7
        
        if (in_array($groupStudentId, $userGroups)) {
            $roleName = 'student';
        } elseif (Constants::GROUP_TEACHER && in_array((int)Constants::GROUP_TEACHER, $userGroups)) {
            $roleName = 'teacher';
        } elseif (Constants::GROUP_ADMIN && in_array((int)Constants::GROUP_ADMIN, $userGroups)) {
            $roleName = 'admin';
        }
        
        // Если не нашли по константам, проверяем по названию и коду (для обратной совместимости)
        if (!$roleName) {
            foreach ($userGroups as $groupId) {
                $group = \CGroup::GetByID($groupId);
                if ($groupRes = $group->Fetch()) {
                    $groupName = trim($groupRes['NAME'] ?? '');
                    $groupCode = trim($groupRes['STRING_ID'] ?? '');
                    $groupNameLower = mb_strtolower($groupName);
                    $groupCodeLower = mb_strtolower($groupCode);
                    
                    $groupNames[] = $groupName;
                    if ($groupCode) {
                        $groupCodes[] = $groupCode;
                    }
                    $groupDetails[] = [
                        'id' => $groupId,
                        'name' => $groupName,
                        'code' => $groupCode,
                        'nameLower' => $groupNameLower,
                        'codeLower' => $groupCodeLower
                    ];
                    
                    // Проверяем по коду группы
                    if ($groupCodeLower) {
                        if (in_array($groupCodeLower, ['student', 'students', 'студент', 'студенты'])) {
                            $roleName = 'student';
                            break;
                        } elseif (in_array($groupCodeLower, ['teacher', 'teachers', 'преподаватель', 'преподаватели'])) {
                            $roleName = 'teacher';
                            break;
                        } elseif (in_array($groupCodeLower, ['admin', 'administrators', 'администратор', 'администраторы'])) {
                            $roleName = 'admin';
                            break;
                        }
                    }
                    
                    // Проверяем по названию группы
                    if (!$roleName && $groupNameLower) {
                        if (in_array($groupNameLower, ['студент', 'student', 'студенты', 'students'])) {
                            $roleName = 'student';
                            break;
                        } elseif (in_array($groupNameLower, ['преподаватель', 'teacher', 'преподаватели', 'teachers'])) {
                            $roleName = 'teacher';
                            break;
                        } elseif (in_array($groupNameLower, ['администратор', 'admin', 'администраторы', 'administrators'])) {
                            $roleName = 'admin';
                            break;
                        }
                    }
                }
            }
        }

        // Если не нашли по названию, проверяем пользовательское поле UF_ROLE (для обратной совместимости)
        if (!$roleName && !empty($arUser['UF_ROLE'])) {
            $enumRes = \CUserFieldEnum::GetList([], ['ID' => $arUser['UF_ROLE']]);
            if ($enum = $enumRes->Fetch()) {
                $roleName = mb_strtolower($enum['VALUE']); // student / teacher / admin
            }
        }

        return [
            'id' => $arUser['ID'],
            'login' => $arUser['LOGIN'],
            'email' => $arUser['EMAIL'],
            'firstName' => $arUser['NAME'],
            'lastName' => $arUser['LAST_NAME'],
            'role' => $roleName,
            'groups' => $userGroups, // ID групп для отладки
            'groupNames' => $groupNames, // Названия групп для отладки
            'groupCodes' => $groupCodes, // Коды групп для отладки
            'groupDetails' => $groupDetails // Детали групп для отладки
        ];
    }
}