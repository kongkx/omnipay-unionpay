<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\UnionPay\Common\DecryptHelper;

/**
 * Class WtzQueryResponse
 * @package Omnipay\UnionPay\Message
 */
class WtzQueryResponse extends AbstractResponse
{
    /**
     * @var WtzRefundRequest
     */
    protected $request;


    public function getCustomerInfo()
    {
        $cert = $this->request->getCertPath();
        $pass = $this->request->getCertPassword();

        return DecryptHelper::decryptCustomerInfo($this->data['customerInfo'], $cert, $pass);
    }
}
