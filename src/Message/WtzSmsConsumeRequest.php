<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\Common\Message\ResponseInterface;
use Omnipay\UnionPay\Common\CertUtil;
use Omnipay\UnionPay\Common\ResponseHelper;

/**
 * Class WtzSmsConsumeRequest
 * @package Omnipay\UnionPay\Message
 */
class WtzSmsConsumeRequest extends WtzAbstractRequest
{
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $bizType = $this->getBizType();
        $encryptSensitive = $this->getEncryptSensitive();

        $this->validate('orderId', 'txnTime', 'txnAmt');

        switch ($bizType) {
            case '000301':
                $this->validate('accNo', 'customerInfo');
                break;
            case '000902':
                $this->validate('token', 'trId');
                break;
        }

        $data = array(
            'version'       => $this->getVersion(),  //版本号
            'encoding'      => $this->getEncoding(),  //编码方式
            'certId'        => $this->getTheCertId(),    //证书ID
            'signMethod'    => $this->getSignMethod(),  //签名方法
            'txnType'       => '77',        //交易类型
            'txnSubType'    => '02',        //交易子类
            'bizType'       => $bizType,    //业务类型
            'accessType'    => $this->getAccessType(),         //接入类型
            'channelType'   => $this->getChannelType(), //05:语音 07:互联网 08:移动
            'encryptCertId' => CertUtil::readX509CertId($this->getEncryptKey()),
            'merId'         => $this->getMerId(),     //商户代码
            'orderId'       => $this->getOrderId(),     //商户订单号，填写开通并支付交易的orderId
            'txnTime'       => $this->getTxnTime(),    //订单发送时间
            'txnAmt'        => $this->getTxnAmt(),    //交易金额，单位分
            'currencyCode'  => $this->getCurrencyCode(),
        );

        switch ($bizType) {
            case '000301':
                $data['accNo'] = $encryptSensitive ? $this->encrypt($this->getAccNo()) : $this->getAccNo();
                $data['customerInfo'] = $encryptSensitive ?
                    $this->getEncryptCustomerInfo() :
                    $this->getPlainCustomerInfo();
                break;
            case '000902':
                $data['tokenPayData'] = sprintf('{trId=%s&token=%s}', $this->getTrId(), $this->getToken());
                break;
        }



        $data = $this->filter($data);

        $data['signature'] = $this->sign($data, 'RSA2');

        return $data;
    }


    /**
     * @return mixed
     */
    public function getTrId()
    {
        return $this->getParameter('trId');
    }


    /**
     * @param $value
     *
     * @return $this
     */
    public function setTrId($value)
    {
        return $this->setParameter('trId', $value);
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
        $data = $this->httpRequest('back', $data);
        return $this->response = new WtzSmsConsumeResponse($this, $data);
    }
}
