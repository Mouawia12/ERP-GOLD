<?php

namespace App\Models;

use App\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['description'];
    protected $guarded = ['id'];

    public function openingBalances()
    {
        return $this->hasMany(OpeningBalance::class, 'financial_year');
    }

    /**
     * السنة المالية النشطة، أو `null` إن لم تُفعَّل واحدة.
     */
    public static function active(): ?self
    {
        return static::query()->where('is_active', true)->first();
    }

    /**
     * السنة المالية النشطة، وإلا رُفضت العملية برسالة مفهومة.
     *
     * كل مستند محاسبي يحتاج سنة مالية. قراءة `->id` على `null` كانت تعطي
     * «Attempt to read property id on null» — رسالة لا تدل المستخدم على شيء.
     * الاستدعاءات كلها داخل معاملة، فرفع الاستثناء يُرجِعها كاملة بلا كتابة
     * جزئية.
     *
     * @throws \RuntimeException
     */
    public static function activeOrFail(): self
    {
        $financialYear = static::active();

        if (! $financialYear) {
            throw new \RuntimeException(
                'لا توجد سنة مالية نشطة، فلا يمكن تنفيذ هذه العملية. فعّل سنة مالية أولًا.'
            );
        }

        return $financialYear;
    }
}
