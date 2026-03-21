<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two taxes totals
 */
class TaxesTotal
{
    private string $taxCurrencyCode;
    private float $taxTotal;

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
     * Set tax total
     *
     * @param float $taxTotal
     *
     * @return $this
     */
    public function setTaxTotal(float $taxTotal): self
    {
        $this->taxTotal = $taxTotal;

        return $this;
    }

    /**
     * Get tax total
     *
     * @param float $taxTotal
     *
     * @return float
     */
    public function getTaxTotal(): float
    {
        return $this->taxTotal;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        return [
            'name' => 'TaxAmount',
            'value' => number_format($this->taxTotal, 2, '.', ''),
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
            ]
        ];
    }
}
