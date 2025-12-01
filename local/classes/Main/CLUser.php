<?php

namespace Legacy\Main;

use Legacy\General\Constants;
use \Bitrix\Main as BMain;
use Legacy\General\SmsRu;
use Legacy\HighLoadBlock\Entity;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\UserTable;
use Legacy\Main\Security\Mfa\TotpAlgorithm;

class CLUser extends \CUser
{
    protected static $digits = 4;

    public function Register($USER_LOGIN, $USER_NAME, $USER_LAST_NAME, $USER_PASSWORD, $USER_CONFIRM_PASSWORD, $USER_EMAIL, $SITE_ID = false, $captcha_word = "", $captcha_sid = 0, $bSkipConfirm = false, $USER_PHONE_NUMBER = "")
    {
        /**
         * @global CMain $APPLICATION
         * @global CUserTypeManager $USER_FIELD_MANAGER
         */
        global $APPLICATION, $DB, $USER_FIELD_MANAGER;

        $APPLICATION->ResetException();
        if(defined("ADMIN_SECTION") && ADMIN_SECTION===true && $SITE_ID!==false)
        {
            $APPLICATION->ThrowException(GetMessage("MAIN_FUNCTION_REGISTER_NA_INADMIN"));
            return array("MESSAGE"=>GetMessage("MAIN_FUNCTION_REGISTER_NA_INADMIN"), "TYPE"=>"ERROR");
        }

        $strError = "";

        if (\COption::GetOptionString("main", "captcha_registration", "N") == "Y")
        {
            if (!($APPLICATION->CaptchaCheckCode($captcha_word, $captcha_sid)))
            {
                $strError .= GetMessage("MAIN_FUNCTION_REGISTER_CAPTCHA")."<br>";
            }
        }

        if($strError)
        {
            if(\COption::GetOptionString("main", "event_log_register_fail", "N") === "Y")
            {
                \CEventLog::Log("SECURITY", "USER_REGISTER_FAIL", "main", false, $strError);
            }

            $APPLICATION->ThrowException($strError);
            return array("MESSAGE"=>$strError, "TYPE"=>"ERROR");
        }

        if($SITE_ID === false)
            $SITE_ID = SITE_ID;

        $bConfirmReq = !$bSkipConfirm && (\COption::GetOptionString("main", "new_user_registration_email_confirmation", "N") == "Y"
                && \COption::GetOptionString("main", "new_user_email_required", "Y") <> "N");

        $phoneRegistration = (\COption::GetOptionString("main", "new_user_phone_auth", "N") == "Y");
        $phoneRequired = ($phoneRegistration && \COption::GetOptionString("main", "new_user_phone_required", "N") == "Y");

        $checkword = md5(\CMain::GetServerUniqID().uniqid());
        $active = ($bConfirmReq || $phoneRequired? "N": "Y");

        $arFields = array(
            "LOGIN" => $USER_LOGIN,
            "NAME" => $USER_NAME,
            "LAST_NAME" => $USER_LAST_NAME,
            "PASSWORD" => $USER_PASSWORD,
            "CONFIRM_PASSWORD" => $USER_CONFIRM_PASSWORD,
            "CHECKWORD" => $checkword,
            "~CHECKWORD_TIME" => $DB->CurrentTimeFunction(),
            "EMAIL" => $USER_EMAIL,
            "PHONE_NUMBER" => $USER_PHONE_NUMBER,
            "ACTIVE" => $active,
            "CONFIRM_CODE" => ($bConfirmReq? randString(8): ""),
            "SITE_ID" => $SITE_ID,
            "LANGUAGE_ID" => LANGUAGE_ID,
            "USER_IP" => $_SERVER["REMOTE_ADDR"],
            "USER_HOST" => @gethostbyaddr($_SERVER["REMOTE_ADDR"]),
        );
        $USER_FIELD_MANAGER->EditFormAddFields("USER", $arFields);

        $def_group = \COption::GetOptionString("main", "new_user_registration_def_group", "");
        if($def_group!="")
            $arFields["GROUP_ID"] = array_merge($arFields["GROUP_ID"], explode(",", $def_group));

        $bOk = true;
        $result_message = true;
        foreach(GetModuleEvents("main", "OnBeforeUserRegister", true) as $arEvent)
        {
            if(ExecuteModuleEventEx($arEvent, array(&$arFields)) === false)
            {
                if($err = $APPLICATION->GetException())
                {
                    $result_message = array("MESSAGE"=>$err->GetString()."<br>", "TYPE"=>"ERROR");
                }
                else
                {
                    $APPLICATION->ThrowException("Unknown error");
                    $result_message = array("MESSAGE"=>"Unknown error"."<br>", "TYPE"=>"ERROR");
                }

                $bOk = false;
                break;
            }
        }

        $ID = false;
        $phoneReg = false;
        if($bOk)
        {
            if($arFields["SITE_ID"] === false) {
                $arFields["SITE_ID"] = \CSite::GetDefSite();
            }
            $arFields["LID"] = $arFields["SITE_ID"];

            if($ID = $this->Add($arFields)) {
                if($phoneRegistration && $arFields["PHONE_NUMBER"] <> '') {
                    $phoneReg = true;

                    $arElementFields = array(
                        'UF_PHONE_NUMBER' => $USER_PHONE_NUMBER,
                        'UF_USER_ID' => $ID,
                        'UF_CONFIRMED' => 'N'
                    );
                    Entity::getInstance()->add(Constants::HLBLOCK_EMAIL_CODES, $arElementFields);

                    $code = CLUser::GeneratePhoneCode($USER_PHONE_NUMBER);
//                    $sms = new SmsRu('SMS_NEW_USER_CONFIRM_NUMBER', [
//                        'PHONE' => $USER_PHONE_NUMBER,
//                        'CODE' => $code,
//                    ]);
//                    $sms->setSite($SITE_ID);
//                    $sms->setLanguage('ru');
//                    $obResult = $sms->send();
//
//                    if($obResult->isSuccess()) {
//                        $result_message = array(
//                            "MESSAGE" => GetMessage("main_register_sms_sent"),
//                            "TYPE" => "OK",
//                            "ID" => $ID,
//                        );
//                    } else {
//                        $result_message = array(
//                            "MESSAGE" => implode('. ', $obResult->getErrorMessages()),
//                            "TYPE" => "ERROR",
//                            "ID" => $ID,
//                        );
//                    }
                } else {
                    $result_message = array(
                        "MESSAGE" => GetMessage("USER_REGISTER_OK"),
                        "TYPE" => "OK",
                        "ID" => $ID
                    );
                }

                $arFields["USER_ID"] = $ID;
                $arEventFields = $arFields;
                unset($arEventFields["PASSWORD"]);
                unset($arEventFields["CONFIRM_PASSWORD"]);
                unset($arEventFields["~CHECKWORD_TIME"]);

                $event = new \CEvent;
                $event->SendImmediate("NEW_USER", $arEventFields["SITE_ID"], $arEventFields);
                if($bConfirmReq) {
                    $event->SendImmediate("NEW_USER_CONFIRM", $arEventFields["SITE_ID"], $arEventFields);
                }
            } else {
                $APPLICATION->ThrowException($this->LAST_ERROR);
                $result_message = array("MESSAGE"=>$this->LAST_ERROR, "TYPE"=>"ERROR");
            }
        }

        if(is_array($result_message))
        {
            if($result_message["TYPE"] == "OK")
            {
                if(\COption::GetOptionString("main", "event_log_register", "N") === "Y")
                {
                    $res_log["user"] = ($USER_NAME != "" || $USER_LAST_NAME != "") ? trim($USER_NAME." ".$USER_LAST_NAME) : $USER_LOGIN;
                    \CEventLog::Log("SECURITY", "USER_REGISTER", "main", $ID, serialize($res_log));
                }
            }
            else
            {
                if(\COption::GetOptionString("main", "event_log_register_fail", "N") === "Y")
                {
                    \CEventLog::Log("SECURITY", "USER_REGISTER_FAIL", "main", $ID, $result_message["MESSAGE"]);
                }
            }
        }

        //authorize succesfully registered user, except email or phone confirmation is required
        $isAuthorize = false;
        if($ID !== false && $arFields["ACTIVE"] === "Y" && $phoneReg === false)
        {
            $isAuthorize = $this->Authorize($ID);
        }

        $agreementId = intval(\COption::getOptionString("main", "new_user_agreement", ""));
        if ($agreementId && $isAuthorize)
        {
            $agreementObject = new \Bitrix\Main\UserConsent\Agreement($agreementId);
            if ($agreementObject->isExist() && $agreementObject->isActive() && $_REQUEST["USER_AGREEMENT"] == "Y")
            {
                \Bitrix\Main\UserConsent\Consent::addByContext($agreementId, "main/reg", "register");
            }
        }

        $arFields["RESULT_MESSAGE"] = $result_message;
        foreach (GetModuleEvents("main", "OnAfterUserRegister", true) as $arEvent)
            ExecuteModuleEventEx($arEvent, array(&$arFields));

        return $arFields["RESULT_MESSAGE"];
    }

