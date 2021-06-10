<?php

/**
 * Description of IBlockHelper
 *
 * @author ggrachdev
 */
class IBlockHelper {

    // Получить данные об инфоблоке
    public static function getIblockDataById(int $iblockId, array $select = ['ID', 'CODE']) {

        self::includeIblockModule();

        $query = new Query(\Bitrix\Iblock\IblockTable::getEntity());

        return $query
                ->setSelect($select)
                ->setFilter(["ID" => $iblockId])
                ->exec()
                ->fetch();
    }

    // Получить элементы инфоблока
    public static function getIblockElements(int $iblockId, array $select = ['ID', 'CODE'], array $filter = ["ACTIVE" => "Y"]) {

        $arReturn = [];

        $filter['IBLOCK_ID'] = $iblockId;

        if (self::includeModules()) {

            $arFilter = $filter;
            $arSelect = $select;
            $res = \CIBlockElement::GetList([], $arFilter, false, [], $arSelect);

            while ($ob = $res->fetch()) {
                $arReturn[] = $ob;
            }
        }

        return $arReturn;
    }

    // Получить секции инфоблока
    public static function getIblockSections(int $iblockId, array $select = ['ID', 'CODE'], array $filter = ["ACTIVE" => "Y"]) {
        $arReturn = [];

        $filter['IBLOCK_ID'] = $iblockId;

        if (self::includeModules()) {

            $arFilter = $filter;
            $arSelect = $select;
            $res = \CIBlockSection::GetList([], $arFilter, false, $arSelect, []);

            while ($ob = $res->fetch()) {
                $arReturn[] = $ob;
            }
        }

        return $arReturn;
    }

    // Получить данные элемента инфоблока
    public static function getIblockElement(int $iblockId, $elemId, array $select = ['ID', 'CODE'], array $filter = ["ACTIVE" => "Y"]) {

        $arReturn = [];

        $filter['IBLOCK_ID'] = $iblockId;

        if (is_array($elemId))
            $filter['=ID'] = $elemId;
        else
            $filter['ID'] = $elemId;

        if (self::includeModules()) {

            $arFilter = $filter;
            $arSelect = $select;
            $res = \CIBlockElement::GetList([], $arFilter, false, [], $arSelect);

            while ($ob = $res->fetch()) {
                $arReturn[] = $ob;
            }
        }

        return $arReturn;
    }

    /**
     * @throws Exception
     */
    private static function includeModules() {
        if (!Loader::includeModule('iblock')) {
            throw new Exception('Not found modules');
        }
    }

}
