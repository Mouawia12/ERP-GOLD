<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two tax sub totals
 */
class TaxSubtotal
{
    private string $taxCurrencyCode;
    private float $taxableAmount;
    private float $taxAmount;
    private string $taxCategory;
    private float $taxPercentage;
    private string $taxExemptionReasonCode = '';
    private string $taxExemptionReason = '';

    /**
     * Set tax currency code
     *
     * @param string $taxCurrencyCode
     *
     * @return $this
     */
    public function setTaxCurrencyCode(string $taxCurrencyCode): self
    {
        $this->taxCurrencyCode = $taxCurrencyCode;

        return $this;
    }

    /**
     * Set taxable amount
     *
     * @param float $taxableAmount
     *
     * @return $this
     */
    public function setTaxableAmount(float $taxableAmount): self
    {
        $this->taxableAmount = $taxableAmount;

        return $this;
    }

    /**
     * Set tax amount
     *
     * @param float $taxAmount
     *
     * @return $this
     */
    public function setTaxAmount(float $taxAmount): self
    {
        $this->taxAmount = $taxAmount;

        return $this;
    }

    /**
     * Set tax category
     *
     * @param string $taxCategory
     *
     * @return $this
     */
    public function setTaxCategory(string $taxCategory): self
    {
        $this->taxCategory = $taxCategory;

        return $this;
    }

    /**
     * Set tax percentage
     *
     * @param float $taxPercentage
     *
     * @return $this
     */
    public function setTaxPercentage(string $taxPercentage): self
    {
        $this->taxPercentage = $taxPercentage;

        return $this;
    }

    /**
     * Set tax reason code
     *
     * @param string $taxExemptionReasonCode
     *
     * @return $this
     */
    public function setTaxExemptionReasonCode(string $taxExemptionReasonCode): self
    {
        $this->taxExemptionReasonCode = $taxExemptionReasonCode;

        return $this;
    }

    /**
     * Set tax reason
     *
     * @param string $taxExemptionReason
     *
     * @return $this
     */
    public function setTaxExemptionReason(string $taxExemptionReason): self
    {
        $this->taxExemptionReason = $taxExemptionReason;

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
            'name' => 'TaxSubtotal',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cac',
            'childs' => [
                [
                    'name' => 'TaxableAmount',
                    'value' => number_format($this->taxableAmount, 2, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'currencyID',
                            'value' => $this->taxCurrencyCode,
                            'namespaced' => false,
                            'namespace' => null,
                            'prefix' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'TaxAmount',
                    'value' => number_format($this->taxAmount, 2, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'currencyID',
                            'value' => $this->taxCurrencyCode,
                            'namespaced' => false,
                            'namespace' => null,
                            'prefix' => null,
                        ],
                    ],
                ],
                [
                    'name' => 'TaxCategory',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'ID',
                            'value' => $this->taxCategory,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'schemeAgencyID',
                                    'value' => '6',
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                                [
                                    'name' => 'schemeID',
                                    'value' => 'UN/ECE 5305',
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                            ],
                        ],
                        [
                            'name' => 'Percent',
                            'value' => number_format($this->taxPercentage, 2, '.', ''),
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
                        (!empty($this->taxExemptionReasonCode) ? [
                            'name' => 'TaxExemptionReasonCode',
                            'value' => $this->taxExemptionReasonCode,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ] : null),
                        (!empty($this->taxExemptionReason) ? [
                            'name' => 'TaxExemptionReason',
                            'value' => $this->taxExemptionReason,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ] : null),
                        [
                            'name' => 'TaxScheme',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                [
                                    'name' => 'ID',
                                    'value' => 'VAT',
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                    'attributes' => [
                                        [
                                            'name' => 'schemeAgencyID',
                                            'value' => '6',
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                        [
                                            'name' => 'schemeID',
                                            'value' => 'UN/ECE 5153',
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                    ],
                                ]
                            ]
                        ],
                    ],
                ],
            ]
        ];
    }
}
