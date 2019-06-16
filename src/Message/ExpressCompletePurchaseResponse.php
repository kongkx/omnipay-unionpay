<?php

namespace Omnipay\UnionPay\Message;

/**
 * Class ExpressCompletePurchaseResponse
 * @package Omnipay\UnionPay\Message
 */
class ExpressCompletePurchaseResponse extends AbstractResponse
{
    public function isPaid()
    {
        return $this->isSuccessful() && $this->data['respCode'] == '00';
    }


    /**
     * Is the response successful?
     *
     * @return boolean
     */
    public function isSuccessful()
    {
        return $this->data['verify_success'];
    }
}
