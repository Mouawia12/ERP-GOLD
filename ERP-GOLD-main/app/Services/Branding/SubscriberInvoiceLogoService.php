<?php

namespace App\Services\Branding;

use App\Models\Subscriber;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SubscriberInvoiceLogoService
{
    public function logoUrl(Subscriber $subscriber): ?string
    {
        return $subscriber->invoiceLogoUrl();
    }

    public function storeUploadedLogo(Subscriber $subscriber, UploadedFile $file): string
    {
        $oldPath = $subscriber->invoice_logo_path;
        $newPath = $file->store('subscriber-invoice-logos', 'public');

        if ($oldPath && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        $subscriber->forceFill(['invoice_logo_path' => $newPath])->save();

        return $newPath;
    }

    public function deleteLogo(Subscriber $subscriber): void
    {
        $path = $subscriber->invoice_logo_path;

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        $subscriber->forceFill(['invoice_logo_path' => null])->save();
    }
}
