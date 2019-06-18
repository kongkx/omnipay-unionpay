<?php

namespace Omnipay\UnionPay\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\UnionPay\ExpressGateway;
use Omnipay\UnionPay\Message\ExpressPurchaseResponse;
use Behat\Mink\Mink;
use Behat\Mink\Session;
use DMore\ChromeDriver\ChromeDriver;

class ExpressGatewayTest extends GatewayTestCase
{
    /**
     * @var ExpressGateway
     */
    protected $gateway;

    protected $options;

    protected $mink;


    public function setUp()
    {
        parent::setUp();
        $this->gateway = Omnipay::create('UnionPay_Express');
        $this->gateway->setMerId(UNIONPAY_EXPRESS_MER_ID);

        $this->gateway->setEncryptCert(UNIONPAY_510_ENCRYPT_CERT);
        $this->gateway->setMiddleCert(UNIONPAY_510_MIDDLE_CERT);
        $this->gateway->setRootCert(UNIONPAY_510_ROOT_CERT);
        $this->gateway->setCertPath(UNIONPAY_510_SIGN_CERT);
        $this->gateway->setCertPassword(UNIONPAY_510_CERT_PASSWORD);

        $this->gateway->setReturnUrl('http://example.com/return');
        $this->gateway->setNotifyUrl('http://www.specialUrl.com');
        $this->gateway->setEnvironment('sandbox');

        $this->options = [
            'orderId' => getenv('UNIONPAY_EXPRESS_ORDER_ID') ?: '20190616013132',
            'txnTime' => getenv('UNIONPAY_EXPRESS_TXN_TIME') ?: '20190616013132',
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


    public function testPurchase()
    {

        $orderId = date('YmdHis');

        $params = array(
            'orderId' => $orderId, //Your order ID
            'txnTime' => $orderId, //Should be format 'YmdHis'
            'txnAmt'  => '100', //Order Total Fee
            'riskRateInfo' => array(
                'commodityName' => '测试商品名称',
            )
//            'payTimeout' => date('YmdHis', strtotime('+15 minutes')) // 可选， 使用北京时间
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->purchase($params)->send();
        $this->assertTrue($response->isSuccessful());
        $this->assertTrue($response->isRedirect());
        $this->assertNotEmpty($response->getRedirectHtml());
        $form = $response->getRedirectHtml();

        $file = $this->writeForm($form);
        $session = $this->mink->getSession();
        $session->visit('file://'.$file);
        $session->wait('1000');
        $url = $session->getCurrentUrl();

        $urlMatched = (bool) preg_match(
            '/^https:\/\/cashier.test.95516.com\/b2c\/index.action?/',
            $url
        );
        $this->assertTrue($urlMatched);

        $page = $session->getPage();
        $page->findById('cardNumber')->setValue('6226090000000048');
        $page->findById('btnNext')->click();
        $session->wait('2000');
        $page->findById('realName')->setValue('张三');
        $page->findById('credentialNo')->setValue('510265790128303');
        $page->findById('btnGetCode')->click();
        $page->findById('smsCode')->setValue('111111');
        $session->wait('500');
        $page->findById('btnCardPay')->click();
        $session->wait('1000');


        $session->stop();
        exec(sprintf('rm %s', $file));

        return [
            'params' => $params
        ];

    }

//
//    public function testCompletePurchase()
//    {
//        $options = array(
//            'request_params' => array(
//                'certId'    => '68759585097',
//                'signature' => 'xxxxxxx',
//            ),
//        );
//
//        /**
//         * @var ExpressPurchaseResponse
//         */
//        $response = $this->gateway->completePurchase($options)->send();
//        $this->assertFalse($response->isSuccessful());
//    }

    /**
     * @depends testPurchase
     */
    public function testQuery($purData)
    {
        $params = array(
            'orderId' => $purData['params']['orderId'],
            'txnTime' => $purData['params']['txnTime']
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->query($params)->send();
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);

        $code = $response->getCodeFromRespMsg();
        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误

        return [
            'params' => $params,
            'response' => $data,
        ];
    }

    /**
     * @depends testQuery
     */
    public function testConsumeUndo($queryData)
    {
        $orderId = date('YmdHis');
        $params = array(
            'orderId' => $orderId,
            'txnTime' => $orderId,
            'queryId' => $queryData['response']['queryId'],
            'txnAmt'  => '100',
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->consumeUndo($params)->send();
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);

        $code = $response->getCodeFromRespMsg();

        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }

    /**
     * @depends testQuery
     */
    public function testRefund($queryData)
    {
        $orderId = date('YmdHis');
        $options = array(
            'orderId' => $orderId,
            'txnTime' => $orderId,
            'queryId' => $queryData['response']['queryId'],
            'txnAmt'  => '100',
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->refund($options)->send();

        $data = $response->getData();

        $this->assertTrue($data['verify_success']);

        $code = $response->getCodeFromRespMsg();

        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }


    public function testFileTransfer()
    {

        $params = array(
            'merId' => '700000000000001',  // 固定测试用商户号
            'txnTime' =>  '20190616033059',
            'fileType' => '00',
            'settleDate' => '0119',
        );

        $response = $this->gateway->fileTransfer($params)->send();
        $data = $response->getData();

        $this->assertTrue($data['verify_success']);
        $code = $response->getCodeFromRespMsg();
        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }
}
