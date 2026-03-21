<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two previous hash
 */
class PIH
{
    private string $pih;

    /**
     * Set previous hash
     *
     * @param string $pih
     *
     * @return $this
     */
    public function setPIH(string $pih): self
    {
        $this->pih = $pih;

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
                'value' => 'PIH',
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
                        'value' => $this->pih,
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
