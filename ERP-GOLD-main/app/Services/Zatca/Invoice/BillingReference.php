<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two billing reference when credit or debit notes
 */
class BillingReference
{
    private string $billingReference;

    /**
     * Set billing reference
     *
     * @param string $billingReference
     *
     * @return $this
     */
    public function setBillingReference(string $billingReference): self
    {
        $this->billingReference = $billingReference;

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
            'name' => 'InvoiceDocumentReference',
            'value' => null,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cac',
            'childs' => [
                [
                    'name' => 'ID',
                    'value' => 'Invoice Number: ' . $this->billingReference,
                    'namespaced' => true,
                    'namespace' => null,
                    'prefix' => 'cbc',
                ]
            ]
        ];
    }
}
