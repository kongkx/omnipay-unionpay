<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\Common\Message\ResponseInterface;
use Omnipay\UnionPay\Common\ResponseHelper;

/**
 * Class ExpressQueryRequest
 * @package Omnipay\UnionPay\Message
 */
class ExpressQueryRequest extends AbstractRequest
{

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $this->validate('orderId', 'txnTime');

        $data = array(
            'version'     => $this->getVersion(),
            'encoding'    => $this->getEncoding(),
            'certId'      => $this->getTheCertId(),
            'signMethod'  => $this->getSignMethod(),
            'txnType'     => '00',
            'txnSubType'  => '00',
            'bizType'     => $this->getBizType(),
            'accessType'  => $this->getAccessType(),
            'channelType' => $this->getChannelType(),
            'orderId'     => $this->getOrderId(),
            'merId'       => $this->getMerId(),
            'txnTime'     => $this->getTxnTime(),
        );

        $data = $this->filter($data);

        $data['signature'] = $this->sign($data, 'RSA2');

        return $data;
    }


    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return ResponseInterface
     */
    public function sendData($data)
    {
        $data = $this->httpRequest('query', $data);
        return $this->response = new ExpressResponse($this, $data);
    }
}
