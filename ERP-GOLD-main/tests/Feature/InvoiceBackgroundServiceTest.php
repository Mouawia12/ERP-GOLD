<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Services\Invoices\InvoiceBackgroundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class InvoiceBackgroundServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_failed_pdf_conversion_keeps_existing_background(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('invoice-backgrounds/current.png', 'existing-image');
        SystemSetting::putValue(InvoiceBackgroundService::SETTING_PATH, 'invoice-backgrounds/current.png');
        SystemSetting::putValue(InvoiceBackgroundService::SETTING_ENABLED, '1');

        $this->expectException(RuntimeException::class);

        try {
            app(InvoiceBackgroundService::class)->upload(
                $this->uploadedFile('broken-letterhead.pdf', 'not a valid pdf')
            );
        } finally {
            $this->assertSame(
                'invoice-backgrounds/current.png',
                SystemSetting::getValue(InvoiceBackgroundService::SETTING_PATH)
            );
            Storage::disk('public')->assertExists('invoice-backgrounds/current.png');
        }
    }

    public function test_pdf_background_is_converted_to_png_when_dependencies_are_available(): void
    {
        if (! class_exists(\Imagick::class) || trim((string) shell_exec('command -v gs 2>/dev/null')) === '') {
            $this->markTestSkipped('Imagick and Ghostscript are required for PDF conversion.');
        }

        Storage::fake('public');

        app(InvoiceBackgroundService::class)->upload(
            $this->uploadedFile('letterhead.pdf', $this->minimalPdf())
        );

        $storedPath = SystemSetting::getValue(InvoiceBackgroundService::SETTING_PATH);

        $this->assertNotNull($storedPath);
        $this->assertStringStartsWith('invoice-backgrounds/', $storedPath);
        $this->assertStringEndsWith('.png', $storedPath);
        $this->assertSame('1', SystemSetting::getValue(InvoiceBackgroundService::SETTING_ENABLED));
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_preview_url_uses_current_host_relative_storage_path(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('invoice-backgrounds/current.png', 'existing-image');
        SystemSetting::putValue(InvoiceBackgroundService::SETTING_PATH, 'invoice-backgrounds/current.png');

        $this->assertStringStartsWith(
            '/storage/invoice-backgrounds/current.png?v=',
            app(InvoiceBackgroundService::class)->rawImageUrl()
        );
    }

    public function test_uploaded_landscape_page_sets_full_page_landscape_preview(): void
    {
        Storage::fake('public');

        app(InvoiceBackgroundService::class)->upload(
            UploadedFile::fake()->image('letterhead-landscape.png', 2356, 1744)
        );

        $this->assertSame('full_page', SystemSetting::getValue(InvoiceBackgroundService::SETTING_RENDER_MODE));
        $this->assertSame('landscape', SystemSetting::getValue(InvoiceBackgroundService::SETTING_ORIENTATION));
        $this->assertSame('2356', SystemSetting::getValue(InvoiceBackgroundService::SETTING_IMAGE_WIDTH));
        $this->assertSame('1744', SystemSetting::getValue(InvoiceBackgroundService::SETTING_IMAGE_HEIGHT));
    }

    public function test_uploaded_wide_strip_is_not_treated_as_full_page(): void
    {
        Storage::fake('public');

        app(InvoiceBackgroundService::class)->upload(
            UploadedFile::fake()->image('letterhead-strip.png', 2400, 500)
        );

        $this->assertSame('wide_strip', SystemSetting::getValue(InvoiceBackgroundService::SETTING_RENDER_MODE));
        $this->assertSame('landscape', SystemSetting::getValue(InvoiceBackgroundService::SETTING_ORIENTATION));
    }

    public function test_footer_visibility_can_be_controlled_independently_from_header(): void
    {
        $service = app(InvoiceBackgroundService::class);

        $service->setHideHeader(true);

        $this->assertTrue($service->isHideHeader(false));
        $this->assertTrue($service->isHideFooter(false));

        $service->setHideFooter(false);

        $this->assertTrue($service->isHideHeader(false));
        $this->assertFalse($service->isHideFooter(false));
    }

    public function test_background_settings_are_scoped_per_branch_with_global_fallback(): void
    {
        SystemSetting::putValue(InvoiceBackgroundService::SETTING_SCALE, '1.25');

        $branchOne = app(InvoiceBackgroundService::class)->forBranch(1);
        $branchTwo = app(InvoiceBackgroundService::class)->forBranch(2);

        $this->assertSame(1.25, $branchOne->currentScale(false));
        $this->assertSame(1.25, $branchTwo->currentScale(false));

        $branchOne->setScale(0.85);

        $this->assertSame(0.85, $branchOne->currentScale(false));
        $this->assertSame(1.25, $branchTwo->currentScale(false));
        $this->assertSame('0.85', SystemSetting::getValue(InvoiceBackgroundService::SETTING_SCALE.':branch:1'));
        $this->assertSame('1.25', SystemSetting::getValue(InvoiceBackgroundService::SETTING_SCALE));
    }

    public function test_invoice_content_scale_is_clamped_and_stored(): void
    {
        $service = app(InvoiceBackgroundService::class);

        $service->setContentScale(1.24);
        $this->assertSame(1.24, $service->currentContentScale(false));
        $this->assertSame('1.24', SystemSetting::getValue(InvoiceBackgroundService::SETTING_CONTENT_SCALE));

        $service->setContentScale(2.0);
        $this->assertSame(1.5, $service->currentContentScale(false));

        $service->setContentScale(0.1);
        $this->assertSame(0.5, $service->currentContentScale(false));
    }

    private function uploadedFile(string $name, string $content): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'invoice-bg-');
        file_put_contents($path, $content);

        return new UploadedFile($path, $name, 'application/pdf', null, true);
    }

    private function minimalPdf(): string
    {
        $objects = [
            '1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj',
            '2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj',
            '3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 200 200] /Contents 4 0 R >> endobj',
            "4 0 obj << /Length 35 >> stream\n0.9 0.9 0.9 rg 0 0 200 200 re f\nendstream endobj",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";

        for ($i = 1; $i <= 4; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer << /Size 5 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";

        return $pdf;
    }
}
