<?php


namespace Legacy\Main;


use Bitrix\Main\Loader;
use Bitrix\Main\UserTable;
use Bitrix\Sale\Fuser;

class User
{
    var $id;
    var $fuid;
    var $firstName;
    var $lastName;
    var $email;

    public function __construct(int $id)
    {
        Loader::includeModule('sale');

        $this->setUserId($id);

        $this->setFUserId(Fuser::getId());

        if ($this->id) {
            $this->fetch();
        }
    }

    public static function getByLogin(string $login):?User
    {
        $user = UserTable::getRow([
            'filter' => [
                '=LOGIN' => $login
            ]
        ]);
        if ($user) {
            return new self($user['ID']);
        }

        return null;
    }

    private function setUserId(?int $id)
    {
        $this->id = $id;
    }

    private function setFUserId(int $id)
    {
        $this->fuid = $id;
    }

    private function fetch()
    {
        $obResult = UserTable::getById($this->id);
        if ($arr = $obResult->fetch()) {
            $this->setFirstName($arr['NAME']);
            $this->setLastName($arr['LAST_NAME']);
            $this->setEmail($arr['EMAIL']);
        }
    }

    public function getId()
    {
        return $this->id;
    }

    public function setFirstName(string $firstName)
    {
        if (empty($firstName)) {
            throw new \Exception('Имя не может быть пустым.');
        }

        $this->firstName = $firstName;
    }

    public function setLastName(string $lastName)
    {
        if (empty($lastName)) {
            throw new \Exception('Имя не может быть пустым.');
        }

        $this->lastName = $lastName;
    }

    public function setEmail(string $email)
    {
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new \Exception('Некорректный email');
        }

        $this->email = $email;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getEmail()
    {
        return $this->email;
    }
}