<?php
namespace App\Services\Zatca;

use Illuminate\Support\Arr;
use Exception;

/**
 * A class defines zatca required integration defaults
 */
class ZatcaConfig
{
    /**
     * Get base url depended on zatca environment
     *
     * @param string $env
     *
     * @return string
     */
    public static function baseUrl(string $env): string
    {
        return "https://gw-fatoora.zatca.gov.sa/e-invoicing/$env";
    }

    /**
     * Get zatca environment types
     *
     * @return array
     */
    public static function getEnvironments(): array
    {
        return [
            'developer-portal',
            'simulation',
            'core'
        ];
    }

    /**
     * Get zatca certificate templates
     *
     * @param string $env
     *
     * @return string
     */
    public static function getCertificateTemplates(string $env): string
    {
        $templates = [
            'developer-portal' => 'TSTZATCA-Code-Signing',
            'simulation' => 'PREZATCA-Code-Signing',
            'core' => 'ZATCA-Code-Signing',
        ];

        return $templates[$env];
    }

    /**
     * Get zatca invoice types into array
     *
     * @return array
     */
    public static function getInvoiceTypes(): array
    {
        return [
            '1100',  // for simplified and standard invoices
            '0100',  // for simplified invoices
            '1000',  // for standard invoices
        ];
    }
}
