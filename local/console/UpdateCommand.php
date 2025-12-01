<?php
namespace Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

use Nette\PhpGenerator\PhpNamespace;
use Bitrix\Main\Entity;
use \Bitrix\Main\Loader;
use Bitrix\Highloadblock\HighloadBlockTable;

class UpdateCommand extends SymfonyCommand
{
    public function configure()
    {
        $this->setName('constants')->setDescription('Создает класс \Legacy\General\Constants с константами проекта.');
    }

    private function getEntities()
    {
        try {
            $result = [];

            $result[] = [
                'MODULE_NAME' => 'iblock',
                'PREFIX' => 'IB',
                'ENTITY' => 'Bitrix\Iblock\IblockTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'CODE'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'iblock',
                'PREFIX' => 'IB_PROP',
                'ENTITY' => 'Bitrix\Iblock\PropertyTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'CODE',
                    'VAR_NAME' => 'CONCAT(%s, "_", %s)',
                    'VAR_BUILD_FROM' => ['IBLOCK.CODE', 'CODE'],
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'main',
                'PREFIX' => 'GROUP',
                'ENTITY' => 'Bitrix\Main\GroupTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'STRING_ID'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'highloadblock',
                'PREFIX' => 'HLBLOCK',
                'ENTITY' => 'Bitrix\Highloadblock\HighloadBlockTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'TABLE_NAME'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'sale',
                'PREFIX' => 'DELIVERY',
                'ENTITY' => 'Bitrix\Sale\Delivery\Services\Table',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'NAME'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'sale',
                'PREFIX' => 'PERSON_TYPE_',
                'ENTITY' => 'Bitrix\Sale\Internals\PersonTypeTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'CODE'
                ],
                'FILTER' => [
                    'LID' => 's1'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'sale',
                'PREFIX' => 'PAY_SYSTEM_',
                'ENTITY' => 'Bitrix\Sale\Internals\PaySystemActionTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'CODE'
                ],
                'FILTER' => [
                    'ACTIVE' => 'Y',
                    'ENTITY_REGISTRY_TYPE' => 'ORDER'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'form',
                'PREFIX' => 'WEBFORM',
                'ENTITY' => 'Legacy\Form\FormTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'SID'
                ]
            ];

            $result[] = [
                'MODULE_NAME' => 'catalog',
                'PREFIX' => 'CATALOG_GROUP_',
                'ENTITY' => 'Bitrix\Catalog\GroupTable',
                'PARAMS' => [
                    'NAME' => 'NAME',
                    'CODE' => 'NAME'
                ]
            ];

            return $result;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    private function getDataFromTable($moduleName, $className, $prefix, $params, $filter = [])
    {
        try {
            $result = [];
            if (strlen($moduleName) > 0 && Loader::includeModule($moduleName)) {
                $prefixCode = strtoupper($prefix).'_';
                $filter = array_merge($filter, ['!='.$params['CODE'] => null]);
                $db = $className::getList([
                    'select' => ['LEGACY_CONSTANT_VALUE' => 'ID', 'LEGACY_CONSTANT_ALIAS', 'LEGACY_CONSTANT_DESCRIPTION'],
                    'filter' => $filter,
                    'runtime' => [
                        new Entity\ExpressionField('LEGACY_CONSTANT_ALIAS', 'CONCAT("'.$prefixCode.'", UPPER('.($params['VAR_NAME'] ?? $params['CODE']).'))', $params['VAR_BUILD_FROM']),
                        new Entity\ExpressionField('LEGACY_CONSTANT_DESCRIPTION', 'UPPER(%s)', $params['NAME']),
                    ]
                ]);
                $result = $db->fetchAll();
                $arParams = [
                    "replace_space" => "_",
                    "replace_other" => "_",
                    "change_case"   => 'U'
                ];
                foreach ($result as &$item) {
                    $item['LEGACY_CONSTANT_ALIAS'] = \CUtil::translit($item['LEGACY_CONSTANT_ALIAS'], 'ru', $arParams);
                }
            }
            return $result;
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    function generateClass($CLASS_NAME, $NAMESPACE, $DIR = '')
    {
        $namespace = new PhpNamespace($NAMESPACE);

        $class = $namespace->addClass($CLASS_NAME);

        $entities = $this->getEntities();
        foreach ($entities as $entity) {
            $data = $this->getDataFromTable($entity['MODULE_NAME'], $entity['ENTITY'], $entity['PREFIX'], $entity['PARAMS'], $entity['FILTER'] ?? []);
            foreach ($data as $item) {
                $constant = $class->addConstant($item['LEGACY_CONSTANT_ALIAS'], $item['LEGACY_CONSTANT_VALUE']);
                $constant->addComment($item['LEGACY_CONSTANT_DESCRIPTION']);
            }
        }

        $namespace = '<?php'.PHP_EOL.$namespace;

        if (empty($DIR)) {
            $dir = __DIR__;
        } else {
            $dir = $DIR;
        }
        if (!is_dir($dir)) {
            mkdir($dir, BX_DIR_PERMISSIONS, true);
        }
        $handle = fopen($dir.DIRECTORY_SEPARATOR.$CLASS_NAME.'.php', 'w');
        fwrite($handle, $namespace);
        fclose($handle);
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->generateClass('Constants', 'Legacy\General', $_SERVER['DOCUMENT_ROOT'].'/local/classes/General');

        return 0;
    }
}