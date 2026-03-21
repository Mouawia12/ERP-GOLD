<?php
namespace App\Services\Zatca\Invoice;

/**
 * A class defines zatca phase two return reasons
 */
class ReturnReason
{
    private string $returnReason;

    /**
     * Set return reason
     *
     * @param string $returnReason
     *
     * @return $this
     */
    public function setReturnReason(string $returnReason): self
    {
        $this->returnReason = $returnReason;

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
            'name' => 'InstructionNote',
            'value' => $this->returnReason,
            'namespaced' => true,
            'namespace' => null,
            'prefix' => 'cbc',
        ];
    }
}
