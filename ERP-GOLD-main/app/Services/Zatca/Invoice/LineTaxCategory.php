<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two line tax category
 */
class LineTaxCategory
{
    private string $taxCategory;
    private float $taxPercentage;

    /**
     * Set item tax category
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
     * Set item tax percentage
     *
     * @param float $taxPercentage
     *
     * @return $this
     */
    public function setTaxPercentage(float $taxPercentage): self
    {
        $this->taxPercentage = $taxPercentage;

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
            'name' => 'ClassifiedTaxCategory',
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
                ],
                [
                    'name' => 'Percent',
                    'value' => number_format($this->taxPercentage, 2, '.', ''),
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
                        ]
                    ]
                ],
            ]
        ];
    }
}
