<?php

namespace Legacy\General;

class DocumentRoot
{
    /**
     * @param string $root_dir_must_contains
     * @return null|string
     */
    public static function get(string $root_dir_must_contains = 'bitrix'):?string
    {
        chdir(__DIR__);
        do {
        $cwd = getcwd();
        $has_bitrix_dir = array_search($root_dir_must_contains, scandir($cwd));
        chdir('..');
        } while ($has_bitrix_dir === false && $cwd != '/');

        if ($has_bitrix_dir === false) {
            return null;
        } else {
            return $cwd;
        }
    }
}