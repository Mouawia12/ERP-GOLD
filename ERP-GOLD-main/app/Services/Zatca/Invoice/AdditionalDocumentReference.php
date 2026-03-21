<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two invoice additional document reference
 */
class AdditionalDocumentReference
{
    private string $invoiceID;

    /**
     * Set invoice ID
     *
     * @param string $invoiceID
     *
     * @return $this
     */
    public function setInvoiceID(string $invoiceID): self
    {
        $this->invoiceID = $invoiceID;

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
                'name' => 'ID',
                'value' => 'ICV',
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
            ],
            [
                'name' => 'UUID',
                'value' => $this->invoiceID,
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
            ],
        ];
    }
}
