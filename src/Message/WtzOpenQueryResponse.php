<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\UnionPay\Common\DecryptHelper;

/**
 * Class WtzOpenQueryResponse
 * @package Omnipay\UnionPay\Message
 */
class WtzOpenQueryResponse extends AbstractResponse
{
    public function isSuccessful()
    {
        return isset($this->data['respCode']) && $this->data['respCode'] == '00' && $this->data['verify_success'];
    }


    public function getActivateStatus()
    {
        return $this->data['activateStatus'];
    }

    public function getPayCardType()
    {
        return $this->data['payCardType'];
    }

    public function getTokenPayData()
    {
        $tokenPayData = $this->data['tokenPayData'];

        $str = substr($tokenPayData, 1, -1);
        parse_str($str, $output);

        return $output;
    }
}
