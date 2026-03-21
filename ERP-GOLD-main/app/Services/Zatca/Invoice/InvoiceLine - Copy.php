<?php
namespace App\Services\Zatca\Invoice;

class InvoiceLine
{
    private string $lineID;
    private float $lineQuantity;
    private float $lineExtensionAmount;
    private string $lineCurrency;
    private array $documentReference = [];
    private array $tax = [];
    private string $itemName;
    private float $priceAmount;

    public function setLineID(string $lineID): self
    {
        $this->lineID = $lineID;
        return $this;
    }

    public function setLineQuantity(float $quantity): self
    {
        $this->lineQuantity = $quantity;
        return $this;
    }

    public function setLineExtensionAmount(float $amount): self
    {
        $this->lineExtensionAmount = $amount;
        return $this;
    }

    public function setLineCurrency(string $currency): self
    {
        $this->lineCurrency = $currency;
        return $this;
    }

    public function setDocumentReference(string $id, string $uuid, string $date, string $time, string $typeCode): self
    {
        $this->documentReference = compact('id', 'uuid', 'date', 'time', 'typeCode');
        return $this;
    }

    public function setTax(float $taxableAmount, float $taxAmount, float $rounding, float $percent, string $categoryID, string $schemeID): self
    {
        $this->tax = compact('taxableAmount', 'taxAmount', 'rounding', 'percent', 'categoryID', 'schemeID');
        return $this;
    }

    public function setItem(string $name): self
    {
        $this->itemName = $name;
        return $this;
    }

    public function setPriceAmount(float $amount): self
    {
        $this->priceAmount = $amount;
        return $this;
    }

    public function getElement(): array
    {
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
                    'value' => number_format($this->lineQuantity, 6, '.', ''),
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                    'attributes' => [
                        [
                            'name' => 'unitCode',
                            'value' => 'PCE',
                            'namespaced' => false,
                            'namespace' => null,
                            'prefix' => null,
                        ]
                    ]
                ],
                [
                    'name' => 'LineExtensionAmount',
                    'value' => number_format($this->lineExtensionAmount, 2, '.', ''),
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
                        ]
                    ]
                ],
                [
                    'name' => 'DocumentReference',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        ['name' => 'ID', 'value' => $this->documentReference['id'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                        ['name' => 'UUID', 'value' => $this->documentReference['uuid'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                        ['name' => 'IssueDate', 'value' => $this->documentReference['date'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                        ['name' => 'IssueTime', 'value' => $this->documentReference['time'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                        ['name' => 'DocumentTypeCode', 'value' => $this->documentReference['typeCode'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                    ]
                ],
                [
                    'name' => 'TaxTotal',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        ['name' => 'TaxAmount', 'value' => number_format($this->tax['taxAmount'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc', 'attributes' => [['name' => 'currencyID', 'value' => $this->lineCurrency, 'namespaced' => false, 'namespace' => null, 'prefix' => null]]],
                        ['name' => 'RoundingAmount', 'value' => number_format($this->tax['rounding'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc', 'attributes' => [['name' => 'currencyID', 'value' => $this->lineCurrency, 'namespaced' => false, 'namespace' => null, 'prefix' => null]]],
                        [
                            'name' => 'TaxSubtotal',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                ['name' => 'TaxableAmount', 'value' => number_format($this->tax['taxableAmount'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc', 'attributes' => [['name' => 'currencyID', 'value' => $this->lineCurrency, 'namespaced' => false, 'namespace' => null, 'prefix' => null]]],
                                ['name' => 'TaxAmount', 'value' => number_format($this->tax['taxAmount'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc', 'attributes' => [['name' => 'currencyID', 'value' => $this->lineCurrency, 'namespaced' => false, 'namespace' => null, 'prefix' => null]]],
                                [
                                    'name' => 'TaxCategory',
                                    'value' => null,
                                    'namespaced' => true,
                                    'namespace' => null,
                                    'prefix' => 'cac',
                                    'childs' => [
                                        ['name' => 'ID', 'value' => $this->tax['categoryID'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                                        ['name' => 'Percent', 'value' => number_format($this->tax['percent'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                                        ['name' => 'TaxScheme', 'value' => null, 'namespaced' => true, 'namespace' => null, 'prefix' => 'cac', 'childs' => [['name' => 'ID', 'value' => $this->tax['schemeID'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc']]]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                [
                    'name' => 'Item',
                    'value' => null,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cac',
                    'childs' => [
                        ['name' => 'Name', 'value' => $this->itemName, 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                        [
                            'name' => 'ClassifiedTaxCategory',
                            'value' => null,
                            'namespaced' => true,
                            'namespace' => null,
                            'prefix' => 'cac',
                            'childs' => [
                                ['name' => 'ID', 'value' => $this->tax['categoryID'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                                ['name' => 'Percent', 'value' => number_format($this->tax['percent'], 2, '.', ''), 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc'],
                                ['name' => 'TaxScheme', 'value' => null, 'namespaced' => true, 'namespace' => null, 'prefix' => 'cac', 'childs' => [['name' => 'ID', 'value' => $this->tax['schemeID'], 'namespaced' => true, 'namespace' => null, 'prefix' => 'cbc']]]
                            ]
                        ]
                    ]
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
                            'value' => number_format($this->priceAmount, 2, '.', ''),
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
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
