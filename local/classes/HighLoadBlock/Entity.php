<?php

namespace Legacy\HighLoadBlock;

use Bitrix\Main\Loader;
use Bitrix\Highloadblock as HL;
use Bitrix\Highloadblock\HighloadBlockTable;
use Bitrix\Main\CDBResult;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\Entity\Base;
use Bitrix\Main\ORM\Query\Result;

class Entity
{
    public $LAST_SQL;

    protected static $_instance;

    /**
     * Entity constructor.
     * @throws \Bitrix\Main\LoaderException
     */
    private function __construct()
    {
        if (!Loader::includeModule('highloadblock')) {
            throw new \Exception('Highloadblock is not found');
        }
    }

    public static function getInstance():Entity
    {
        if (self::$_instance === null) {
            self::$_instance = new self;
        }

        return self::$_instance;
    }

    private function __clone()
    {
    }

    public function getId(string $tableName):?int
    {
        $result = null;

        $row = HighloadBlockTable::getRow([
            'select' => [
                'ID'
            ],
            'filter' => [
                'TABLE_NAME' => $tableName
            ],
            'cache' => [
                'ttl' => 86400
            ],
        ]);

        if ($row) {
            $result = intval($row['ID']);
        }

        return $result;
    }

    /**
     * @param int $id
     * @param string $method
     * @param mixed ...$params
     * @return int|array|bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function invoke(int $id, string $method, ...$params)
    {
        if ($id > 0 && strlen($method) > 0 && !empty($params)) {
            $entity = $this->getEntity($id);
            $entity_data_class = $entity->getDataClass();

            list($first, $second) = $params;
            $obResult = call_user_func([$entity_data_class, $method], $first, $second);

            if ($method != 'getList') {
                $entity->cleanCache();
            } else {
                return $obResult->fetchAll();
            }

            if ($obResult->isSuccess() && $method == 'add') {
                return $obResult->getId();
            } elseif ($method == 'add') {
              throw new \Exception(implode('. ', $obResult->getErrorMessages()));
            } else {
                return $obResult->isSuccess();
            }
        } else {
            throw new \Exception('Ошибка проверки ID или PARAMS');
        }
    }

    /**
     * @param int $id
     * @return DataManager|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    private function getEntity(int $id):?Base
    {
        $hlblock = HL\HighloadBlockTable::getById($id)->fetch();
        if ($hlblock) {
            $entity = HL\HighloadBlockTable::compileEntity($hlblock);
            return $entity;
        }
        return null;
    }

    /**
     * @param int $id
     * @return \Bitrix\Main\Entity\DataManager|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getDataClass(int $id):?string
    {
        $entity = $this->getEntity($id);
        if ($entity) {
            return $entity->getDataClass();
        }
        return null;
    }

    /**
     * @param $id
     * @return array|bool
     */
    public function getFields($id)
    {
        if ($id > 0) {
            $arFields = array(
                'ID' => array(
                    'EDIT_FORM_LABEL' => 'Ид',
                    'LIST_COLUMN_LABEL' => 'Ид',
                    'LIST_FILTER_LABEL' => 'Ид'
                )
            );
            $arFields = array_merge(
                $arFields,
                $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_'.$id, 0, LANGUAGE_ID)
            );
            return $arFields;
        }

        return false;
    }

    /**
     * @param $id
     * @param $params
     * @return array|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getList($id, $params):?array
    {
        if (!isset($params['cache'])) {
            $params['cache'] = [
                'ttl' => 86400
            ];
        }
        $result = $this->invoke($id, 'getList', $params);
        if (!empty($result)) {
            return $result;
        }
        return null;
    }

    /**
     * @param $id
     * @param $params
     * @return array|null
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function getRow($id, $params):?array
    {
        $params['limit'] = 1;
        $row = $this->getList($id, $params);
        if ($row) {
            return current($row);
        }
        return null;
    }

    public function getValue($id, $code, $params)
    {
        $row = $this->getRow($id, $params);
        if ($row && $row[$code]) {
            return $row[$code];
        }
        return null;
    }

    /**
     * @param int $id
     * @param array $params
     * @return int
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function add(int $id, array $params):int
    {
        return $this->invoke($id, 'add', $params);
    }

    /**
     * @param $id
     * @param $element_id
     * @param $params
     * @return bool
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function update(int $id, int $element_id, array $params):bool
    {
        return $this->invoke($id, 'update', $element_id, $params);
    }

    /**
     * @param $id
     * @param $element_id
     * @return mixed
     * @throws \Bitrix\Main\ArgumentException
     * @throws \Bitrix\Main\ObjectPropertyException
     * @throws \Bitrix\Main\SystemException
     */
    public function delete(int $id, int $element_id):bool
    {
        return $this->invoke($id, 'delete', $element_id);
    }
}