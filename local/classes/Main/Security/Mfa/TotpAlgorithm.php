<?php


namespace Legacy\Main\Security\Mfa;


class TotpAlgorithm extends \Bitrix\Main\Security\Mfa\TotpAlgorithm
{
    public function setDigits($digits)
    {
        $digits = intval($digits);
        if ($digits > 0) {
            $this->digits = $digits;
        }
    }
}