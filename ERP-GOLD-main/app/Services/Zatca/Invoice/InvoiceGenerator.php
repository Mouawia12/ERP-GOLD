<?php
namespace App\Services\Zatca\Invoice;

use App\Services\Zatca\Cert509XParser;
use App\Services\Zatca\QRCodeString;
use App\Services\Zatca\ZatcaConfig;
use GuzzleHttp\Exception\ClientException;
use Exception;

/**
 * A class defines zatca phase two invoice generator
 */
class InvoiceGenerator
{
    private string $profileID = 'reporting:1.0';
    private string $invoiceNumber;
    private string $invoiceUuid;
    private string $invoiceIssueDate;
    private string $invoiceIssueTime;
    private string $invoiceType;
    private string $invoiceDocumentType;
    private string $invoiceCurrencyCode;
    private string $invoiceTaxCurrencyCode;
    private ?BillingReference $billingReference = null;
    private AdditionalDocumentReference $AdditionalDocumentReference;
    private PIH $pih;
    private Supplier $supplier;
    private ?Client $client = null;
    private Delivery $delivery;
    private PaymentType $paymentType;
    private array $allowanceCharges;
    private ?ReturnReason $returnReason = null;
    private LegalMonetaryTotal $legalMonetaryTotal;
    private TaxesTotal $taxesTotal;
    private array $taxSubTotal;
    private array $invoiceLines;
    private string $timestamp;
    private string $certificateEncoded;
    private string $certificateSecret;
    private string $privateKey;
    private ?string $invoiceDigitalSignature = null;
    private string $env;
    private string $language = 'en';

