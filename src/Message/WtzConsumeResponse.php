<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\UnionPay\Common\DecryptHelper;

/**
 * Class WtzConsumeResponse
 * @package Omnipay\UnionPay\Message
 */
class WtzConsumeResponse extends AbstractResponse
{
    /**
     * @var WtzSmsConsumeRequest
     */
    protected $request;


    public function getCustomerInfo()
    {
        $cert = $this->request->getCertPath();
        $pass = $this->request->getCertPassword();

        return DecryptHelper::decryptCustomerInfo($this->data['customerInfo'], $cert, $pass);
    }
}
