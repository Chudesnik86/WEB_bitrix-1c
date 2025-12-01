//массив табов
$arTabs = [
    [
        'DIV' => 'edit1', //- code таба
        'TAB' => Loc::getMessage('KUBX_SETTINGS_OPTIONS_TAB_SETTINGS'), //- название таба
    ]
];

//массив групп
$arGroups = [
    'LOGOTYPES' => [ //- code группы
        'TAB' => 'edit1', //- code таба, к которому относится группа
        'TITLE' => 'Логотипы' //- название группы
    ],
    'COLORS' => [
        'TAB' => 'edit1',
        'TITLE' => 'Цветовые решения'
    ]
];

//массив свойств
$arOptions = [
    [
        'GROUP' => 'LOGOTYPES', //- code группы, к которому относится свойство
        'SORT' => '1', //- сортировка свойства
        'NAME' => 'logo', //- code свойства
        'TITLE' => 'Логотип', //- название свойства
        'TYPE' => 'IMAGE', //- тип свойства image
    ],
    [
        'GROUP' => 'COLORS',
        'SORT' => '2',
        'NAME' => 'primary_800',
        'TITLE' => 'Цвет primary_800',
        'TYPE' => 'COLOR',  //- тип свойства color
    ],
    [
        'GROUP' => 'OPTIONS',
        'SORT' => '2',
        'NAME' => 'show_on_main',
        'TITLE' => 'Выводить на главной',
        'TYPE' => 'CHECKBOX', //- тип свойства checkbox
    ],
    [
        'GROUP' => 'OPTIONS',
        'SORT' => '3',
        'NAME' => 'select1',
        'TITLE' => 'Вид каталога',
        'TYPE' => 'SELECT', //- тип свойства select
        'OPTIONS' => [ //- массив значений свойства select
            ['VALUE' => 'list', 'TITLE' => 'Список'],
            ['VALUE' => 'cards', 'TITLE' => 'Карточки']
        ]
    ],
    [
        'GROUP' => 'OPTIONS',
        'SORT' => '5',
        'NAME' => 'mselect',
        'TITLE' => 'Вид каталога - м',
        'TYPE' => 'MSELECT', //- тип свойства multi select
        'OPTIONS' => [ //- массив значений свойства  multi select
            ['VALUE' => 'list', 'TITLE' => 'Список'],
            ['VALUE' => 'cards', 'TITLE' => 'Карточки']
        ]
    ],
    [
        'GROUP' => 'TEXTS',
        'SORT' => '1',
        'NAME' => 'text',
        'TITLE' => 'Текст1',
        'TYPE' => 'TEXT', //- тип свойства text
    ],
    [
        'GROUP' => 'TEXTS',
        'SORT' => '2',
        'NAME' => 'textarea1',
        'TITLE' => 'Мультитекст',
        'TYPE' => 'TEXTAREA', //- тип свойства textarea
        'ROWS_COUNT' => 8 //- высота поля textarea
    ],
];

//создание класса опций
$moduleOptions = new CModuleOptions($module_id, $arTabs, $arGroups, $arOptions);

//Визуальный вывод
$moduleOptions->showOptions();



