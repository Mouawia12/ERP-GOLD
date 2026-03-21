<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two qr element
 */
class Qr
{
    private string $qrCode;

    /**
     * Set qr value
     *
     * @param string $qrCode
     *
     * @return $this
     */
    public function setQrCode(string $qrCode): self
    {
        $this->qrCode = $qrCode;

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
                'value' => 'QR',
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cbc'
            ],
            [
                'name' => 'Attachment',
                'value' => null,
                'namespaced' => true,
                'namespace' => null,
                'prefix' => 'cac',
                'childs' => [
                    [
                        'name' => 'EmbeddedDocumentBinaryObject',
                        'value' => $this->qrCode,
                        'namespaced' => true,
                        'namespace' => null,
                        'prefix' => 'cbc',
                        'attributes' => [
                            [
                                'name' => 'mimeCode',
                                'value' => 'text/plain',
                                'namespaced' => false,
                                'namespace' => null,
                                'prefix' => null,
                            ],
                        ]
                    ]
                ]
            ],
        ];
    }
}