    public function __construct()
    {
        $this->timestamp = (new \DateTime())->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * Set invoice number
     *
     * @param string $invoiceNumber
     *
     * @return $this
     */
    public function setInvoiceNumber(string $invoiceNumber): self
    {
        $this->invoiceNumber = $invoiceNumber;

        return $this;
    }

    /**
     * Set invoice uuid
     *
     * @param string $invoiceUuid
     *
     * @return $this
     */
    public function setInvoiceUuid(string $invoiceUuid): self
    {
        $this->invoiceUuid = $invoiceUuid;

        return $this;
    }

    /**
     * Set invoice issue date
     *
     * @param string $invoiceUuid
     *
     * @return $this
     */
    public function setInvoiceIssueDate(string $invoiceIssueDate): self
    {
        $this->invoiceIssueDate = $invoiceIssueDate;

        return $this;
    }

    /**
     * Set invoice issue time
     *
     * @param string $invoiceIssueTime
     *
     * @return $this
     */
    public function setInvoiceIssueTime(string $invoiceIssueTime): self
    {
        $this->invoiceIssueTime = $invoiceIssueTime;

        return $this;
    }

    /**
     * Define invoice simplified or standard by (invoiceType) & define is it normal invocie or debit note or credit note by (invoiceDocumentType)
     *
     * @param string $invoiceType
     * @param string $invoiceDocumentType
     *
     * @return $this
     */
    public function setInvoiceType(string $invoiceType, string $invoiceDocumentType): self
    {
        $this->invoiceType = $invoiceType;
        $this->invoiceDocumentType = $invoiceDocumentType;

        return $this;
    }

    /**
     * Set invoice currency code
     *
     * @param string $invoiceCurrencyCode
     *
     * @return $this
     */
    public function setInvoiceCurrencyCode(string $invoiceCurrencyCode): self
    {
        $this->invoiceCurrencyCode = $invoiceCurrencyCode;

        return $this;
    }

    /**
     * Set invoice tax currency code
     *
     * @param string $invoiceTaxCurrencyCode
     *
     * @return $this
     */
    public function setInvoiceTaxCurrencyCode(string $invoiceTaxCurrencyCode): self
    {
        $this->invoiceTaxCurrencyCode = $invoiceTaxCurrencyCode;

        return $this;
    }

    /**
     * Set invoice billing reference
     *
     * @param BillingReference $billingReference
     *
     * @return $this
     */
    public function setInvoiceBillingReference(BillingReference $billingReference): self
    {
        if ($this->invoiceDocumentType != '388') {
            $this->billingReference = $billingReference;
        }

        return $this;
    }

    /**
     * Set invoice additional document reference
     *
     * @param AdditionalDocumentReference $AdditionalDocumentReference
     *
     * @return $this
     */
    public function setInvoiceAdditionalDocumentReference(AdditionalDocumentReference $AdditionalDocumentReference): self
    {
        $this->AdditionalDocumentReference = $AdditionalDocumentReference;

        return $this;
    }

    /**
     * Set invoice previous hash
     *
     * @param PIH $pih
     *
     * @return $this
     */
    public function setInvoicePIH(PIH $pih): self
    {
        $this->pih = $pih;

        return $this;
    }

    /**
     * Set invoice supplier
     *
     * @param Supplier $supplier
     *
     * @return $this
     */
    public function setInvoiceSupplier(Supplier $supplier): self
    {
        $this->supplier = $supplier;

        return $this;
    }

    /**
     * Set invoice client
     *
     * @param Client $client
     *
     * @return $this
     */
    public function setInvoiceClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Set invoice delivery
     *
     * @param Delivery $delivery
     *
     * @return $this
     */
    public function setInvoiceDelivery(Delivery $delivery): self
    {
        $this->delivery = $delivery;

        return $this;
    }

    /**
     * Set invoice payment type
     *
     * @param PaymentType $paymentType
     *
     * @return $this
     */
    public function setInvoicePaymentType(PaymentType $paymentType): self
    {
        $this->paymentType = $paymentType;

        return $this;
    }

    /**
     * Set invoice allowance charges
     *
     * @param array $allowanceCharges
     *
     * @return $this
     */
    public function setInvoiceAllowanceCharges(...$allowanceCharges): self
    {
        $this->allowanceCharges = $allowanceCharges;

        return $this;
    }

    /**
     * Set invoice return reason
     *
     * @param ReturnReason $returnReason
     *
     * @return $this
     */
    public function setInvoiceReturnReason(ReturnReason $returnReason): self
    {
        if ($this->invoiceDocumentType != '388') {
            $this->returnReason = $returnReason;
        }

        return $this;
    }

    /**
     * Get payment means element
     *
     * @return array
     */
    public function getPaymentMeansElement(): array
    {
        $paymentMeans = [];
        array_push($paymentMeans, $this->paymentType->getElement());
        if ($this->returnReason) {
            array_push($paymentMeans, $this->returnReason->getElement());
        }

        return $paymentMeans;
    }

    /**
     * Set invoice legal monetary total
     *
     * @param LegalMonetaryTotal $legalMonetaryTotal
     *
     * @return $this
     */
    public function setInvoiceLegalMonetaryTotal(LegalMonetaryTotal $legalMonetaryTotal): self
    {
        $this->legalMonetaryTotal = $legalMonetaryTotal;

        return $this;
    }

    /**
     * Set invoice tax totals
     *
     * @param TaxesTotal $taxesTotal
     *
     * @return $this
     */
    public function setInvoiceTaxesTotal(TaxesTotal $taxesTotal): self
    {
        $this->taxesTotal = $taxesTotal;

        return $this;
    }

    /**
     * Set invoice tax sub totals
     *
     * @param array $taxSubTotal
     *
     * @return $this
     */
    public function setInvoiceTaxSubTotal(...$taxSubTotal): self
    {
        array_unshift($taxSubTotal, $this->taxesTotal->getElement());
        $this->taxSubTotal = $taxSubTotal;

        return $this;
    }

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
     * Get invoice Hash
     *
     * @return string
     */
    public function getInvoiceHashBaseEncoded(): string
    {
        $xml = (new XmlBuilder())
            ->build($this->getElement())
            ->generateAsText();

        $xml = str_replace('<?xml version="1.0"?>', '', $xml);
        $xml = str_replace('<cbc:elementToRemoved></cbc:elementToRemoved>', '', $xml);

        return base64_encode(hash('sha256', trim($xml), true));
    }

    /**
     * Set invoice lines
     *
     * @param array $invoiceLines
     *
     * @return $this
     */
    public function setInvoiceLines(...$invoiceLines): self
    {
        $this->invoiceLines = $invoiceLines;

        return $this;
    }

    /**
     * Load certificate parser
     *
     * @return Cert509XParser
     */
    public function certificateParser(): Cert509XParser
    {
        return (new Cert509XParser())
            ->setCertificateEncoded($this->certificateEncoded)
            ->setPrivateKeyEncoded($this->privateKey)
            ->setCertificateSecret($this->certificateSecret);
    }

    /**
     * Get invoice digital signature
     *
     * @return string
     */
    public function getInvoiceDigitalSignature(): string
    {
        if (is_null($this->invoiceDigitalSignature)) {
            openssl_sign($this->getInvoiceHashBaseEncoded(), $signature, $this->certificateParser()->getPrivateKeyDecoded(), 'sha256');
            $this->invoiceDigitalSignature = base64_encode($signature);
        }

        return $this->invoiceDigitalSignature;
    }

    /**
     * Get invoice qrcode element
     *
     * @return array
     */
    public function getInvoiceQrCodeElement(): array
    {
        $qrCodeString = new QRCodeString([
            $this->supplier->getVatName(),
            $this->supplier->getVatNumber(),
            (string) $this->invoiceIssueDate . 'T' . (string) $this->invoiceIssueTime,
            number_format($this->legalMonetaryTotal->getTaxInclusiveAmount(), 2, '.', ''),
            number_format($this->taxesTotal->getTaxTotal(), 2, '.', ''),
            $this->getInvoiceHashBaseEncoded(),
            $this->getInvoiceDigitalSignature(),
            $this->certificateParser()->getCertificatePublicKeyEncoded(),
            $this->certificateParser()->getCertificateSignature()
        ]);

        return (new Qr())
            ->setQrCode($qrCodeString->toBase64())
            ->getElement();
    }

    /**
     * Get invoice signature element
     *
     * @return array
     */
    public function getInvoiceSignatureElement(): array
    {
        return (new Signature())
            ->getElement();
    }

    /**
     * Set invoice signed properties hash base64 encoded
     */
    public function GetSignedPropertiesHashEncoded()
    {
        $ublDefaults = (new UBLExtensions())
            ->setSigningTimestamp($this->timestamp)
            ->setCertificateHash($this->certificateParser()->getCertificateHashEncoded())
            ->setCertificateIssuer($this->certificateParser()->getCertificateIssuerName())
            ->setCertificateSerialNumber($this->certificateParser()->getCertificateSerialNumber())
            ->getElement();

        $xml = (new XmlBuilder())
            ->build($this->getElement($ublDefaults))
            ->generateAsText();

        // Creating an XMLReader
        $reader = new \XMLReader();
        $reader->xml($xml);

        // Opening a reader
        while ($reader->read()) {
            if ($reader->name == 'xades:QualifyingProperties' && $reader->nodeType === \XmlReader::ELEMENT) {
                $signedProperties = $reader->readInnerXml();
            }
        }

        // Closing the reader
        $reader->close();

        return base64_encode(hash('sha256', trim($signedProperties), false));
    }

    /**
     * Get Signed invoice
     *
     * @return string
     */
    public function getSignedInvoiceEncoded(): string
    {
        $uBLExtensions = (new UBLExtensions())
            ->setInvoiceHash($this->getInvoiceHashBaseEncoded())
            ->setSignedPropertiesHash($this->GetSignedPropertiesHashEncoded())
            ->setDigitalSignature($this->getInvoiceDigitalSignature())
            ->setCertificateValue($this->certificateParser()->getCertificateDecoded())
            ->setSigningTimestamp($this->timestamp)
            ->setCertificateHash($this->certificateParser()->getCertificateHashEncoded())
            ->setCertificateIssuer($this->certificateParser()->getCertificateIssuerName())
            ->setCertificateSerialNumber($this->certificateParser()->getCertificateSerialNumber())
            ->getElement();

        $xml = (new XmlBuilder())
            ->build($this->getElement($uBLExtensions, true))
            ->generateAsText();

        return trim(base64_encode($xml));
    }

    /**
     * Send document to zatca
     *
     * @return mixed
     */
    public function generateDocument(): mixed
    {
        return [
            'hash' => $this->getInvoiceHashBaseEncoded(),
            'uuid' => $this->invoiceUuid,
            'invoice' => $this->getSignedInvoiceEncoded(),
            'signing_time' => $this->timestamp,
            'qr_value' => $this->extractQr($this->getSignedInvoiceEncoded()),
        ];
    }

    /**
     * Send document to zatca
     *
     * @param bool $isProduction
     *
     * @return mixed
     */
    public function sendDocument($hash, $uuid, $invoice, bool $isProduction = false): mixed
    {
        $options['json'] = [
            'invoiceHash' => $hash,
            'uuid' => $uuid,
            'invoice' => $invoice,
        ];

        $url = ZatcaConfig::BaseUrl($this->env);
        if ($isProduction) {
            if ($this->invoiceType == '0200000') {
                $url .= '/invoices/reporting/single';
            } else {
                $url .= '/invoices/clearance/single';
            }
        } else {
            $url .= '/compliance/invoices';
        }

        $client = new \GuzzleHttp\Client(['verify' => false]);

        $options['headers'] = [
            'Content-Type' => 'application/json',
            'Accept-Language' => $this->language,
            'Accept-Version' => 'V2',
            'Clearance-Status' => '1',
            'Accept' => 'application/json'
        ];

        if (empty($this->certificateEncoded) || empty($this->certificateSecret)) {
            throw new Exception('Zatca Basic Auth is required');
        }
        $options['auth'] = [$this->certificateEncoded, $this->certificateSecret];

        try {
            $request = $client->request('POST', $url, $options);
            $response = json_decode($request->getBody()->getContents());
            $xml = '';
            if (!in_array($this->env, ['developer-portal', 'simulation']) && $this->invoiceType != '0200000' && $isProduction == true) {
                $xml = $response->clearedInvoice;
            } else {
                $xml = $invoice;
            }
            return ['success' => true, 'response' => $response, 'hash' => $hash, 'xml' => $xml, 'qr_value' => $this->extractQr($xml)];
        } catch (ClientException $exception) {
            $response = json_decode($exception->getResponse()->getBody()->getContents());
            return ['success' => false, 'response' => $response];
        }
    }

    /**
     * Extract qrcode value from xml
     *
     * @param string $xml
     *
     * @return string
     */
    public function extractQr($xml): string
    {
        $xml_string = base64_decode($xml);
        $element = simplexml_load_string($xml_string);
        $element->registerXPathNamespace('cbc', 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2');
        $element->registerXPathNamespace('cac', 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2');
        $result = $element->xpath('//cac:AdditionalDocumentReference[3]//cac:Attachment//cbc:EmbeddedDocumentBinaryObject')[0];

        return (string) $result;
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
     * The getElement method is called during xml writing.
     *
     * @param array $uBLExtensions
     * @param bool $forSigning
     *
     * @return array
     */
    public function getElement(array $uBLExtensions = [], bool $forSigning = false): array
    {
        return [
            [
                'name' => 'Invoice',
                'value' => null,
                'namespaced' => false,
                'namespace' => null,
                'prefix' => null,
                'attributes' => [
                    [
                        'name' => 'xmlns',
                        'value' => 'urn:oasis:names:specification:ubl:schema:xsd:Invoice-2',
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                    [
                        'name' => 'xmlns:cac',
                        'value' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonAggregateComponents-2',
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                    [
                        'name' => 'xmlns:cbc',
                        'value' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonBasicComponents-2',
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                    [
                        'name' => 'xmlns:ext',
                        'value' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonExtensionComponents-2',
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ],
                'childs' => array_merge((count($uBLExtensions) > 0) ? [$uBLExtensions] : [null], [
                    ($forSigning) ? null : [
                        'name' => 'elementToRemoved',
                        'value' => '',
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'ProfileID',
                        'value' => $this->profileID,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'ID',
                        'value' => $this->invoiceNumber,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'UUID',
                        'value' => $this->invoiceUuid,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'IssueDate',
                        'value' => $this->invoiceIssueDate,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'IssueTime',
                        'value' => $this->invoiceIssueTime,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'InvoiceTypeCode',
                        'value' => $this->invoiceDocumentType,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                        'attributes' => [
                            [
                                'name' => 'name',
                                'value' => $this->invoiceType,
                                'namespaced' => false,
                                'namespace' => null,
                                'prefix' => null,
                            ],
                        ]
                    ],
                    [
                        'name' => 'DocumentCurrencyCode',
                        'value' => $this->invoiceCurrencyCode,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    [
                        'name' => 'TaxCurrencyCode',
                        'value' => $this->invoiceTaxCurrencyCode,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    (!is_null($this->billingReference)) ? [
                        'name' => 'BillingReference',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => [
                            $this->billingReference->getElement()
                        ]
                    ] : null,
                    [
                        'name' => 'AdditionalDocumentReference',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => $this->AdditionalDocumentReference->getElement()
                    ],
                    [
                        'name' => 'AdditionalDocumentReference',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => $this->pih->getElement()
                    ],
                    ($forSigning) ? null : [
                        'name' => 'elementToRemoved',
                        'value' => '',
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    ($forSigning) ? null : [
                        'name' => 'elementToRemoved',
                        'value' => '',
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                    ],
                    (!$forSigning) ? null : [
                        'name' => 'AdditionalDocumentReference',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => $this->getInvoiceQrCodeElement()
                    ],
                    (!$forSigning) ? null : [
                        'name' => 'Signature',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => $this->getInvoiceSignatureElement()
                    ],
                    [
                        'name' => 'AccountingSupplierParty',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => [
                            $this->supplier->getElement()
                        ]
                    ],
                    [
                        'name' => 'AccountingCustomerParty',
                        'value' => (is_null($this->client)) ? ' ' : null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => (!is_null($this->client)) ? [
                            $this->client->getElement()
                        ] : null
                    ],
                    [
                        'name' => 'Delivery',
                        'value' => null,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cac',
                        'childs' => [
                            $this->delivery->getElement()
                        ]
                    ]
                ],
                    [
                        [
                            'name' => 'PaymentMeans',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => $this->getPaymentMeansElement()
                        ]
                    ],
                    $this->allowanceCharges,
                    [
                        [
                            'name' => 'TaxTotal',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                $this->taxesTotal->getElement()
                            ]
                        ],
                        [
                            'name' => 'TaxTotal',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => $this->taxSubTotal
                        ],
                        [
                            'name' => 'LegalMonetaryTotal',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => $this->legalMonetaryTotal->getElement()
                        ]
                    ], $this->invoiceLines)
            ]
        ];
    }
}
