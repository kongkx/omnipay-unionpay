<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\Common\Message\ResponseInterface;
use Omnipay\UnionPay\Common\ResponseHelper;

/**
 * Class ExpressFileTransferRequest
 * @package Omnipay\UnionPay\Message
 */
class ExpressFileTransferRequest extends AbstractRequest
{

    /**
     * Get the raw data array for this message. The format of this varies from gateway to
     * gateway, but will usually be either an associative array, or a SimpleXMLElement.
     *
     * @return mixed
     */
    public function getData()
    {
        $this->validate('txnTime', 'fileType', 'settleDate');

        $data = array(
            'version'    => $this->getVersion(),        //版本号
            'encoding'   => $this->getEncoding(),        //编码方式
            'certId'     => $this->getTheCertId(),    //证书ID
            'txnType'    => '76',        //交易类型
            'signMethod' => $this->getSignMethod(),        //签名方法
            'txnSubType' => '01',        //交易子类
            'bizType'    => '000000',        //业务类型
            'accessType' => '0',        //接入类型
            'merId'      => $this->getMerId(),     //商户代码
            'settleDate' => $this->getSettleDate(),        //清算日期
            'txnTime'    => $this->getTxnTime(),    //订单发送时间
            'fileType'   => $this->getFileType(),        //文件类型
        );

        $data = $this->filter($data);

        $data['signature'] = $this->sign($data, 'RSA2');

        return $data;
    }


    public function getSettleDate()
    {
        return $this->getParameter('settleDate');
    }

    public function setSettleDate($value)
    {
        return $this->setParameter('settleDate', $value);
    }

    public function getFileType()
    {
        return $this->getParameter('fileType');
    }


    public function setFileType($value)
    {
        return $this->setParameter('fileType', $value);
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
        $data = $this->httpRequest('file', $data);
        return $this->response = new ExpressResponse($this, $data);
    }
}
