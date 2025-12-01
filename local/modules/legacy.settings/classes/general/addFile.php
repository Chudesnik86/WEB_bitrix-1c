<?php
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
global $USER;
if($USER->IsAdmin()){
    $file = $_FILES['file'];
    $res = CFile::SaveFile(
        $file,
        'legacy.settings/'. $file['name']
    );

    echo json_encode(['path' => \CFile::GetPath($res)], JSON_UNESCAPED_UNICODE);
}