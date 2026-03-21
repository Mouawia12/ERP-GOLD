<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two ubl extensions
 */
class UBLExtensions
{
    private ?string $invoiceHash = '';
    private ?string $signedPropertiesHash = '';
    private ?string $digitalSignature = '';
    private ?string $certificateValue = '';
    private ?string $signingTimestamp = '';
    private ?string $certificateHash = '';
    private ?string $certificateIssuer = '';
    private ?string $certificateSerialNumber = '';

    /**
     * Set invoice hash
     *
     * @param string $invoiceHash
     *
     * @return $this
     */
    public function setInvoiceHash(string $invoiceHash): self
    {
        $this->invoiceHash = $invoiceHash;

        return $this;
    }

    /**
     * Set signed properties hash
     *
     * @param string $signedPropertiesHash
     *
     * @return $this
     */
    public function setSignedPropertiesHash(string $signedPropertiesHash): self
    {
        $this->signedPropertiesHash = $signedPropertiesHash;

        return $this;
    }

    /**
     * Set digital signature
     *
     * @param string $digitalSignature
     *
     * @return $this
     */
    public function setDigitalSignature(string $digitalSignature): self
    {
        $this->digitalSignature = $digitalSignature;

        return $this;
    }

    /**
     * Set certificate value
     *
     * @param string $certificateValue
     *
     * @return $this
     */
    public function setCertificateValue(string $certificateValue): self
    {
        $this->certificateValue = $certificateValue;

        return $this;
    }

    /**
     * Set signing timestamp
     *
     * @param string $signingTimestamp
     *
     * @return $this
     */
    public function setSigningTimestamp(string $signingTimestamp): self
    {
        $this->signingTimestamp = $signingTimestamp;

        return $this;
    }

    /**
     * Set certificate hash
     *
     * @param string $certificateHash
     *
     * @return $this
     */
    public function setCertificateHash(string $certificateHash): self
    {
        $this->certificateHash = $certificateHash;

        return $this;
    }

    /**
     * Set certificate issuer
     *
     * @param string $certificateIssuer
     *
     * @return $this
     */
    public function setCertificateIssuer(string $certificateIssuer): self
    {
        $this->certificateIssuer = $certificateIssuer;

        return $this;
    }

