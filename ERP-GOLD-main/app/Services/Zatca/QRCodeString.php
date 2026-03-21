<?php
namespace App\Services\Zatca;

/**
 * A class defines zatca qr base64 string
 */
class QRCodeString
{
    private string $result = '';

    public function __construct($params)
    {
        foreach ($params as $key => $value) {
            $tag = $key + 1;
            $length = $this->stringLen($value);
            $this->result .= $this->toString($tag, $length, $value);
        }
    }

    /**
     * Get string number of bytes start
     *
     * @param null|string $value
     *
     * @return string
     */
    public function stringLen(null|string $value): string
    {
        return strlen($value);
    }

    /**
     * Get string representing the encoded TLV data structure
     *
     *  @param string $tag
     *  @param string $length
     *  @param null|string $value
     *
     *  @return string
     */
    public function toString(string $tag, string $length, null|string $value): string
    {
        return $this->__toHex($tag) . $this->__toHex($length) . ($value);
    }

    /**
     * Convert the string value to hex start
     *
     * @param $value
     *
     * @return string
     */
    public function __toHex($value): string
    {
        return pack('H*', sprintf('%02X', $value));
    }

    /**
     * Convert the string value to hex end
     *
     * @return string
     */
    public function getResult(): string
    {
        return $this->result;
    }

    /**
     * Convert qr string value to base64 encode
     *
     * @return string
     */
    public function toBase64(): string
    {
        return base64_encode($this->result);
    }
}
