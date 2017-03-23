<?php

/**
 * Toknot (http://toknot.com)
 *
 * @copyright  Copyright (c) 2011 - 2017 Toknot.com
 * @license    http://toknot.com/LICENSE.txt New BSD License
 * @link       https://github.com/chopins/toknot
 */

namespace Toknot\Share;

use Toknot\Exception\BaseException;

/**
 * OpenSSL
 *
 * @author chopin
 */
class OpenSSL {

    private $keySize = '2048';
    private $alg = 'sha512';
    private $dn = ['commonName' => 'toknot'];
    private $validDay = 30;
    private $strCert = '';
    private $cert = null;
    private $prk = null;
    private $csr = null;
    private $ver = 0;

    public function __construct() {
        try {
            $this->ver = constant('OPENSSL_VERSION_NUMBER');
        } catch (\Exception $ex) {
            throw new BaseException('OpenSSL extension unload');
        }
    }

    public function setValidDay($day = 30) {
        $this->validDay = $day;
    }

    public function setAlg($alg) {
        $this->alg = $alg;
    }

    public function setKeyBit($bit) {
        $this->keySize = $bit;
    }

    public function addDN($key, $v) {
        $this->dn[$key] = $v;
    }

    protected function generatePrivateKey() {
        $conf = ['private_key_bits' => $this->keySize,
            'digest_alg' => $this->alg,
            'private_key_type' => OPENSSL_KEYTYPE_RSA];
        $this->prk = openssl_pkey_new($conf);
    }

    public function getPairKey() {
        $this->generatePrivateKey();
        $out = openssl_pkey_get_details($this->prk);
        return ['pri' => $this->getPriKeyStr(), 'pub' => $out['key']];
    }

    public function generateCert() {
        $this->generatePrivateKey();
        $this->csr = openssl_csr_new($this->dn, $this->prk);
        $this->cert = openssl_csr_sign($this->csr, null, $this->prk, $this->validDay);
        openssl_x509_export($this->cert, $this->strCert);
    }

    public function getCert() {
        return $this->cert;
    }

    public function getCertStr() {
        return $this->strCert;
    }

    public function getPriKeyStr() {
        $out = '';
        openssl_pkey_export($this->prk, $out);
        return $out;
    }

    public function getPriKey() {
        return $this->prk;
    }

    public function getCertPubKeyStr() {
        $this->generateCert();
        $res = openssl_get_publickey($this->strCert);
        $out = openssl_pkey_get_details($res);
        return $out['key'];
    }

    public function encrypt($data, $pubKey) {
        $ecrypted = false;
        openssl_public_encrypt($data, $ecrypted, $pubKey);
        return base64_encode($ecrypted);
    }

    public function dencrypt($data, $priKey) {
        $decrypted = false;
        $data = base64_decode($data);
        openssl_private_decrypt($data, $decrypted, $priKey);
        return $decrypted;
    }

    public function sign($data, $priKey) {
        openssl_sign($data, $signature, $priKey);
        return base64_encode($signature);
    }

    public function verify($data, $signature, $pubKey) {
        $d = base64_decode($signature);
        return openssl_verify($data, $d, $pubKey);
    }

}
