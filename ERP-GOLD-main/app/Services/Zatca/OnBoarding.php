<?php
namespace App\Services\Zatca;

use App\Services\Zatca\Invoice\ComplianceSteps;
use App\Services\Zatca\ZatcaConfig;
use GuzzleHttp\Exception\ClientException;
use Exception;
use OpenSSLAsymmetricKey;
use OpenSSLCertificateSigningRequest;

/**
 * A class defines first step for zatca phase two integration (get zatca authorization)
 */
class OnBoarding
{
    private string $env;
    private string $language = 'en';
    private string $emailAddress;
    private string $certificateTemplateName;
    private string $commonName;
    private string $countryCode;
    private string $organizationUnitName;
    private string $organizationName;
    private string $egsSerialNumber;
    private string $vatNumber;
    private string $invoiceType;
    private string $registeredAddress;
    private string $businessCategory;
    private ?string $authOtp = NULL;
    private ?string $certificate = NULL;
    private ?string $certificateSecret = NULL;
    private ?string $certificateRequestID = NULL;
    private ?string $productionCertificate = NULL;
    private ?string $productionCertificateSecret = NULL;
    private ?string $productionCertificateRequestID = NULL;
    private OpenSSLAsymmetricKey $privateKey;
    private OpenSSLCertificateSigningRequest $csrKey;

    /**
     * Set zatca environment
     *
     * @param string $env
     *
     * @throws Exception
     *
     * @return self
     */
    public function setZatcaEnv(string $env): self
    {
        if (!in_array($env, ZatcaConfig::getEnvironments())) {
            throw new Exception('Zatca environment is required');
        }

        $this->certificateTemplateName = ZatcaConfig::getCertificateTemplates($env);
        $this->env = $env;

        return $this;
    }

    /**
     * Set zatca response messages language
     *
     * @param string $language
     *
     * @return self
     */
    public function setZatcaLang(string $language): self
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Set zatca auth otp
     *
     * @param string $authOtp
     *
     * @throws Exception
     *
     * @return self
     */
    public function setAuthOtp(string $authOtp): self
    {
        if (is_null($authOtp) || empty($authOtp)) {
            throw new Exception('Zatca Otp is required');
        }

        $this->authOtp = $authOtp;

        return $this;
    }

    /**
     * Set email address
     *
     * @param string $emailAddress
     *
     * @throws Exception
     *
     * @return self
     */
    public function setEmailAddress(string $emailAddress): self
    {
        if (is_null($emailAddress) || empty($emailAddress)) {
            throw new Exception('Email Address is required');
        }

        $this->emailAddress = $emailAddress;

        return $this;
    }

    /**
     * Set common name of company
     *
     * @param string $commonName
     *
     * @throws Exception
     *
     * @return self
     */
    public function setCommonName(string $commonName): self
    {
        if (is_null($commonName) || empty($commonName)) {
            throw new Exception('Common Name is required');
        }

        $this->commonName = $commonName;

        return $this;
    }

    /**
     * Set country code of company
     *
     * @param string $countryCode
     *
     * @throws Exception
     *
     * @return self
     */
    public function setCountryCode(string $countryCode): self
    {
        if (is_null($countryCode) || empty($countryCode)) {
            throw new Exception('Country Code is required');
        }

        $this->countryCode = $countryCode;

        return $this;
    }

    /**
     * Set organization unit name of company
     *
     * @param string $organizationUnitName
     *
     * @throws Exception
     *
     * @return self
     */
    public function setOrganizationUnitName(string $organizationUnitName): self
    {
        if (is_null($organizationUnitName) || empty($organizationUnitName)) {
            throw new Exception('Organization Unit Name is required');
        }

        $this->organizationUnitName = $organizationUnitName;

        return $this;
    }

    /**
     * Set organization name of company
     *
     * @param string $organizationName
     *
     * @throws Exception
     *
     * @return self
     */
    public function setOrganizationName(string $organizationName): self
    {
        if (is_null($organizationName) || empty($organizationName)) {
            throw new Exception('Organization Name is required');
        }

        $this->organizationName = $organizationName;

        return $this;
    }

    /**
     * Set egs serial number of company
     *
     * @param string $egsSerialNumber
     *
     * @throws Exception
     *
     * @return self
     */
    public function setEgsSerialNumber(string $egsSerialNumber): self
    {
        if (is_null($egsSerialNumber) || empty($egsSerialNumber)) {
            throw new Exception('Egs Serial Number is required');
        }

        $this->egsSerialNumber = $egsSerialNumber;

        return $this;
    }

