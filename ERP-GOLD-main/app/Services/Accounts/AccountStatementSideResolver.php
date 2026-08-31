<?php

namespace App\Services\Accounts;

/**
 * قسم الحساب يُشتق من قائمة الحساب ولا يُختار يدويًا.
 *
 * الأصول والالتزامات وحقوق الملكية حسابات مركز مالي، أما الإيرادات والمصروفات
 * فحسابات قائمة دخل. القاعدة محاسبية ثابتة، وتركها اختيارًا حرًّا كان يسمح بحفظ
 * حساب مصروفات على «مركز مالي» فيختل ظهوره في التقارير.
 */
class AccountStatementSideResolver
{
    /** @var array<string, string> */
    private const SIDE_BY_LIST = [
        'assets' => 'budget',
        'liabilities' => 'budget',
        'equity' => 'budget',
        'revenues' => 'income_statement',
        'expenses' => 'income_statement',
    ];

    /**
     * القسم الموافق لقائمة الحساب، و`not_have` لقائمة غير محدّدة.
     */
    public function forList(?string $accountList): string
    {
        return self::SIDE_BY_LIST[$accountList] ?? 'not_have';
    }

    /**
     * الخريطة كاملة لتغذية الشاشة بها بدل تكرارها في JavaScript.
     *
     * @return array<string, string>
     */
    public function map(): array
    {
        return self::SIDE_BY_LIST;
    }
}
