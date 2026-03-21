<?php
namespace App\Services\Zatca;

use phpseclib3\File\X509;

/**
 * A class defines certificate parser
 */
class Cert509XParser
{
    private string $certificateEncoded;
    private string $certificateSecret;
    private string $privateKey;
    private X509 $x509;

    public function __construct()
    {
        $this->x509 = new X509();
    }

    /**
     * Set certificate encoded
     *
     * @param string $certificateEncoded
     *
     * @return $this
     */
    public function setCertificateEncoded($certificateEncoded): self
    {
        $this->certificateEncoded = $certificateEncoded;

        return $this;
    }

    /**
     * Set certificate secret
     *
     * @param string $certificateSecret
     *
     * @return $this
     */
    public function setCertificateSecret($certificateSecret): self
    {
        $this->certificateSecret = $certificateSecret;

        return $this;
    }

    /**
     * Set private key
     *
     * @param string $privateKey
     *
     * @return $this
     */
    public function setPrivateKeyEncoded($privateKey): self
    {
        $this->privateKey = $privateKey;

        return $this;
    }

    /**
     * Get certificate decoded value
     *
     * @return string
     */
    public function getCertificateDecoded(): string
    {
        return base64_decode($this->certificateEncoded);
    }

    /**
     * Get private key decoded
     *
     * @return string
     */
    public function getPrivateKeyDecoded(): string
    {
        return base64_decode($this->privateKey);
    }

    /**
     * Get certificate with headers and footers
     *
     * @return string
     */
    public function getCertificate(): string
    {
        return "-----BEGIN CERTIFICATE-----\r\n" . $this->getCertificateDecoded() . "\r\n-----END CERTIFICATE-----";
    }

    /**
     * Get certificate hash base64 encoded
     *
     * @return string
     */
    public function getCertificateHashEncoded(): string
    {
        return base64_encode(hash('sha256', $this->getCertificateDecoded(), false));
    }

    /**
     * Get certificate signature
     *
     * @return string
     */
    public function getCertificateSignature(): string
    {
        $certOut = $this->x509->loadX509($this->GetCertificate());
        $signature = unpack('H*', $certOut['signature'])['1'];

        return pack('H*', substr($signature, 2));
    }

    /**
     * Get certificate public key base64 encoded
     *
     * @return string
     */
    public function getCertificatePublicKeyEncoded(): string
    {
        $this->x509->loadX509($this->GetCertificate());
        $publicKey = $this->x509->getPublicKey();
        $publicKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $publicKey);
        $publicKey = str_replace('-----END PUBLIC KEY-----', '', $publicKey);

        return base64_decode($publicKey);
    }

    /**
     * Get certificate serial number
     *
     * @return string
     */
    public function getCertificateSerialNumber(): string
    {
        $certOut = $this->x509->loadX509($this->GetCertificate());

        return $certOut['tbsCertificate']['serialNumber']->toString();
    }

    /**
     * Get certificate issuer name
     *
     * @return string
     */
    public function getCertificateIssuerName(): string
    {
        $this->x509->loadX509($this->GetCertificate());
        $issuer_names = [];
        $issuer_info = $this->x509->getIssuerDN(X509::DN_OPENSSL);

        foreach ($issuer_info as $key_parent => $string_row) {
            if ($key_parent == '0.9.2342.19200300.100.1.25') {
                foreach ($string_row as $string) {
                    $issuer_names[] = 'DC=' . $string;
                }
            }
            if ($key_parent == 'CN') {
                $issuer_names[] = 'CN=' . $string_row;
            }
        }

        return implode(', ', array_reverse($issuer_names));
    }
}
