<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER;
if($USER->IsAdmin()){
    $res = [];
    foreach($_FILES as $file) {
        $id = CFile::SaveFile(
            $file,
            'legacy.settings/'.$file['name']
        );
        $res[] = \CFile::GetPath($id);
    }
    echo json_encode(['pathes' => $res], JSON_UNESCAPED_UNICODE);
}