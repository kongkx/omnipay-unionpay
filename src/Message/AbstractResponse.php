<?php

namespace Omnipay\UnionPay\Message;

use Omnipay\Common\Message\AbstractResponse as BaseAbstractResponse;
use Omnipay\UnionPay\Common\CertUtil;
use Omnipay\UnionPay\Common\Signer;
use Omnipay\UnionPay\Common\StringUtil;

abstract class AbstractResponse extends BaseAbstractResponse
{
    public function __construct(AbstractRequest $request, $data)
    {
        parent::__construct($request, $data);

        if (!$this->isRedirect()) {
            $this->data['verify_success'] = self::verify(
                $data,
                $this->request->getEnvironment(),
                $this->request->getRootCert(),
                $this->request->getMiddleCert()
            );
        }
    }

    public function isSuccessful()
    {
        return isset($this->data['respCode']) && $this->data['respCode'] == '00' && $this->data['verify_success'];
    }

    public function getCodeFromRespMsg()
    {
        if (!array_key_exists('respMsg', $this->data)) {
            return null;
        }
        if (preg_match("/\[(\d*)\]$/", $this->data['respMsg'], $arr)) {
            return $arr[1];
        } else {
            return null;
        }
    }

    public function getCustomerInfo()
    {
        if (!array_key_exists('customerInfo', $this->data)) {
            return null;
        }

        $customerInfoStr = substr(base64_decode($this->data['customerInfo']), 1, -2);
        $customerInfo = StringUtil::parseString($customerInfoStr);
        if (array_key_exists('encryptedInfo', $customerInfo)) {
            $encryptedInfoStr = $customerInfo["encryptedInfo"];
            unset($customerInfo ["encryptedInfo"]);
            $encryptedInfoStr = base64_decode($encryptedInfoStr);

            $encryptedInfoStr = $this->decrypt($encryptedInfoStr);
            $encryptedInfo = StringUtil::parseString($encryptedInfoStr);
            $customerInfo = array_merge($customerInfo, $encryptedInfo);
        }

        return $customerInfo;
    }

    public function decrypt($payload)
    {
        if ($privateKey = $this->request->getPrivateKey()) {
            return CertUtil::decryptWithPrivateKey($payload, $privateKey);
        }
        return CertUtil::decryptWithCert($payload, $this->request->getCertPath(), $this->request->getCertPassword());
    }

    public static function verify($data, $env, $rootCert, $middleCert)
    {
        if (! isset($data['signPubKeyCert'])) {
            return false;
        }

        $publicKey = $data['signPubKeyCert'];
        $certInfo  = openssl_x509_parse($publicKey);

        $cn    = CertUtil::getCompanyFromCert($certInfo);
        $union = '中国银联股份有限公司';

        if ($env == 'sandbox') {
            if (! in_array($cn, array('00040000:SIGN', $cn))) {
                return false;
            }
        } else {
            if ($cn != $union) {
                return false;
            }
        }

        $from = date_create('@' . $certInfo['validFrom_time_t']);
        $to   = date_create('@' . $certInfo['validTo_time_t']);
        $now  = date_create(date('Ymd'));

        $interval1 = $from->diff($now);
        $interval2 = $now->diff($to);

        if ($interval1->invert || $interval2->invert) {
            return false;
        }

        $result = openssl_x509_checkpurpose(
            $publicKey,
            X509_PURPOSE_ANY,
            array($rootCert, $middleCert)
        );

        if ($result === true) {
            $signer = new Signer($data);
            $signer->setIgnores(array('signature'));

            $hashed    = hash('sha256', $signer->getPayload());
            $signature = base64_decode($data['signature']);

            $isSuccess = openssl_verify($hashed, $signature, $publicKey, OPENSSL_ALGO_SHA256);

            return boolval($isSuccess);
        } else {
            return false;
        }
    }
}
