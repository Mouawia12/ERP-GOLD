<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two invoice totals
 */
class LegalMonetaryTotal
{
    private string $totalCurrency;
    private float $lineExtensionAmount;
    private float $taxExclusiveAmount;
    private float $taxInclusiveAmount;
    private float $allowanceTotalAmount;
    private float $prepaidAmount;
    private float $payableAmount;

    /**
     * Set totals currency
     *
     * @param string $totalCurrency
     *
     * @return $this
     */
    public function setTotalCurrency(string $totalCurrency): self
    {
        $this->totalCurrency = $totalCurrency;

        return $this;
    }

    /**
     * Set line extension amount
     *
     * @param float $lineExtensionAmount
     *
     * @return $this
     */
    public function setLineExtensionAmount(float $lineExtensionAmount): self
    {
        $this->lineExtensionAmount = $lineExtensionAmount;

        return $this;
    }

    /**
     * Set tax exclusive amount
     *
     * @param float $taxExclusiveAmount
     *
     * @return $this
     */
    public function setTaxExclusiveAmount(float $taxExclusiveAmount): self
    {
        $this->taxExclusiveAmount = $taxExclusiveAmount;

        return $this;
    }

    /**
     * Set tax inclusive amount
     *
     * @param float $taxInclusiveAmount
     *
     * @return $this
     */
    public function setTaxInclusiveAmount(float $taxInclusiveAmount): self
    {
        $this->taxInclusiveAmount = $taxInclusiveAmount;

        return $this;
    }

    /**
     * Get tax inclusiveAmount amount
     *
     * @return float
     */
    public function getTaxInclusiveAmount(): float
    {
        return $this->taxInclusiveAmount;
    }

    /**
     * Set allowance total amount
     *
     * @param float $allowanceTotalAmount
     *
     * @return $this
     */
    public function setAllowanceTotalAmount(float $allowanceTotalAmount): self
    {
        $this->allowanceTotalAmount = $allowanceTotalAmount;

        return $this;
    }

    /**
     * Set prepaid amount
     *
     * @param float $prepaidAmount
     *
     * @return $this
     */
    public function setPrepaidAmount(float $prepaidAmount): self
    {
        $this->prepaidAmount = $prepaidAmount;

        return $this;
    }

    /**
     * Set payable amount
     *
     * @param float $payableAmount
     *
     * @return $this
     */
    public function setPayableAmount(float $payableAmount): self
    {
        $this->payableAmount = $payableAmount;

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
            [
                'name' => 'LineExtensionAmount',
                'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
            [
                'name' => 'TaxExclusiveAmount',
                'value' => number_format($this->taxExclusiveAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
            [
                'name' => 'TaxInclusiveAmount',
                'value' => number_format($this->taxInclusiveAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
            [
                'name' => 'AllowanceTotalAmount',
                'value' => number_format($this->allowanceTotalAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
            [
                'name' => 'PrepaidAmount',
                'value' => number_format($this->prepaidAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
            [
                'name' => 'PayableAmount',
                'value' => number_format($this->payableAmount, 2, '.', ''),
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
                'attributes' => [
                    [
                        'name' => 'currencyID',
                        'value' => $this->totalCurrency,
                        'namespaced' => false,
                        'namespace' => null,
                        'prefix' => null,
                    ],
                ]
            ],
        ];
    }
}
