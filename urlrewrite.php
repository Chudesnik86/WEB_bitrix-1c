<?php
$arUrlRewrite=array (
    0 => array(
        'CONDITION' => '#^/api/(.*)/(.*)/(.*)#',
        'RULE' => 'CLASS=$1&METHOD=$2',
        'ID' => 'legacy:api',
        'PATH' => '/local/api/index.php',
        'SORT' => 100,
    ),

    1 =>
        array(
            'CONDITION' => '#^(.*)#',
            'PATH' => '/app/index.php',
        ),
);
