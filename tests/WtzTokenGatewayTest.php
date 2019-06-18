<?php

namespace Omnipay\UnionPay\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\UnionPay\WtzGateway;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

class WtzTokenGatewayTest extends GatewayTestCase
{

    /**
     * @var WtzGateway $gateway
     */
    protected $gateway;

    protected $options;

    protected $mink;


    public function setUp()
    {
        parent::setUp();
        $this->gateway = Omnipay::create('UnionPay_Wtz');
        $this->gateway->setMerId(UNIONPAY_WTZ_MER_ID);
        $this->gateway->setEncryptSensitive(true);
        $this->gateway->setBizType('000902');
        $this->gateway->setTrId('99988877766');
        $this->gateway->setEncryptCert(UNIONPAY_510_ENCRYPT_CERT);
        $this->gateway->setMiddleCert(UNIONPAY_510_MIDDLE_CERT);
        $this->gateway->setRootCert(UNIONPAY_510_ROOT_CERT);
        $this->gateway->setCertPath(UNIONPAY_510_SIGN_CERT);
        $this->gateway->setCertPassword(UNIONPAY_510_CERT_PASSWORD);
        $this->gateway->setReturnUrl('http://example.com/return');
        $this->gateway->setNotifyUrl('http://www.specialUrl.com');

        // options 为已完成 FrontOpen 的订单数据。可以通过 query 接口 获取相关 token 信息
        $this->options = [
            'orderId' => getenv('UNIONPAY_WTZ_TOKEN_ORDER_ID') ?: '20190608021356',
            'txnTime' => getenv('UNIONPAY_WTZ_TOKEN_TXN_TIME') ?: '20190608021356',
        ];

        date_default_timezone_set('PRC');
        $this->mink = new Mink(array(
            'browser' => new Session(new ChromeDriver('http://localhost:9222', null, ''))
        ));
        $this->mink->setDefaultSessionName('browser');
    }


    private function writeForm($content)
    {
        $file = sprintf('./%s.html', md5(uniqid()));
        $fh = fopen($file, 'w');
        fwrite($fh, $content);
        fclose($fh);

        $path = realpath($file);

        return $path;
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
        $file = $this->writeForm($form);
        $session = $this->mink->getSession();
        $session->visit('file://'.$file);

        $session->wait('1000');
        $url = $session->getCurrentUrl();
        $urlMatched = (bool) preg_match(
            '/^https:\/\/cashier.test.95516.com\/b2c\/authpay\/ActivateAndPay.action?/',
            $url
        );
        $this->assertTrue($urlMatched);
        $session->stop();
        exec(sprintf('rm %s', $file));
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
        $file = $this->writeForm($form);
        $session = $this->mink->getSession();
        $session->visit('file://'.$file);

        $session->wait('1000');
        $url = $session->getCurrentUrl();

        // redirect page
        $urlMatched = (bool) preg_match(
            '/^https:\/\/cashier.test.95516.com\/b2c\/authpay\/Activate.action?/',
            $url
        );
        $this->assertTrue($urlMatched);

        // interact with page
        $page = $session->getPage();
        $page->findById('realName')->setValue('张三');
        $page->findById('credentialNo')->setValue('510265790128303');
        $page->findById('cellPhoneNumber')->setValue('18100000000');
        $page->findById('btnGetCode')->click();
        $session->wait('500');
        $page->findById('smsCode')->setValue('111111');
        $page->findById('btnCardPay')->click();
        $session->wait('2000');
        $session->stop();
        exec(sprintf('rm %s', $file));

        return [
            'params' => $params,
        ];
    }


    public function testBackOpen()
    {
        date_default_timezone_set('PRC');

        $params = array(
            'orderId'      => date('YmdHis'),
            'txnTime'      => date('YmdHis'),
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
        $this->assertTrue($response->getData()['verify_success']);
        // $this->assertTrue($response->isSuccessful());
    }


    public function testSmsOpen()
    {

        $params = array(
            'orderId'      => date('YmdHis'),
            'txnTime'      => date('YmdHis'),
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

        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
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

    /**
     * @depends testFrontOpen
     */
    public function testOpenQuery($openData)
    {
        $params = array(
            'txnSubType' => '02',
            'orderId' => $openData['params']['orderId'],
            'txnTime' => $openData['params']['txnTime'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzOpenQueryResponse $response
         */
        $response = $this->gateway->openQuery($params)->send();

        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
        $customerInfo = $response->getCustomerInfo();
        $this->assertTrue(array_key_exists('phoneNo', $customerInfo));

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
            'token'   => $preData['tokenPayData']['token'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzSmsConsumeResponse $response
         */
        $request =  $this->gateway->smsConsume($params);
        $response = $request->send();

        $this->assertTrue($response->getData()['verify_success']);
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
            'token'   => $preData['tokenPayData']['token'],
            'customerInfo' => ['smsCode' => '111111']
        );

        $request = $this->gateway->consume($params);

        /**
         * @var \Omnipay\UnionPay\Message\WtzConsumeResponse $response
         */
        $response = $request->send();
        
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
        // 6100030 格式错误
        $this->assertNotEquals("6100030", $response->getCodeFromRespMsg(), $data['respMsg']);

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
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
        $this->assertNotEquals("6100030", $response->getCodeFromRespMsg(), $data['respMsg']);
    }



    /**
     * @depends testOpenQuery
     * @depends testConsume
     */
    public function testRefund($queryData, $consumeData)
    {
        $params = array(
            'bizType'   => '000301',
            'orderId'   => date('YmdHis'),
            'origQryId' =>  array_key_exists('queryId', $consumeData['response']) ?
                $consumeData['response']['queryId'] :
                "xxxxxxxxx",
            'txnTime'   => date('YmdHis'),
            'txnAmt'    => 100,
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzRefundResponse $response
         */
        $response = $this->gateway->refund($params)->send();
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
        $this->assertNotEquals("6100030", $response->getCodeFromRespMsg(), $data['respMsg']);
    }

    /**
     * @depends testFrontOpen
     */
    public function testApplyToken($openData)
    {
        $params = array(
            'orderId' => $openData['params']['orderId'],
            'txnTime' => $openData['params']['txnTime'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzApplyTokenResponse $response
         */
        $response = $this->gateway->applyToken($params)->send();
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);
    }

    /**
     * @depends testOpenQuery
     */
    public function testUpdateToken($queryData)
    {
        $params = array(
            'orderId' => $this->options['orderId'],
            'txnTime' => $this->options['txnTime'],
            'token'   => $queryData['tokenPayData']['token'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzApplyTokenResponse $response
         */
        $response = $this->gateway->applyToken($params)->send();
        $this->assertTrue($response->getData()['verify_success']);
    }

    /**
     * @depends testOpenQuery
     */
    public function testDeleteToken($queryData)
    {
        $params = array(
            'orderId' => $this->options['orderId'],
            'txnTime' => $this->options['txnTime'],
            'token'   => $queryData['tokenPayData']['token'],
        );

        /**
         * @var \Omnipay\UnionPay\Message\WtzDeleteTokenResponse $response
         */
        $response = $this->gateway->deleteToken($params)->send();
        $this->assertTrue($response->getData()['verify_success']);
    }
}
