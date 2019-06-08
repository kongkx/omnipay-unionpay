<?php

namespace Omnipay\UnionPay\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\UnionPay\WtzGateway;

class WtzTokenGatewayTest extends GatewayTestCase
{

    /**
     * @var WtzGateway $gateway
     */
    protected $gateway;

    protected $options;


    public function setUp()
    {
        parent::setUp();
        $this->gateway = Omnipay::create('UnionPay_Wtz');
        $this->gateway->setMerId(UNIONPAY_WTZ_MER_ID);
        $this->gateway->setEncryptSensitive(true);
        $this->gateway->setBizType('000902');
        $this->gateway->setEncryptCert(UNIONPAY_TWZ_ENCRYPT_CERT);
        $this->gateway->setMiddleCert(UNIONPAY_TWZ_MIDDLE_CERT);
        $this->gateway->setRootCert(UNIONPAY_TWZ_ROOT_CERT);
        $this->gateway->setCertPath(UNIONPAY_TWZ_SIGN_CERT);
        $this->gateway->setCertPassword(UNIONPAY_CERT_PASSWORD);
        $this->gateway->setReturnUrl('http://example.com/return');
        $this->gateway->setNotifyUrl('http://example.com/notify');

        // 获取 token 需要在银联侧开通，options 为已完成 FrontOpen 的订单数据。
        $this->options = [
            'orderId' => '20190608021356',
            'txnTime' => '20190608021356',
        ];
    }


    private function open($content)
    {
        $file = sprintf('./%s.html', md5(uniqid()));
        $fh = fopen($file, 'w');
        fwrite($fh, $content);
        fclose($fh);

        exec(sprintf('open %s -a "/Applications/Google Chrome.app" && sleep 5 && rm %s', $file, $file));
    }