    /**
     * Set vat number of company
     *
     * @param string $vatNumber
     *
     * @throws Exception
     *
     * @return self
     */
    public function setVatNumber(string $vatNumber): self
    {
        if (is_null($vatNumber) || empty($vatNumber)) {
            throw new Exception('Vat Number is required');
        }

        $this->vatNumber = $vatNumber;

        return $this;
    }

    /**
     * Set invoice type of company
     *
     * @param string $invoiceType
     *
     * @throws Exception
     *
     * @return self
     */
    public function setInvoiceType(string $invoiceType): self
    {
        if (!in_array($invoiceType, ZatcaConfig::getInvoiceTypes())) {
            throw new Exception('Invoice Type is required');
        }

        $this->invoiceType = $invoiceType;

        return $this;
    }

    /**
     * Set registered address of company
     *
     * @param string $registeredAddress
     *
     * @throws Exception
     *
     * @return self
     */
    public function setRegisteredAddress(string $registeredAddress): self
    {
        if (is_null($registeredAddress) || empty($registeredAddress)) {
            throw new Exception('Registered Address is required');
        }

        $this->registeredAddress = $registeredAddress;

        return $this;
    }

    /**
     * Set business category of company
     *
     * @param string $businessCategory
     *
     * @throws Exception
     *
     * @return self
     */
    public function setBusinessCategory(string $businessCategory): self
    {
        if (is_null($businessCategory) || empty($businessCategory)) {
            throw new Exception('Business Category is required');
        }

        $this->businessCategory = $businessCategory;

        return $this;
    }

    /**
     * Generate zatca required settings into array
     *
     * @return array
     */
    private function getSettings(): array
    {
        return [
            'emailAddress' => $this->emailAddress,
            'certificateTemplateName' => $this->certificateTemplateName,
            'commonName' => $this->commonName,
            'countryCode' => $this->countryCode,
            'organizationUnitName' => $this->organizationUnitName,
            'organizationName' => $this->organizationName,
            'egsSerialNumber' => $this->egsSerialNumber,
            'vatNumber' => $this->vatNumber,
            'invoiceType' => $this->invoiceType,
            'registeredAddress' => $this->registeredAddress,
            'businessCategory' => $this->businessCategory,
        ];
    }

    /**
     * Generate zatca configuration template
     *
     * @return string
     */
    public function generateConfigTemplate(): string
    {
        $settingsData = $this->getSettings();

        $emailAddress = $settingsData['emailAddress'];
        $certificateTemplateName = $settingsData['certificateTemplateName'];
        $commonName = $settingsData['commonName'];
        $countryCode = $settingsData['countryCode'];
        $organizationUnitName = $settingsData['organizationUnitName'];
        $organizationName = $settingsData['organizationName'];
        $egsSerialNumber = $settingsData['egsSerialNumber'];
        $vatNumber = $settingsData['vatNumber'];
        $invoiceType = $settingsData['invoiceType'];
        $registeredAddress = $settingsData['registeredAddress'];
        $businessCategory = $settingsData['businessCategory'];

        return "
            oid_section = OIDs
            [ OIDs ]
            certificateTemplateName= 1.3.6.1.4.1.311.20.2

            [ req ]
            default_bits \t= 2048
            emailAddress \t= {$emailAddress}
            req_extensions\t= v3_req
            x509_extensions \t= v3_ca
            prompt = no
            default_md = sha256
            req_extensions = req_ext
            distinguished_name = dn

            [ v3_req ]
            basicConstraints = CA:FALSE
            keyUsage = digitalSignature, nonRepudiation, keyEncipherment

            [req_ext]
            certificateTemplateName = ASN1:PRINTABLESTRING:{$certificateTemplateName}
            subjectAltName = dirName:alt_names

            [ v3_ca ]

            # Extensions for a typical CA

            # PKIX recommendation.

            subjectKeyIdentifier = hash

            authorityKeyIdentifier = keyid:always,issuer:always

            [ dn ]
            CN = {$commonName}  \t\t\t\t                    # Common Name
            C = {$countryCode}\t\t\t\t\t\t\t            # Country Code e.g SA
            OU = {$organizationUnitName}\t\t\t\t\t\t\t# Organization Unit Name
            O = {$organizationName}\t\t\t\t\t\t\t        # Organization Name

            [alt_names]
            SN = {$egsSerialNumber}\t\t\t\t                    # EGS Serial Number 1-ABC|2-PQR|3-XYZ
            UID = {$vatNumber}\t\t\t\t\t\t                # Organization Identifier (VAT Number)
            title = {$invoiceType}\t\t\t\t\t\t\t\t    # Invoice Type
            registeredAddress = {$registeredAddress}  \t \t\t\t# Address
            businessCategory = {$businessCategory}\t\t\t\t\t# Business Category
        ";
    }

