<?php

namespace Fred\Bitrix\Helper\Cache;

use \Bitrix\Main\Data\Cache;

/*
 * Класс-обертка над кешем битрикса
 */
class CacheHelper
{
    public static function getCall($key, $callback_cache, $time = (60 * 30))
    {
        $cache = Cache::createInstance();

        if ($cache->initCache($time, $key)) {

            $res = $cache->getVars();

        } elseif ($cache->startDataCache()) {

            $res = $callback_cache();

            if ($res != null) {
                $cache->endDataCache($res);
            }
        }

        return $res;
    }

}