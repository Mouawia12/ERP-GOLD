<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two allowance charge
 */
class AllowanceCharge
{
    private string $allowanceChargeCurrency;
    private string $allowanceChargeIndex;
    private float $allowanceChargeAmount;
    private string $allowanceChargeTaxCategory;
    private float $allowanceChargeTaxPercentage;

    /**
     * Set allowance currency
     *
     * @param string $allowanceChargeCurrency
     *
     * @return $this
     */
    public function setAllowanceChargeCurrency(string $allowanceChargeCurrency): self
    {
        $this->allowanceChargeCurrency = $allowanceChargeCurrency;

        return $this;
    }

    /**
     * Set allowance index
     *
     * @param string $allowanceChargeIndex
     *
     * @return $this
     */
    public function setAllowanceChargeIndex(string $allowanceChargeIndex): self
    {
        $this->allowanceChargeIndex = $allowanceChargeIndex;

        return $this;
    }

    /**
     * Set allowance amount
     *
     * @param float $allowanceChargeAmount
     *
     * @return $this
     */
    public function setAllowanceChargeAmount(float $allowanceChargeAmount): self
    {
        $this->allowanceChargeAmount = $allowanceChargeAmount;

        return $this;
    }

    /**
     * Set allowance tax category
     *
     * @param string $allowanceChargeTaxCategory
     *
     * @return $this
     */
    public function setAllowanceChargeTaxCategory(string $allowanceChargeTaxCategory): self
    {
        $this->allowanceChargeTaxCategory = $allowanceChargeTaxCategory;

        return $this;
    }

    /**
     * Set allowance tax percentage
     *
     * @param float $allowanceChargeTaxPercentage
     *
     * @return $this
     */
    public function setAllowanceChargeTaxPercentage(float $allowanceChargeTaxPercentage): self
    {
        $this->allowanceChargeTaxPercentage = $allowanceChargeTaxPercentage;

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
            'name' => 'AllowanceCharge',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cac',
            'childs' => [
                [
                    'name' => 'ID',
                    'value' => $this->allowanceChargeIndex,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                ],
                [
                    'name' => 'ChargeIndicator',
                    'value' => 'false',
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                ],
                [
                    'name' => 'AllowanceChargeReason',
                    'value' => 'discount',
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                ],
                [
                    'name' => 'Amount',
                    'value' => number_format($this->allowanceChargeAmount, 2, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'currencyID',
                            'value' => $this->allowanceChargeCurrency,
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
                            'value' => $this->allowanceChargeTaxCategory,
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
                            'value' => $this->allowanceChargeTaxPercentage,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                        ],
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
                                ],
                            ]
                        ],
                    ]
                ],
            ]
        ];
    }
}