    /**
     * Generate openssl configuration array
     *
     * @param string $tempFilePath
     *
     * @return array
     */
    public function generateOpensslConfiguration(string $tempFilePath): array
    {
        return [
            'config' => $tempFilePath,
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp256k1'
        ];
    }

    /**
     * Generate private key
     *
     * @throws Exception
     *
     * @return void
     */
    public function generatePrivateKey(): void
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $this->generateConfigTemplate());
        fseek($tempFile, 0);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        $this->privateKey = openssl_pkey_new($this->generateOpensslConfiguration($tempFilePath));

        if (!$this->privateKey) {
            throw new Exception('ERROR: Fail to generate private key. -> ' . openssl_error_string());
        }

        fclose($tempFile);
    }

    /**
     * Export private key
     *
     * @return string
     */
    public function exportPrivateKey(): string
    {
        $tempFile = tmpfile();
        fwrite($tempFile, $this->generateConfigTemplate());
        fseek($tempFile, 0);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        $privateKey = $this->privateKey;
        openssl_pkey_export($privateKey, $exportedString, NULL, $this->generateOpensslConfiguration($tempFilePath));

        fclose($tempFile);

        return $exportedString;
    }

    /**
     * Export public key
     *
     * @return string
     */
    public function exportPublicKey(): string
    {
        $privateKey = $this->privateKey;
        $keyDetails = openssl_pkey_get_details($privateKey);

        return $keyDetails['key'];
    }

    /**
     * Generate csr key
     *
     * @return OpenSSLCertificateSigningRequest
     */
    public function generateCsrKey(): OpenSSLCertificateSigningRequest
    {
        $this->generatePrivateKey();

        $settingsData = $this->getSettings();
        $privateKey = $this->privateKey;

        $commonName = $settingsData['commonName'];
        $countryCode = $settingsData['countryCode'];
        $organizationUnitName = $settingsData['organizationUnitName'];
        $organizationName = $settingsData['organizationName'];

        $tempFile = tmpfile();
        fwrite($tempFile, $this->generateConfigTemplate());
        fseek($tempFile, 0);
        $tempFilePath = stream_get_meta_data($tempFile)['uri'];

        $dn = [
            'commonName' => $commonName,
            'organizationalUnitName' => $organizationUnitName,
            'organizationName' => $organizationName,
            'countryName' => $countryCode
        ];
        $csrKey = openssl_csr_new($dn, $privateKey, array('digest_alg' => 'sha256', 'req_extensions' => 'req_ext', 'curve_name' => 'secp256k1', 'config' => $tempFilePath));

        fclose($tempFile);

        return $csrKey;
    }

    /**
     * Export csr key
     *
     * @return string
     */
    public function exportCsrKey(): string
    {
        openssl_csr_export($this->csrKey, $exportedString);

        return $exportedString;
    }

    /**
     * Build zatca onboarding api request
     *
     * @param  array  $body
     * @param  bool  $isProduction
     *
     * @return mixed
     */
    public function request(array $body, bool $isProduction = false): mixed
    {
        if (count($body) == 0) {
            throw new Exception("Body data is must't be empty !");
        }
        $url = ZatcaConfig::BaseUrl($this->env);
        $url .= ($isProduction) ? '/production/csids' : '/compliance';
        $options['json'] = $body;
        $options['headers'] = [
            'Content-Type' => 'application/json',
            'Accept-Language' => $this->language,
            'Accept-Version' => 'V2',
            'Accept' => 'application/json'
        ];

        if (!$isProduction) {
            $options['headers']['otp'] = $this->authOtp;
        }

        $client = new \GuzzleHttp\Client(['verify' => false]);

        if ($isProduction) {
            if (empty($this->certificate) || empty($this->certificateSecret)) {
                throw new Exception('Zatca Basic Auth is required');
            }
            $options['auth'] = [$this->certificate, $this->certificateSecret];
        }
        try {
            $request = $client->request('POST', $url, $options);
            $response = json_decode($request->getBody()->getContents());
            if ($isProduction) {
                $this->productionCertificate = $response->binarySecurityToken;
                $this->productionCertificateSecret = $response->secret;
                $this->productionCertificateRequestID = $response->requestID;
            } else {
                $this->certificate = $response->binarySecurityToken;
                $this->certificateSecret = $response->secret;
                $this->certificateRequestID = $response->requestID;
            }
            return ['success' => true, 'response' => $response];
        } catch (ClientException $exception) {
            $response = json_decode($exception->getResponse()->getBody()->getContents());
            return ['success' => false, 'response' => $response];
        }
    }

    /**
     * Get zatca authorization
     *
     * @return mixed
     */
    public function getAuthorization(): mixed
    {
        if (is_null($this->authOtp) || empty($this->authOtp)) {
            throw new Exception('Zatca Otp is required');
        }
        $this->csrKey = $this->generateCsrKey();

        $body = [
            'csr' => base64_encode($this->exportCsrKey())
        ];

        $complianceRequest = $this->request($body);

        if ($complianceRequest['success']) {
            $body = [
                'compliance_request_id' => (string) $this->certificateRequestID
            ];

            (new ComplianceSteps(
                $this->certificate,
                $this->certificateSecret,
                $this->exportPrivateKey(),
                $this->vatNumber,
                $this->invoiceType,
                $this->env,
            ))->sendComplianceSteps();

            $productionRequest = $this->request($body, true);

            return $this->handleResponse($productionRequest);
        } else {
            return $this->handleResponse($complianceRequest);
        }
    }

    /**
     * Get Final results
     *
     * @return mixed
     */
    public function getFinalResults(): mixed
    {
        return [
            'complianceCertificate' => $this->certificate,
            'complianceSecret' => $this->certificateSecret,
            'complianceRequestID' => $this->certificateRequestID,
            'productionCertificate' => $this->productionCertificate,
            'productionCertificateSecret' => $this->productionCertificateSecret,
            'productionCertificateRequestID' => $this->productionCertificateRequestID,
            'privateKey' => base64_encode($this->exportPrivateKey()),
            'publicKey' => base64_encode($this->exportPublicKey()),
            'csrKey' => base64_encode($this->exportCsrKey()),
            'configData' => base64_encode($this->generateConfigTemplate()),
        ];
    }

    /**
     * Handle http response
     *
     * @param mixed $response
     *
     * @return mixed
     */
    public function handleResponse(mixed $response): mixed
    {
        if ($response['success']) {
            return [
                'success' => true,
                'message' => $response['response']->{'dispositionMessage'},
                'data' => $this->getFinalResults()
            ];
        } else {
            if (isset($response['response']->{'errors'}) && count($response['response']->{'errors'}) > 0) {
                return [
                    'success' => false,
                    'message' => $response['response']->{'errors'}[0]->{'message'},
                    'data' => $this->getFinalResults()
                ];
            } elseif (isset($response['response']->{'code'}) && $response['response']->{'code'} == 'Invalid-OTP') {
                return [
                    'success' => false,
                    'message' => $response['response']->{'message'},
                    'data' => $this->getFinalResults()
                ];
            } elseif (isset($response['response']->{'code'}) && $response['response']->{'code'} == 'Missing-ComplianceSteps') {
                return [
                    'success' => false,
                    'message' => $response['response']->{'message'},
                    'data' => $this->getFinalResults()
                ];
            } elseif (isset($response['response']->{'code'}) && $response['response']->{'code'} == 'Incorrect-ComplianceRequestId') {
                return [
                    'success' => false,
                    'message' => $response['response']->{'message'},
                    'data' => $this->getFinalResults()
                ];
            } elseif (isset($response['response']->{'code'}) && $response['response']->{'code'} == 'Invalid-CurrentCCSID') {
                return [
                    'success' => false,
                    'message' => $response['response']->{'message'},
                    'data' => $this->getFinalResults()
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Something went wrong',
                    'data' => $this->getFinalResults()
                ];
            }
        }
    }
}
