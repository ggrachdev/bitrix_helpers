<?php

namespace Fred\Bitrix\Helper\Auth;

use Exception;

class AuthHelper {

    public static function generateNumericPassword(int $max = 10, bool $onlyNumbers = false): string {

        $chars = "123456789";

        $size = strLen($chars) - 1;

        $password = null;

        while ($max--) {
            $password .= strval($chars[rand(0, $size)]);
        }

        return $password;
    }

    public static function generateAlphanumericPassword(int $max = 10): string {

        $chars = "qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP";

        $size = strLen($chars) - 1;

        $password = null;

        while ($max--) {
            $password .= strval($chars[rand(0, $size)]);
        }

        return $password;
    }

    public static function register(array $data, $captcha_word = null, $captcha_sid = null) {
        global $USER;

        $login = trim($data['email']);
        $name = trim($data['name']);
        $password = trim($data['password']);
        $passwordConfirm = trim($data['password_repeat']);
        $email = trim($data['email']);
        $arResult = $USER->Register($login, $name, null, $password, $passwordConfirm, $email, false, $captcha_word, $captcha_sid);

        if (empty($idNewUser)) {
            return $arResult;
        } else {
            $us = new \CUser;

            if (!empty($data['phone'])) {
                $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($data['phone']);
                $fields = [
                    "PERSONAL_PHONE" => $phone
                ];
            } else {
                $fields = [];
            }

            $rsChange = $us->Update($arResult['ID'], $fields);
        }

        return $arResult;
    }

    public static function login(string $login, string $password) {
        global $USER;

        $arAuthResult = $USER->Login(trim($login), trim($password), "Y");

        return empty($arAuthResult['MESSAGE']) ? true : $arAuthResult['MESSAGE'];
    }

    public static function changeEmail($newEmail) {
        global $USER;

        if (self::hasLoginOrEmail(trim($newEmail))) {
            throw new Exception('Нельзя сменить email на этот');
        } else {
            $us = new \CUser;
            $fields = [
                "ACTIVE" => 'N',
                "EMAIL" => trim($newEmail),
                "LOGIN" => trim($newEmail),
                'CONFIRM_CODE' => $confirmCode = randString(8)
            ];

            $rsChange = $us->Update($USER->GetID(), $fields);

            // Отправляем на емейл ссылку с кодом подтверждения
            $arFields = [
                'EMAIL' => $newEmail,
                'USER_ID' => $USER->GetID(),
                'CONFIRM_CODE' => $confirmCode
            ];
            $res = \CEvent::Send("CHANGE_EMAIL", 's1', $arFields);
        }

        return true;
    }

    public static function hasLoginOrEmail(string $email): bool {
        $data = \CUser::GetList(($by = "ID"), ($order = "ASC"),
                [
                    'LOGIN' => $email
                ]
        );

        return $arUser = $data->Fetch() ? true : false;
    }

    public static function hasPhone(string $phone): bool {
        $phone = \Bitrix\Main\UserPhoneAuthTable::normalizePhoneNumber($phone);

        $data = \CUser::GetList(($by = "ID"), ($order = "ASC"),
                [
                    'PERSONAL_PHONE' => $phone
                ]
        );

        return $arUser = $data->Fetch() ? true : false;
    }

    public static function changeData(int $userId, array $arUpdateFields) {
        $us = new \CUser;
        return $rsChange = $us->Update($userId, $arUpdateFields);
    }

    public static function changePassword(string $login, string $newPassword): bool {
        $rsUser = \CUser::GetByLogin($login);
        $arUser = $rsUser->Fetch();

        if (!empty($arUser['ID'])) {
            $objDateTime = new \Bitrix\Main\Type\DateTime();

            $us = new \CUser;
            $fields = [
                "PASSWORD" => trim($newPassword),
                "CONFIRM_PASSWORD" => trim($newPassword),
                'UF_DT_CHANGE_PASS' => $objDateTime->add("1 minutes")->format("d.m.Y H:i:s")
            ];
            $rsChange = $us->Update($arUser['ID'], $fields);

            if ($rsChange != true) {
                return false;
            }
        }

        return !empty($arUser);
    }

}