    /**
     * Set certificate serial number
     *
     * @param string $certificateSerialNumber
     *
     * @return $this
     */
    public function setCertificateSerialNumber(string $certificateSerialNumber): self
    {
        $this->certificateSerialNumber = $certificateSerialNumber;

        return $this;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        return [
            'name' => 'UBLExtensions',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'ext',
            'childs' => [
                [
                    'name' => 'UBLExtension',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'ext',
                    'childs' => [
                        [
                            'name' => 'ExtensionURI',
                            'value' => 'urn:oasis:names:specification:ubl:dsig:enveloped:xades',
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'ext',
                        ],
                        [
                            'name' => 'ExtensionContent',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'ext',
                            'childs' => [
                                [
                                    'name' => 'UBLDocumentSignatures',
                                    'value' => null,
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'sig',
                                    'attributes' => [
                                        [
                                            'name' => 'xmlns:sac',
                                            'value' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureAggregateComponents-2',
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                        [
                                            'name' => 'xmlns:sbc',
                                            'value' => 'urn:oasis:names:specification:ubl:schema:xsd:SignatureBasicComponents-2',
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                        [
                                            'name' => 'xmlns:sig',
                                            'value' => 'urn:oasis:names:specification:ubl:schema:xsd:CommonSignatureComponents-2',
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                    ],
                                    'childs' => [
                                        [
                                            'name' => 'SignatureInformation',
                                            'value' => null,
                                            'namespaced' => true,
                                            'namespace' => null,
                                            'prefix' => 'sac',
                                            'childs' => [
                                                [
                                                    'name' => 'ID',
                                                    'value' => 'urn:oasis:names:specification:ubl:signature:1',
                                                    'namespaced' => true,
                                                    'namespace' => null,
                                                    'prefix' => 'cbc',
                                                ],
                                                [
                                                    'name' => 'ReferencedSignatureID',
                                                    'value' => 'urn:oasis:names:specification:ubl:signature:Invoice',
                                                    'namespaced' => true,
                                                    'namespace' => null,
                                                    'prefix' => 'sbc',
                                                ],
                                                [
                                                    'name' => 'Signature',
                                                    'value' => null,
                                                    'namespaced' => true,
                                                    'namespace' => null,
                                                    'prefix' => 'ds',
                                                    'attributes' => [
                                                        [
                                                            'name' => 'Id',
                                                            'value' => 'signature',
                                                            'namespaced' => false,
                                                            'namespace' => null,
                                                            'prefix' => null,
                                                        ],
                                                        [
                                                            'name' => 'xmlns:ds',
                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#',
                                                            'namespaced' => false,
                                                            'namespace' => null,
                                                            'prefix' => null,
                                                        ],
                                                    ],
                                                    'childs' => [
                                                        [
                                                            'name' => 'SignedInfo',
                                                            'value' => null,
                                                            'namespaced' => true,
                                                            'namespace' => null,
                                                            'prefix' => 'ds',
                                                            'childs' => [
                                                                [
                                                                    'name' => 'CanonicalizationMethod',
                                                                    'value' => '',
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'ds',
                                                                    'attributes' => [
                                                                        [
                                                                            'name' => 'Algorithm',
                                                                            'value' => 'http://www.w3.org/2006/12/xml-c14n11',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                    ],
                                                                ],
                                                                [
                                                                    'name' => 'SignatureMethod',
                                                                    'value' => '',
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'ds',
                                                                    'attributes' => [
                                                                        [
                                                                            'name' => 'Algorithm',
                                                                            'value' => 'http://www.w3.org/2001/04/xmldsig-more#ecdsa-sha256',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                    ],
                                                                ],
                                                                [
                                                                    'name' => 'Reference',
                                                                    'value' => null,
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'ds',
                                                                    'attributes' => [
                                                                        [
                                                                            'name' => 'Id',
                                                                            'value' => 'invoiceSignedData',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                        [
                                                                            'name' => 'URI',
                                                                            'value' => '',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                    ],
                                                                    'childs' => [
                                                                        [
                                                                            'name' => 'Transforms',
                                                                            'value' => '',
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                            'childs' => [
                                                                                [
                                                                                    'name' => 'Transform',
                                                                                    'value' => null,
                                                                                    'namespaced' => true,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'ds',
                                                                                    'attributes' => [
                                                                                        [
                                                                                            'name' => 'Algorithm',
                                                                                            'value' => 'http://www.w3.org/TR/1999/REC-xpath-19991116',
                                                                                            'namespaced' => false,
                                                                                            'namespace' => null,
                                                                                            'prefix' => null,
                                                                                        ],
                                                                                    ],
                                                                                    'childs' => [
                                                                                        [
                                                                                            'name' => 'XPath',
                                                                                            'value' => 'not(//ancestor-or-self::ext:UBLExtensions)',
                                                                                            'namespaced' => true,
                                                                                            'namespace' => null,
                                                                                            'prefix' => 'ds',
                                                                                        ]
                                                                                    ]
                                                                                ],
                                                                                [
                                                                                    'name' => 'Transform',
                                                                                    'value' => null,
                                                                                    'namespaced' => true,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'ds',
                                                                                    'attributes' => [
                                                                                        [
                                                                                            'name' => 'Algorithm',
                                                                                            'value' => 'http://www.w3.org/TR/1999/REC-xpath-19991116',
                                                                                            'namespaced' => false,
                                                                                            'namespace' => null,
                                                                                            'prefix' => null,
                                                                                        ],
                                                                                    ],
                                                                                    'childs' => [
                                                                                        [
                                                                                            'name' => 'XPath',
                                                                                            'value' => 'not(//ancestor-or-self::cac:Signature)',
                                                                                            'namespaced' => true,
                                                                                            'namespace' => null,
                                                                                            'prefix' => 'ds',
                                                                                        ]
                                                                                    ]
                                                                                ],
                                                                                [
                                                                                    'name' => 'Transform',
                                                                                    'value' => null,
                                                                                    'namespaced' => true,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'ds',
                                                                                    'attributes' => [
                                                                                        [
                                                                                            'name' => 'Algorithm',
                                                                                            'value' => 'http://www.w3.org/TR/1999/REC-xpath-19991116',
                                                                                            'namespaced' => false,
                                                                                            'namespace' => null,
                                                                                            'prefix' => null,
                                                                                        ],
                                                                                    ],
                                                                                    'childs' => [
                                                                                        [
                                                                                            'name' => 'XPath',
                                                                                            'value' => "not(//ancestor-or-self::cac:AdditionalDocumentReference[cbc:ID='QR'])",
                                                                                            'namespaced' => true,
                                                                                            'namespace' => null,
                                                                                            'prefix' => 'ds',
                                                                                        ]
                                                                                    ]
                                                                                ],
                                                                                [
                                                                                    'name' => 'Transform',
                                                                                    'value' => '',
                                                                                    'namespaced' => true,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'ds',
                                                                                    'attributes' => [
                                                                                        [
                                                                                            'name' => 'Algorithm',
                                                                                            'value' => 'http://www.w3.org/2006/12/xml-c14n11',
                                                                                            'namespaced' => false,
                                                                                            'namespace' => null,
                                                                                            'prefix' => null,
                                                                                        ],
                                                                                    ],
                                                                                ],
                                                                            ]
                                                                        ],
                                                                        [
                                                                            'name' => 'DigestMethod',
                                                                            'value' => '',
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                            'attributes' => [
                                                                                [
                                                                                    'name' => 'Algorithm',
                                                                                    'value' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                                                                                    'namespaced' => false,
                                                                                    'namespace' => null,
                                                                                    'prefix' => null,
                                                                                ],
                                                                            ],
                                                                        ],
                                                                        [
                                                                            'name' => 'DigestValue',
                                                                            'value' => $this->invoiceHash,
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                        ],
                                                                    ]
                                                                ],
                                                                [
                                                                    'name' => 'Reference',
                                                                    'value' => null,
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'ds',
                                                                    'attributes' => [
                                                                        [
                                                                            'name' => 'Type',
                                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#SignatureProperties',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                        [
                                                                            'name' => 'URI',
                                                                            'value' => '#xadesSignedProperties',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                    ],
                                                                    'childs' => [
                                                                        [
                                                                            'name' => 'DigestMethod',
                                                                            'value' => '',
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                            'attributes' => [
                                                                                [
                                                                                    'name' => 'Algorithm',
                                                                                    'value' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                                                                                    'namespaced' => false,
                                                                                    'namespace' => null,
                                                                                    'prefix' => null,
                                                                                ],
                                                                            ],
                                                                        ],
                                                                        [
                                                                            'name' => 'DigestValue',
                                                                            'value' => $this->signedPropertiesHash,
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                        ],
                                                                    ]
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'name' => 'SignatureValue',
                                                            'value' => $this->digitalSignature,
                                                            'namespaced' => true,
                                                            'namespace' => null,
                                                            'prefix' => 'ds',
                                                        ],
                                                        [
                                                            'name' => 'KeyInfo',
                                                            'value' => null,
                                                            'namespaced' => true,
                                                            'namespace' => null,
                                                            'prefix' => 'ds',
                                                            'childs' => [
                                                                [
                                                                    'name' => 'X509Data',
                                                                    'value' => null,
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'ds',
                                                                    'childs' => [
                                                                        [
                                                                            'name' => 'X509Certificate',
                                                                            'value' => $this->certificateValue,
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'ds',
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ],
                                                        [
                                                            'name' => 'Object',
                                                            'value' => null,
                                                            'namespaced' => true,
                                                            'namespace' => null,
                                                            'prefix' => 'ds',
                                                            'childs' => [
                                                                [
                                                                    'name' => 'QualifyingProperties',
                                                                    'value' => null,
                                                                    'namespaced' => true,
                                                                    'namespace' => null,
                                                                    'prefix' => 'xades',
                                                                    'attributes' => [
                                                                        [
                                                                            'name' => 'Target',
                                                                            'value' => 'signature',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                        [
                                                                            'name' => 'xmlns:xades',
                                                                            'value' => 'http://uri.etsi.org/01903/v1.3.2#',
                                                                            'namespaced' => false,
                                                                            'namespace' => null,
                                                                            'prefix' => null,
                                                                        ],
                                                                    ],
                                                                    'childs' => [
                                                                        [
                                                                            'name' => 'SignedProperties',
                                                                            'value' => null,
                                                                            'namespaced' => true,
                                                                            'namespace' => null,
                                                                            'prefix' => 'xades',
                                                                            'attributes' => [
                                                                                [
                                                                                    'name' => 'xmlns:xades',
                                                                                    'value' => 'http://uri.etsi.org/01903/v1.3.2#',
                                                                                    'namespaced' => false,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'xades',
                                                                                ],
                                                                                [
                                                                                    'name' => 'Id',
                                                                                    'value' => 'xadesSignedProperties',
                                                                                    'namespaced' => false,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'xades',
                                                                                ],
                                                                            ],
                                                                            'childs' => [
                                                                                [
                                                                                    'name' => 'SignedSignatureProperties',
                                                                                    'value' => null,
                                                                                    'namespaced' => true,
                                                                                    'namespace' => null,
                                                                                    'prefix' => 'xades',
                                                                                    'childs' => [
                                                                                        [
                                                                                            'name' => 'SigningTime',
                                                                                            'value' => $this->signingTimestamp,
                                                                                            'namespaced' => true,
                                                                                            'namespace' => null,
                                                                                            'prefix' => 'xades',
                                                                                        ],
                                                                                        [
                                                                                            'name' => 'SigningCertificate',
                                                                                            'value' => null,
                                                                                            'namespaced' => true,
                                                                                            'namespace' => null,
                                                                                            'prefix' => 'xades',
                                                                                            'childs' => [
                                                                                                [
                                                                                                    'name' => 'Cert',
                                                                                                    'value' => null,
                                                                                                    'namespaced' => true,
                                                                                                    'namespace' => null,
                                                                                                    'prefix' => 'xades',
                                                                                                    'childs' => [
                                                                                                        [
                                                                                                            'name' => 'CertDigest',
                                                                                                            'value' => null,
                                                                                                            'namespaced' => true,
                                                                                                            'namespace' => null,
                                                                                                            'prefix' => 'xades',
                                                                                                            'childs' => [
                                                                                                                [
                                                                                                                    'name' => 'DigestMethod',
                                                                                                                    'value' => ' ',
                                                                                                                    'namespaced' => true,
                                                                                                                    'namespace' => null,
                                                                                                                    'prefix' => 'ds',
                                                                                                                    'attributes' => [
                                                                                                                        [
                                                                                                                            'name' => 'xmlns:ds',
                                                                                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#',
                                                                                                                            'namespaced' => false,
                                                                                                                        ],
                                                                                                                        [
                                                                                                                            'name' => 'Algorithm',
                                                                                                                            'value' => 'http://www.w3.org/2001/04/xmlenc#sha256',
                                                                                                                            'namespaced' => false,
                                                                                                                        ],
                                                                                                                    ],
                                                                                                                ],
                                                                                                                [
                                                                                                                    'name' => 'DigestValue',
                                                                                                                    'value' => $this->certificateHash,
                                                                                                                    'namespaced' => true,
                                                                                                                    'namespace' => null,
                                                                                                                    'prefix' => 'ds',
                                                                                                                    'attributes' => [
                                                                                                                        [
                                                                                                                            'name' => 'xmlns:ds',
                                                                                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#',
                                                                                                                            'namespaced' => false,
                                                                                                                        ],
                                                                                                                    ],
                                                                                                                ],
                                                                                                            ]
                                                                                                        ],
                                                                                                        [
                                                                                                            'name' => 'IssuerSerial',
                                                                                                            'value' => null,
                                                                                                            'namespaced' => true,
                                                                                                            'namespace' => null,
                                                                                                            'prefix' => 'xades',
                                                                                                            'childs' => [
                                                                                                                [
                                                                                                                    'name' => 'X509IssuerName',
                                                                                                                    'value' => $this->certificateIssuer,
                                                                                                                    'namespaced' => true,
                                                                                                                    'namespace' => null,
                                                                                                                    'prefix' => 'ds',
                                                                                                                    'attributes' => [
                                                                                                                        [
                                                                                                                            'name' => 'xmlns:ds',
                                                                                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#',
                                                                                                                            'namespaced' => false,
                                                                                                                        ],
                                                                                                                    ],
                                                                                                                ],
                                                                                                                [
                                                                                                                    'name' => 'X509SerialNumber',
                                                                                                                    'value' => $this->certificateSerialNumber,
                                                                                                                    'namespaced' => true,
                                                                                                                    'namespace' => null,
                                                                                                                    'prefix' => 'ds',
                                                                                                                    'attributes' => [
                                                                                                                        [
                                                                                                                            'name' => 'xmlns:ds',
                                                                                                                            'value' => 'http://www.w3.org/2000/09/xmldsig#',
                                                                                                                            'namespaced' => false,
                                                                                                                        ],
                                                                                                                    ],
                                                                                                                ],
                                                                                                            ]
                                                                                                        ],
                                                                                                    ]
                                                                                                ],
                                                                                            ]
                                                                                        ]
                                                                                    ]
                                                                                ]
                                                                            ]
                                                                        ]
                                                                    ]
                                                                ]
                                                            ]
                                                        ],
                                                    ]
                                                ],
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ];
    }
}
