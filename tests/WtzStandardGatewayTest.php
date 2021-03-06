<?php

namespace Omnipay\UnionPay\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\UnionPay\WtzGateway;

class WtzStandardGatewayTest extends GatewayTestCase
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
        $this->gateway->setBizType('000301'); // 标准版
        // certs
        $this->gateway->setEncryptCert(UNIONPAY_510_ENCRYPT_CERT);
        $this->gateway->setMiddleCert(UNIONPAY_510_MIDDLE_CERT);
        $this->gateway->setRootCert(UNIONPAY_510_ROOT_CERT);
        $this->gateway->setCertPath(UNIONPAY_510_SIGN_CERT);
        $this->gateway->setCertPassword(UNIONPAY_510_CERT_PASSWORD);

        $this->gateway->setReturnUrl('http://example.com/return');
        $this->gateway->setNotifyUrl('http://www.specialUrl.com');
    }


    private function open($content)
    {
        return $file = sprintf('./%s.html', md5(uniqid()));
        $fh = fopen($file, 'w');
        fwrite($fh, $content);
        fclose($fh);

        exec(sprintf('open %s -a "/Applications/Google Chrome.app" && sleep 5 && rm %s', $file, $file));
    }

    private function codeFromRespMsg($str)
    {
        if (preg_match("/\[(\d*)\]$/", $str, $arr)) {
            return $arr[1];
        } else {
            return null;
        }
    }

    private function sleep()
    {
//        sleep(5);
    }


    public function testFrontOpenConsume()
    {
        date_default_timezone_set('PRC');

        $orderId = date('YmdHis');

        $params = array(
            'orderId'      => $orderId,
            'txnTime'      => date('YmdHis'),
            'txnAmt'       => '100',
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
            'accNo'        => '6226388000000095',
            'customerInfo' => array(
                'phoneNo'    => '18100000000', //Phone Number
                'cvn2'       => '248', //cvn2
                'expired'    => '1912', // format YYMM
                'smsCode'    => '111111'
            ),
//            'payTimeout'   => date('YmdHis', strtotime('+15 minutes'))
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzFrontOpenResponse $response
         */
        $response = $this->gateway->backOpen($params)->send();
        $data = $response->getData();
        // 可能存在 无此交易权限[6131010] 的情况
        $this->assertTrue($data['verify_success']);
    }


    public function testSmsOpen()
    {
        date_default_timezone_set('PRC');

        $params = array(
            'bizType'      => '000301',
            'orderId'      => date('YmdHis'),
            'txnTime'      => date('YmdHis'),
            'accNo'        => '6226388000000095',
            'customerInfo' => array(
                'phoneNo'    => '18100000000', //Phone Number
            ),
//            'payTimeout'   => date('YmdHis', strtotime('+15 minutes'))
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzSmsOpenResponse $response
         */
        $response = $this->gateway->smsOpen($params)->send();
        $data = $response->getData();
        $this->sleep();

        $this->assertTrue($data['verify_success']);

        $code = $this->codeFromRespMsg($data['respMsg']);
        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
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


    public function testOpenQueryWithAccount()
    {
        $this->sleep();

        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'txnSubType' => '00',
            'accNo'  => '6226090000000048',
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzOpenQueryResponse $response
         */
        $response = $this->gateway->openQuery($params)->send();
        $this->assertTrue($response->isSuccessful());
    }


    public function testSmsConsume()
    {
        $this->sleep();

        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'txnAmt'  => 100,
            'accNo'   => '6226388000000095',
            'customerInfo' => [
                'phoneNo' => '18100000000',
            ]
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzSmsConsumeResponse $response
         */
        $response = $this->gateway->smsConsume($params)->send();
        $this->sleep();

        $data = $response->getData();
        $code = $this->codeFromRespMsg($data['respMsg']);

        // 偶尔会网关超时
        $this->assertTrue($data['verify_success']);
        $this->assertNotEquals("6100030", $code, $data['respMsg']);
    }

    public function testConsume()
    {
        $this->sleep();

        $params = array(
            'orderId' => date('YmdHis'),
            'txnTime' => date('YmdHis'),
            'txnAmt'  => 100,
            'accNo'   => '6226388000000095',
            'customerInfo' => [
                'smsCode' => '111111',
            ]
        );


        /**
         * @var \Omnipay\UnionPay\Message\WtzConsumeResponse $response
         */
        $response = $this->gateway->consume($params)->send();
        $this->assertTrue($response->isSuccessful());

        return [
            'params' => $params,
            'response' => $response->getData(),
        ];
    }

    /**
     * @depends testConsume
     */
    public function testRefund($preData)
    {
        $this->sleep();

        $params = array(
            'orderId'   => date('YmdHis'),
            'txnTime'   => date('YmdHis'),
            'origQryId' => $preData['response']['queryId'],
            'txnAmt'    => $preData['params']['txnAmt'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzRefundResponse $response
         */
        $response = $this->gateway->refund($params)->send();
        $this->assertTrue($response->isSuccessful());
    }

    /**
     * @depends testConsume
     */
    public function testQuery($preData)
    {
        $this->sleep();

        $params = array(
            'orderId' => $preData['params']['orderId'],
            'txnTime' => $preData['params']['txnTime'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzQueryResponse $response
         */
        $response = $this->gateway->query($params)->send();
        $this->assertTrue($response->isSuccessful());
    }
}
