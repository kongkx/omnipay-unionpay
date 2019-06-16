<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\UnionPay\Common\DecryptHelper;

/**
 * Class WtzOpenResponse
 * @package Omnipay\UnionPay\Message
 */
class WtzOpenResponse extends AbstractResponse
{
    /**
     * @var WtzAbstractRequest
     */
    protected $request;



    public function getAccNo()
    {
        return $this->decrypt($this->data['accNo']);
    }

    public function getTokenPayData()
    {
        if (array_key_exists('tokenPayData', $this->data)) {
            return parse_str(substr($this->data['tokenPayData'], 1. -1));
        }
        return null;
    }



    public function getOrderId()
    {
        return $this->data['orderId'];
    }
}
