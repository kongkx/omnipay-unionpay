<?php

namespace Omnipay\UnionPay\Common;

/**
 * String Util for UnionPay
 * Class StringUtil
 * @package Omnipay\UnionPay\Common
 */
class StringUtil
{
    public static function parseFuckStr($str, $data = array())
    {
        preg_match_all('#(?<k>[^=]+)=(?<v>[^&{]+|{[^}]+}|\[[^]]+])&?#', $str, $matches);

        if (empty($matches['k'])) {
            $data['_response'] = (string) $str;
        }

        foreach ($matches['k'] as $i => $key) {
            $data[$key] = $matches['v'][$i];
        }

        return $data;
    }
}
