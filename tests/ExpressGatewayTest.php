<?php

namespace Omnipay\UnionPay\Tests;

use Omnipay\Omnipay;
use Omnipay\Tests\GatewayTestCase;
use Omnipay\UnionPay\ExpressGateway;
use Omnipay\UnionPay\Message\ExpressPurchaseResponse;

class ExpressGatewayTest extends GatewayTestCase
{
    /**
     * @var ExpressGateway
     */
    protected $gateway;

    protected $options;


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
    }

    private function open($content)
    {
        $file = sprintf('./%s.html', md5(uniqid()));
        return;
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
        $this->open($response->getRedirectHtml());
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


    public function testQuery()
    {
        $params = array(
            'orderId' => $this->options['orderId'],
            'txnTime' => $this->options['txnTime']
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->query($params)->send();
        $data = $response->getData();

        $this->assertTrue($data['verify_success']);

        $code = $this->codeFromRespMsg($data['respMsg']);
        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }


    public function testConsumeUndo()
    {
        $orderId = date('YmdHis');
        $params = array(
            'orderId' => $orderId,
            'txnTime' => $orderId,
            'queryId' => '761906160131325386289',
            'txnAmt'  => '100',
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->consumeUndo($params)->send();
        $data = $response->getData();
        $this->assertTrue($data['verify_success']);

        $code = $this->codeFromRespMsg($data['respMsg']);

        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }


    public function testRefund()
    {
        $orderId = date('YmdHis');
        $options = array(
            'orderId' => $orderId,
            'txnTime' => $orderId,
            'queryId' => '761906160131325386289',
            'txnAmt'  => '100',
        );

        /**
         * @var ExpressPurchaseResponse
         */
        $response = $this->gateway->refund($options)->send();

        $data = $response->getData();

        $this->assertTrue($data['verify_success']);

        $code = $this->codeFromRespMsg($data['respMsg']);

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
        $code = $this->codeFromRespMsg($data['respMsg']);
        $this->assertNotEquals("6100030", $code, $data['respMsg']);  // 报文格式错误
    }
}
