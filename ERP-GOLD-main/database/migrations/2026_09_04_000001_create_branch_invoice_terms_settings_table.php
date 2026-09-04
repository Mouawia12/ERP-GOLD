<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_invoice_terms_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->unique()->constrained()->cascadeOnDelete();
            $table->json('templates')->nullable();
            $table->json('default_template_keys')->nullable();
            $table->timestamps();
        });

        $this->seedBranchesFromExistingSettings();
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_invoice_terms_settings');
    }

    /**
     * كانت الشروط مخزَّنة لكل مستخدم، فنسخة كل موظف تجمّدت يوم أول دخول له
     * ولم يصل إليها تعديل المالك أبدًا. ننقل الشروط إلى الفرع، ونبدأ كل فرع
     * من النصّ الذي يطبعه موظفوه اليوم فعلًا حتى لا تتغيّر فاتورة واحدة
     * لحظة النشر — بعدها يصبح تعديل المالك ظاهرًا على فواتير فرعه فورًا.
     */
    private function seedBranchesFromExistingSettings(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $globalTemplates = $this->decodeSetting('invoice_terms_templates');
        $globalDefaultKeys = $this->decodeSetting('default_invoice_terms_template_keys');
        $now = now();
        $rows = [];

        foreach (DB::table('branches')->pluck('id') as $branchId) {
            $source = $this->branchUserSettings((int) $branchId);
            $templates = $source['templates'] ?? $globalTemplates;
            $defaultKeys = $source['default_template_keys'] ?? $globalDefaultKeys;

            if ($templates === null && $defaultKeys === null) {
                continue;
            }

            $rows[] = [
                'branch_id' => $branchId,
                'templates' => $templates === null ? null : json_encode($templates, JSON_UNESCAPED_UNICODE),
                'default_template_keys' => $defaultKeys === null ? null : json_encode($defaultKeys, JSON_UNESCAPED_UNICODE),
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('branch_invoice_terms_settings')->insert($rows);
        }
    }

    /**
     * قوالب أحد مستخدمي الفرع: هي المصدر الأدقّ لما تطبعه فواتير هذا الفرع
     * اليوم. تُفضَّل نسخة المستخدم الافتراضي للفرع ثم أقدم نسخة متاحة.
     *
     * @return array{templates?: mixed, default_template_keys?: mixed}
     */
    private function branchUserSettings(int $branchId): array
    {
        if (! Schema::hasTable('user_invoice_terms_settings') || ! Schema::hasTable('users')) {
            return [];
        }

        $userIds = DB::table('users')->where('branch_id', $branchId)->pluck('id')->all();

        if (Schema::hasTable('branch_user')) {
            $assignedIds = DB::table('branch_user')
                ->where('branch_id', $branchId)
                ->orderByDesc('is_default')
                ->pluck('user_id')
                ->all();

            $userIds = array_values(array_unique(array_merge($userIds, $assignedIds)));
        }

        if ($userIds === []) {
            return [];
        }

        $settings = DB::table('user_invoice_terms_settings')
            ->whereIn('user_id', $userIds)
            ->orderBy('id')
            ->first();

        if ($settings === null) {
            return [];
        }

        return [
            'templates' => json_decode((string) $settings->templates, true),
            'default_template_keys' => json_decode((string) $settings->default_template_keys, true),
        ];
    }

    private function decodeSetting(string $key): mixed
    {
        if (! Schema::hasTable('system_settings')) {
            return null;
        }

        $value = DB::table('system_settings')->where('key', $key)->value('value');
        $decoded = json_decode((string) $value, true);

        return is_array($decoded) && $decoded !== [] ? $decoded : null;
    }
};