    public function testFrontOpenConsume()
    {
        date_default_timezone_set('PRC');

        $orderId = date('YmdHis');

        $params = array(
            'orderId'      => $orderId,
            'txnTime'      => date('YmdHis'),
            'txnAmt'       => '100',
            'trId'         => '99988877766',
            'accNo'        => '6226090000000048',
            'payTimeout'   => date('YmdHis', strtotime('+15 minutes')),
            'customerInfo' => array(
                'phoneNo'    => '18100000000', //Phone Number
                'certifTp'   => '01', //ID Card
                'certifId'   => '510265790128303', //ID Card Number
                'customerNm' => '张三', // Name
                //'cvn2'       => '248', //cvn2
                //'expired'    => '1912', // format YYMM
            ),
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzFrontOpenConsumeResponse $response
         */
        $response = $this->gateway->frontOpenConsume($params)->send();
        $this->assertTrue($response->isSuccessful());
        $form = $response->getRedirectForm();
        $this->open($form);
    }


    public function testFrontOpen()
    {
        date_default_timezone_set('PRC');

        $orderId = date('YmdHis');

        $params = array(
            'orderId'    => $orderId,
            'txnTime'    => date('YmdHis'),
            'trId'       => '99988877766',
            'accNo'      => '6226090000000048',
            'payTimeout' => date('YmdHis', strtotime('+15 minutes'))
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzFrontOpenResponse $response
         */
        $response = $this->gateway->frontOpen($params)->send();
        $this->assertTrue($response->isSuccessful());
        $form = $response->getRedirectForm();
        $this->open($form);
    }


    public function testBackOpen()
    {
        date_default_timezone_set('PRC');

        $params = array(
            'orderId'      => date('YmdHis'),
            'txnTime'      => date('YmdHis'),
            'trId'         => '62000000001',
            'accNo'        => '6226090000000048',
            'customerInfo' => array(
                'phoneNo'    => '18100000000', //Phone Number
                'certifTp'   => '01', //ID Card
                'certifId'   => '510265790128303', //ID Card Number
                'customerNm' => '张三', // Name
                //'cvn2'       => '248', //cvn2
                //'expired'    => '1912', // format YYMM
            ),
            'payTimeout'   => date('YmdHis', strtotime('+15 minutes'))
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzFrontOpenResponse $response
         */
        $response = $this->gateway->backOpen($params)->send();
        $this->assertTrue($response->isSuccessful());
    }


    public function testSmsOpen()
    {
        date_default_timezone_set('PRC');

        $params = array(
            'orderId'      => date('YmdHis'),
            'txnTime'      => date('YmdHis'),
            'trId'         => '62000000001',
            'accNo'        => '6226090000000048',
            'customerInfo' => array(
                'phoneNo'    => '18100000000', //Phone Number
                'certifTp'   => '01', //ID Card
                'certifId'   => '510265790128303', //ID Card Number
                'customerNm' => '张三', // Name
                //'cvn2'       => '248', //cvn2
                //'expired'    => '1912', // format YYMM
            ),
            'payTimeout'   => date('YmdHis', strtotime('+15 minutes'))
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzSmsOpenResponse $response
         */
        $response = $this->gateway->smsOpen($params)->send();
        $this->assertTrue($response->isSuccessful());
    }


    public function testCompleteFrontOpen()
    {
        parse_str(file_get_contents(UNIONPAY_DATA_DIR . '/WtzCompleteFrontOpen.txt'), $data);

        /**
         * @var \Omnipay\UnionPay\Message\WtzCompleteFrontOpenResponse $response
         */
        $response = $this->gateway->completeFrontOpen(array('request_params' => $data))->send();
        $this->assertFalse($response->isSuccessful());
    }


    public function testOpenQuery()
    {
        $params = array(
            'txnSubType' => '02',
            'orderId' => $this->options['orderId'],
            'txnTime' => $this->options['txnTime'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzOpenQueryResponse $response
         */
        $response = $this->gateway->openQuery($params)->send();
        $this->assertTrue($response->isSuccessful());

        return [
            'params' => $params,
            'response' => $response->getData(),
            'tokenPayData' => $response->getTokenPayData(),
        ];
    }

    /**
     * @depends testOpenQuery
     */
    public function testSmsConsume($preData)
    {
        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'txnAmt'  => 100,
            'trId'    => '99988877766',
            'token'   => $preData['tokenPayData']['token'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzSmsConsumeResponse $response
         */
        $response = $this->gateway->smsConsume($params)->send();
        $this->assertFalse($response->isSuccessful());
    }

    /**
     * @depends testOpenQuery
     */
    public function testConsume($preData)
    {
        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'txnAmt'  => 100,
            'trId'    => '62000000001',
            'token'   => $preData['tokenPayData']['token'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzConsumeResponse $response
         */
        $response = $this->gateway->consume($params)->send();
        $this->assertFalse($response->isSuccessful());

        return [
            'params' => $params,
            'response' => $response->getData(),
        ];

    }

    /**
     * @depends testConsume
     */
    public function testQuery($consumeData)
    {
        $params = array(
            'orderId' => $consumeData['params']['orderId'],
            'txnTime' => $consumeData['params']['txnTime'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzQueryResponse $response
         */
        $response = $this->gateway->query($params)->send();
        $this->assertFalse($response->isSuccessful());
    }



    /**
     * @depends testOpenQuery
     * @depends testConsume
     */
    public function testRefund($queryData, $consumData)
    {
        $params = array(
            'bizType'   => '000301',
            'orderId'   => date('YmdHis'),
            'origQryId' => $consumData['response']['queryId'],
            'txnTime'   => date('YmdHis'),
            'txnAmt'    => 100,
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzRefundResponse $response
         */
        $response = $this->gateway->refund($params)->send();
        $this->assertFalse($response->isSuccessful());
    }


    public function testApplyToken()
    {
        $params = array(
            'orderId' => $this->options['orderId'],
            'txnTime' => $this->options['txnTime'],
            'trId'    => '99988877766',
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzApplyTokenResponse $response
         */
        $response = $this->gateway->applyToken($params)->send();
        $this->assertTrue($response->isSuccessful());
    }


    public function testUpdateToken()
    {
        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'trId'    => '99988877766',
            'token'   => '6235240000020757577',
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzApplyTokenResponse $response
         */
        $response = $this->gateway->applyToken($params)->send();
        $this->assertFalse($response->isSuccessful());
    }


    public function testDeleteToken()
    {
        $params = array(
            'orderId' => '20171031035544',
            'txnTime' => date('YmdHis'),
            'trId'    => '62000000001',
            'token'   => '6235240000020757577',
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzDeleteTokenResponse $response
         */
        $response = $this->gateway->deleteToken($params)->send();
        $this->assertFalse($response->isSuccessful());
    }
}