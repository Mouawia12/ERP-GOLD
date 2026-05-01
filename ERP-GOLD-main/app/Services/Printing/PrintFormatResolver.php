<?php

namespace App\Services\Printing;

use Illuminate\Http\Request;

class PrintFormatResolver
{
    private const FORMATS = ['a4', 'a5', 'pos'];
    private const ORIENTATIONS = ['portrait', 'landscape'];

    /**
     * @return array{format:string,orientation:string,paper:string,is_pos:bool}
     */
    public function resolve(Request $request, string $defaultFormat = 'a4', string $defaultOrientation = 'portrait'): array
    {
        $format = strtolower((string) $request->query('format', $request->query('paper', $defaultFormat)));
        if (! in_array($format, self::FORMATS, true)) {
            $format = $defaultFormat;
        }

        $orientation = strtolower((string) $request->query('orientation', $defaultOrientation));
        if (! in_array($orientation, self::ORIENTATIONS, true)) {
            $orientation = $defaultOrientation;
        }

        if ($format === 'pos') {
            $orientation = 'portrait';
        }

        return [
            'format' => $format,
            'orientation' => $orientation,
            'paper' => $format === 'pos' ? '80mm auto' : strtoupper($format),
            'is_pos' => $format === 'pos',
        ];
    }

    /**
     * @param  array<string, string>  $viewsByFormat
     */
    public function viewFor(array $viewsByFormat, string $format, string $fallbackView): string
    {
        return $viewsByFormat[$format] ?? $fallbackView;
    }
}
