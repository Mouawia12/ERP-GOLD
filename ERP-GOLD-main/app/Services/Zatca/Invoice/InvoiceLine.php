<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two invoice line
 */
class InvoiceLine
{
    private string $lineID;
    private float $lineQuantity;
    private float $lineSubTotal;
    private float $lineTaxTotal;
    private float $lineNetTotal;
    private string $lineName;
    private float $linePrice;
    private string $lineUnitCode = 'PCE';
    private string $lineCurrency;
    private array $lineTaxCategories;
    private string $lineDiscountReason = ' ';
    private float $lineDiscountAmount = 0;

    /**
     * Set line ID
     *
     * @param string $lineID
     *
     * @return $this
     */
    public function setLineID(string $lineID): self
    {
        $this->lineID = $lineID;

        return $this;
    }

    /**
     * Set line quantity
     *
     * @param float $lineQuantity
     *
     * @return $this
     */
    public function setLineQuantity(float $lineQuantity): self
    {
        $this->lineQuantity = $lineQuantity;

        return $this;
    }

    /**
     * Set line sub total without tax
     *
     * @param float $lineSubTotal
     *
     * @return $this
     */
    public function setLineSubTotal(float $lineSubTotal): self
    {
        $this->lineSubTotal = $lineSubTotal;

        return $this;
    }

    /**
     * Set line tax total
     *
     * @param float $lineTaxTotal
     *
     * @return $this
     */
    public function setLineTaxTotal(float $lineTaxTotal): self
    {
        $this->lineTaxTotal = $lineTaxTotal;

        return $this;
    }

    /**
     * Set line net total
     *
     * @param float $lineNetTotal
     *
     * @return $this
     */
    public function setLineNetTotal(float $lineNetTotal): self
    {
        $this->lineNetTotal = $lineNetTotal;

        return $this;
    }

    /**
     * Set line name
     *
     * @param string $lineName
     *
     * @return $this
     */
    public function setLineName(string $lineName): self
    {
        $this->lineName = $lineName;

        return $this;
    }

    /**
     * Set line price
     *
     * @param float $linePrice
     *
     * @return $this
     */
    public function setLinePrice(float $linePrice): self
    {
        $this->linePrice = $linePrice;

        return $this;
    }

    /**
     * Set line currency
     *
     * @param string $lineCurrency
     *
     * @return $this
     */
    public function setLineCurrency(string $lineCurrency): self
    {
        $this->lineCurrency = $lineCurrency;

        return $this;
    }

    /**
     * Set line unit code
     *
     * @param string $lineUnitCode
     *
     * @return $this
     */
    public function setLineUnitCode(string $lineUnitCode): self
    {
        $this->lineUnitCode = !empty($lineUnitCode) ? $lineUnitCode : 'PCE';

        return $this;
    }

    /**
     * Set line tax categories
     *
     * @param array $lineTaxCategories
     *
     * @return $this
     */
    public function setLineTaxCategories(...$lineTaxCategories): self
    {
        $this->lineTaxCategories = $lineTaxCategories;

        return $this;
    }

    /**
     * Set line discount reason
     *
     * @param string $lineDiscountReason
     *
     * @return $this
     */
    public function setLineDiscountReason(string $lineDiscountReason): self
    {
        $this->lineDiscountReason = $lineDiscountReason;

        return $this;
    }

    /**
     * Set line discount amount
     *
     * @param float $lineDiscountAmount
     *
     * @return $this
     */
    public function setLineDiscountAmount(float $lineDiscountAmount): self
    {
        $this->lineDiscountAmount = $lineDiscountAmount;

        return $this;
    }

    /**
     * The getElement method is called during xml writing.
     *
     * @return array
     */
    public function getElement(): array
    {
        array_unshift($this->lineTaxCategories, [
            'name' => 'Name',
            'value' => $this->lineName,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cbc',
        ]);

        return [
            'name' => 'InvoiceLine',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cac',
            'childs' => [
                [
                    'name' => 'ID',
                    'value' => $this->lineID,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                ],
                [
                    'name' => 'InvoicedQuantity',
                    'value' => number_format($this->lineQuantity, 2, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'unitCode',
                            'value' => $this->lineUnitCode,
                            'namespaced' => false,
                            'namespace' => null,
                            'prefix' => null,
                        ],
                    ]
                ],
                [
                    'name' => 'LineExtensionAmount',
                    'value' => number_format($this->lineSubTotal, 2, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'currencyID',
                            'value' => $this->lineCurrency,
                            'namespaced' => false,
                            'namespace' => null,
                            'prefix' => null,
                        ],
                    ]
                ],
                [
                    'name' => 'TaxTotal',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'TaxAmount',
                            'value' => number_format($this->lineTaxTotal, 2, '.', ''),
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'currencyID',
                                    'value' => $this->lineCurrency,
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                            ]
                        ],
                        [
                            'name' => 'RoundingAmount',
                            'value' => number_format($this->lineNetTotal, 2, '.', ''),
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'currencyID',
                                    'value' => $this->lineCurrency,
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                            ]
                        ],
                    ]
                ],
                [
                    'name' => 'Item',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => $this->lineTaxCategories
                ],
                [
                    'name' => 'Price',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        [
                            'name' => 'PriceAmount',
                            'value' => number_format($this->linePrice, 2, '.', ''),
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cbc',
                            'attributes' => [
                                [
                                    'name' => 'currencyID',
                                    'value' => $this->lineCurrency,
                                    'namespaced' => false,
                                    'namespace' => null,
                                    'prefix' => null,
                                ],
                            ]
                        ],
                        [
                            'name' => 'AllowanceCharge',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                [
                                    'name' => 'ChargeIndicator',
                                    'value' => 'false',
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                ],
                                [
                                    'name' => 'AllowanceChargeReason',
                                    'value' => $this->lineDiscountReason,
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                ],
                                [
                                    'name' => 'Amount',
                                    'value' => number_format($this->lineDiscountAmount, 2, '.', ''),
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cbc',
                                    'attributes' => [
                                        [
                                            'name' => 'currencyID',
                                            'value' => $this->lineCurrency,
                                            'namespaced' => false,
                                            'namespace' => null,
                                            'prefix' => null,
                                        ],
                                    ]
                                ],
                            ]
                        ],
                    ]
                ],
            ]
        ];
    }
}