    public static function GeneratePhoneCode($phone)
    {
        $row = Entity::getInstance()->getRow(Constants::HLBLOCK_EMAIL_CODES, [
            'filter' => [
                'UF_PHONE_NUMBER' => $phone
            ]
        ]);

//        $code = rand(1001, 9999);
        $code = 7700;
        $currentDateTime = new DateTime();

        $arElementFields = array(
            'UF_CODE' => md5($code),
            'UF_ATTEMPTS' => 0,
            'UF_DATETIME_SEND' => $currentDateTime->getTimestamp()
        );
        Entity::getInstance()->update(Constants::HLBLOCK_EMAIL_CODES, $row['ID'], $arElementFields);
        return $code;
    }

    public static function VerifyPhoneCode($phoneNumber, $code)
    {
        if(empty($code)) {
            return false;
        }

        $phoneNumber = BMain\UserPhoneAuthTable::normalizePhoneNumber($phoneNumber);
        $row = Entity::getInstance()->getRow(Constants::HLBLOCK_EMAIL_CODES, [
            'filter' => ['UF_PHONE_NUMBER' => $phoneNumber]
        ]);
        if ($row) {
            $isVerified = false;

            if ($row['UF_ATTEMPTS'] >= 3) {
                throw new \Exception('Количество попыток исчерпано');
            }

            $fields = [];
            if ($row['UF_CODE'] == md5($code)) {
                if ($row['UF_CONFIRMED'] != 'Y') {
                    $fields['UF_CONFIRMED'] = 'Y';
                }
                $fields['UF_DATETIME_SEND'] = '';
                $isVerified = true;
            } else {
                $atemptsCount = (int)$row['UF_ATTEMPTS'] + 1;
                $fields['UF_ATTEMPTS'] = $atemptsCount;
            }

            if (!empty($fields)) {
                Entity::getInstance()->update(Constants::HLBLOCK_EMAIL_CODES, $row['ID'], $fields);
            }
            if ($isVerified) {
                return $row['UF_USER_ID'];
            } elseif($atemptsCount == 3) {
                throw new \Exception('Неверный код! Количество попыток исчерпано');
            } else {
                throw new \Exception('Неверный код! Попробуйте еще раз (осталось попыток: '.(3 - $atemptsCount).')');
            }
        }
        return false;
    }
}