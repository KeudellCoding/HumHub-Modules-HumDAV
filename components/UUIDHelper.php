<?php

/**
 * HumHub DAV Access
 *
 * @package humhub.modules.humdav
 * @author KeudellCoding
 */

namespace humhub\modules\humdav\components;

class UUIDHelper
{
    public static function generateNewFromStrings(...$params)
    {
        $value = md5(join('-', $params));
        $value = substr_replace($value, '4', 12, 1);

        $value = substr_replace($value, '-', 8, 0);
        $value = substr_replace($value, '-', 13, 0);
        $value = substr_replace($value, '-', 18, 0);
        $value = substr_replace($value, '-', 23, 0);

        return $value;
    }
}
