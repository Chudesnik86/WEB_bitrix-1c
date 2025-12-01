<?php


namespace Legacy\General;

use Legacy\IblockController\File;

class DataProcessor
{
    public static function processIBProperties($query, $arrays, $needElementInfo = false)
    {
        $result = [];

        $arrayPropsCodes = $arrays['arrayPropsCodes'] ?? [];
        $arrayPropsWithDescriptionCodes = $arrays['arrayPropsWithDescriptionCodes'] ?? [];
        $filePropsCodes = $arrays['filePropsCodes'] ?? [];
        $fileInfoPropsCodes = $arrays['fileInfoPropsCodes'] ?? [];
        $filesPropsCodes = $arrays['filesPropsCodes'] ?? [];
        $enumPropsCodes = $arrays['enumPropsCodes'] ?? [];
        $sprintEditorPropsCodes = $arrays['sprintEditorPropsCodes'] ?? [];
        $htmlPropsCodes = $arrays['htmlPropsCodes'] ?? [];

        $db = $query->exec();

        while ($res = $db->fetch()) {
            $id = $res['ID'];
            if ($needElementInfo && !$result[$id]['INFO']) {
                $result[$id]['INFO'] = [
                    'ID' => $res['ID'],
                    'NAME' => $res['NAME'],
                    'CODE' => $res['CODE'],
                    'ACTIVE_TO'=> $res['ACTIVE_TO'],
                    'ACTIVE_FROM' => $res['ACTIVE_FROM'],
                    'PREVIEW_PICTURE'=> getFilePath($res['PREVIEW_PICTURE']),
                    'DETAIL_PICTURE'=> getFilePath($res['DETAIL_PICTURE']),
                    'PREVIEW_TEXT' => $res['PREVIEW_TEXT'],
                    'DETAIL_TEXT' => $res['DETAIL_TEXT'],
                    'PREVIEW_PICTURE_INFO'=> File::getFilesInfo($res['PREVIEW_PICTURE'], false, false),
                    'DETAIL_PICTURE_INFO'=> File::getFilesInfo($res['DETAIL_PICTURE'], false, false),
                ];
            }
            if (in_array($res['PROPERTY_CODE'], $arrayPropsCodes)){
                $result[$id][$res['PROPERTY_CODE']][] = $res['PROPERTY_VALUE'];
            }
            elseif (in_array($res['PROPERTY_CODE'], $arrayPropsWithDescriptionCodes)){
                $result[$id][$res['PROPERTY_CODE']][] = [
                    'value' => $res['PROPERTY_VALUE'],
                    'description' => $res['PROPERTY_DESCRIPTION']
                ];
            }
            elseif (in_array($res['PROPERTY_CODE'], $filePropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']] = getFilePath($res['PROPERTY_VALUE']);
            }
            elseif (in_array($res['PROPERTY_CODE'], $fileInfoPropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']] = File::getFilesInfo($res['PROPERTY_VALUE'], false, false);
            }
            elseif (in_array($res['PROPERTY_CODE'], $filesPropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']][] = getFilePath($res['PROPERTY_VALUE']);
            }
            elseif (in_array($res['PROPERTY_CODE'], $enumPropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']] = $res['ENUM_CODE'];
            }
            elseif (in_array($res['PROPERTY_CODE'], $sprintEditorPropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']] = \Bitrix\Main\Web\Json::decode($res['PROPERTY_VALUE'])['blocks'];
            }
            elseif (in_array($res['PROPERTY_CODE'], $htmlPropsCodes)) {
                $result[$id][$res['PROPERTY_CODE']] = unserialize($res['PROPERTY_VALUE'])['TEXT'];
            }
            elseif ($res['PROPERTY_VALUE']) {
                $result[$id][$res['PROPERTY_CODE']] = $res['PROPERTY_VALUE'];
            }
        }

        return $result;
    }

    public static function getMonthNameForDate($month)
    {
        return match($month) {
            '01' => 'января',
            '02' => 'февраля',
            '03' => 'марта',
            '04' => 'апреля',
            '05' => 'мая',
            '06' => 'июня',
            '07' => 'июля',
            '08' => 'августа',
            '09' => 'сентября',
            '10' => 'октября',
            '11' => 'ноября',
            '12' => 'декабря',
        };
    }

    public static function sortResultByIDs($result, $ids, $is_object = false)
    {
        $newResult = [];

        if ($is_object) {
            foreach ($ids as $id) {
                if ($result[$id]) {
                    $newResult[] = $result[$id];
                }
            }
        } else {
            foreach ($ids as $id) {
                $index = array_search($id, array_column($result, 'id'));

                if (is_numeric($index)) {
                    $newResult[] = $result[$index];
                }
            }
        }


        if (count($newResult) == 0) {
            $newResult = $result;
        }

        return $newResult;
    }
}
