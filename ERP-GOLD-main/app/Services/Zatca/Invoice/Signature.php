<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two signature element
 */
class Signature
{
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
                'value' => 'urn:oasis:names:specification:ubl:signature:Invoice',
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc'
            ],
            [
                'name' => 'SignatureMethod',
                'value' => 'urn:oasis:names:specification:ubl:dsig:enveloped:xades',
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc',
            ],
        ];
    }
}
