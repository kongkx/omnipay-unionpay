<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\Common\Message\ResponseInterface;
use Omnipay\UnionPay\Common\CertUtil;
use Omnipay\UnionPay\Common\ResponseHelper;

/**
 * Class WtzOpenQueryRequest
 * @package Omnipay\UnionPay\Message
 */
class WtzOpenQueryRequest extends WtzAbstractRequest
{
    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $encryptSensitive = $this->getEncryptSensitive();
        $bizType = $this->getBizType();
        $txnSubType = $this->getTxnSubType();

        $this->validate('orderId', 'txnSubType', 'txnTime');

        switch ($txnSubType) {
            case '00':
                $this->validate('accNo');
                break;
            case '01':
                $this->validate('customerInfo');
                break;
        }


        $data = array(
            'version'       => $this->getVersion(),  //版本号
            'encoding'      => $this->getEncoding(),  //编码方式
            'certId'        => $this->getTheCertId(),    //证书ID
            'signMethod'    => $this->getSignMethod(),  //签名方法
            'txnType'       => '78',        //交易类型
            'txnSubType'    => $this->getTxnSubType(),        //交易子类
            'bizType'       => $bizType,    //业务类型
            'accessType'    => $this->getAccessType(),         //接入类型
            'channelType'   => $this->getChannelType(), //05:语音 07:互联网 08:移动
            'encryptCertId' => CertUtil::readX509CertId($this->getEncryptKey()),
            'merId'         => $this->getMerId(),     //商户代码
            'orderId'       => $this->getOrderId(),     //商户订单号，填写开通并支付交易的orderId
            'txnTime'       => $this->getTxnTime(),    //订单发送时间
        );

        switch ($txnSubType) {
            case '00': // 账号查询
                $data['accNo'] = $encryptSensitive ? $this->encrypt($this->getAccNo()) : $this->getAccNo();
                break;
            case '01': // 手机号查询
                $data['customerInfo'] = $encryptSensitive ?
                    $this->getEncryptCustomerInfo() :
                    $this->getPlainCustomerInfo();
                break;
        }


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
        $data = $this->httpRequest('back', $data);
        return $this->response = new WtzOpenQueryResponse($this, $data);
    }
}
