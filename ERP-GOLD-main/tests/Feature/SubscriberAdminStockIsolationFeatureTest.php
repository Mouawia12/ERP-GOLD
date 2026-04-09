<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use App\Services\Branches\BranchContextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriberAdminStockIsolationFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->withoutMiddleware([
            LaravelLocalizationRedirectFilter::class,
            LocaleSessionRedirect::class,
        ]);
    }

    public function test_subscriber_user_only_sees_his_own_name_in_stock_user_filter(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('مخزون المشترك', [
            'employee.inventory_reports.show',
        ]);

        $secondBranch = $this->createBranch($subscriber->id, 'فرع ثان للمخزون');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberOperator('مخزون أجنبي');

        $ownUser = $this->createUser($subscriber->id, $secondBranch->id, 'مستخدم مخزون داخلي');
        $foreignUser = $this->createUser($foreignSubscriber->id, $foreignBranch->id, 'مستخدم مخزون أجنبي');

        $response = $this
            ->actingAs($user, 'admin-web')
            ->get(route('reports.sales_report.search', [], false));

        $response->assertOk();
        $response->assertSee($mainBranch->getTranslation('name', 'ar'));
        $response->assertSee($user->name);
        $response->assertSee('يمكنك عرض تقارير المستخدم الحالي فقط.');
        $response->assertSee('name="user_id"', false);
        $response->assertDontSee($foreignUser->name);
        $response->assertDontSee($ownUser->name);
    }

    public function test_subscriber_primary_account_sees_all_his_subscriber_users_in_stock_user_filter(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('مستخدمو المشترك', [
            'employee.inventory_reports.show',
        ]);
        $subscriber->update([
            'admin_user_id' => $user->id,
        ]);

        $secondBranch = $this->createBranch($subscriber->id, 'فرع مستخدمين ثان');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberOperator('مستخدمون أجانب');

        $ownUser = $this->createUser($subscriber->id, $secondBranch->id, 'مستخدم داخلي أول');
        $secondUser = $this->createUser($subscriber->id, $mainBranch->id, 'مستخدم داخلي ثان');
        $foreignUser = $this->createUser($foreignSubscriber->id, $foreignBranch->id, 'مستخدم أجنبي');

        $response = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->get(route('reports.sales_report.search', [], false));

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee($ownUser->name);
        $response->assertSee($secondUser->name);
        $response->assertDontSee($foreignUser->name);
        $response->assertDontSee('يمكنك عرض تقارير المستخدم الحالي فقط.');
    }

    public function test_subscriber_user_stock_report_forces_current_user_even_if_request_targets_another_user(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('تقييد مستخدم التقرير', [
            'employee.inventory_reports.show',
        ]);
        $otherUser = $this->createUser($subscriber->id, $mainBranch->id, 'زميل الفرع');

        $ownDimensions = $this->prepareInventoryDimensions('عميل المستخدم الحالي');
        $otherDimensions = $this->prepareInventoryDimensions('عميل الزميل');

        $ownInvoiceId = $this->insertInvoice([
            'bill_number' => 'SELF-ONLY-001',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $mainBranch->id,
            'user_id' => $user->id,
            'customer_id' => $ownDimensions['customer_id'],
            'date' => '2026-04-05',
            'time' => '09:00:00',
            'lines_total_after_discount' => 100,
            'lines_total' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ]);
        $this->insertInvoiceDetail($ownInvoiceId, $ownDimensions, [
            'date' => '2026-04-05',
            'out_quantity' => 1,
            'out_weight' => 1.000,
            'line_total' => 100,
            'line_tax' => 15,
            'net_total' => 115,
        ]);

        $otherInvoiceId = $this->insertInvoice([
            'bill_number' => 'SELF-ONLY-999',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $mainBranch->id,
            'user_id' => $otherUser->id,
            'customer_id' => $otherDimensions['customer_id'],
            'date' => '2026-04-05',
            'time' => '10:00:00',
            'lines_total_after_discount' => 300,
            'lines_total' => 300,
            'taxes_total' => 45,
            'net_total' => 345,
        ]);
        $this->insertInvoiceDetail($otherInvoiceId, $otherDimensions, [
            'date' => '2026-04-05',
            'out_quantity' => 1,
            'out_weight' => 3.000,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
        ]);

        $response = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->post(route('reports.sales_total_report.index', [], false), [
                'branch_id' => $mainBranch->id,
                'user_id' => $otherUser->id,
                'date_from' => '2026-04-05',
                'date_to' => '2026-04-05',
            ]);

        $response->assertOk();
        $response->assertSee('SELF-ONLY-001');
        $response->assertDontSee('SELF-ONLY-999');
        $response->assertSee('115');
        $response->assertDontSee('345');
    }

    public function test_subscriber_user_stock_report_includes_all_accessible_branches_and_excludes_foreign_subscribers(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('تقارير المبيعات', [
            'employee.inventory_reports.show',
        ]);

        $secondBranch = $this->createBranch($subscriber->id, 'فرع بيع ثان');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberOperator('تقارير أجنبية');

        app(BranchContextService::class)->syncUserBranches($user, [$mainBranch->id, $secondBranch->id], $mainBranch->id);

        $foreignUser = $this->createUser($foreignSubscriber->id, $foreignBranch->id, 'بائع أجنبي');

        $mainDimensions = $this->prepareInventoryDimensions('عميل البيع الأول');
        $secondDimensions = $this->prepareInventoryDimensions('عميل البيع الثاني');
        $foreignDimensions = $this->prepareInventoryDimensions('عميل أجنبي');

        $firstInvoiceId = $this->insertInvoice([
            'bill_number' => 'SUB-SALE-001',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $mainBranch->id,
            'user_id' => $user->id,
            'customer_id' => $mainDimensions['customer_id'],
            'date' => '2026-04-03',
            'time' => '09:00:00',
            'lines_total_after_discount' => 100,
            'lines_total' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ]);
        $this->insertInvoiceDetail($firstInvoiceId, $mainDimensions, [
            'date' => '2026-04-03',
            'out_quantity' => 1,
            'out_weight' => 1.111,
            'line_total' => 100,
            'line_tax' => 15,
            'net_total' => 115,
        ]);

        $secondInvoiceId = $this->insertInvoice([
            'bill_number' => 'SUB-SALE-002',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $secondBranch->id,
            'user_id' => $user->id,
            'customer_id' => $secondDimensions['customer_id'],
            'date' => '2026-04-03',
            'time' => '10:00:00',
            'lines_total_after_discount' => 200,
            'lines_total' => 200,
            'taxes_total' => 30,
            'net_total' => 230,
        ]);
        $this->insertInvoiceDetail($secondInvoiceId, $secondDimensions, [
            'date' => '2026-04-03',
            'out_quantity' => 1,
            'out_weight' => 2.222,
            'line_total' => 200,
            'line_tax' => 30,
            'net_total' => 230,
        ]);

        $foreignInvoiceId = $this->insertInvoice([
            'bill_number' => 'SUB-SALE-999',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $foreignBranch->id,
            'user_id' => $foreignUser->id,
            'customer_id' => $foreignDimensions['customer_id'],
            'date' => '2026-04-03',
            'time' => '11:00:00',
            'lines_total_after_discount' => 900,
            'lines_total' => 900,
            'taxes_total' => 135,
            'net_total' => 1035,
        ]);
        $this->insertInvoiceDetail($foreignInvoiceId, $foreignDimensions, [
            'date' => '2026-04-03',
            'out_quantity' => 1,
            'out_weight' => 9.999,
            'line_total' => 900,
            'line_tax' => 135,
            'net_total' => 1035,
        ]);

        $response = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->post(route('reports.sales_total_report.index', [], false), [
                'date_from' => '2026-04-03',
                'date_to' => '2026-04-03',
            ]);

        $response->assertOk();
        $response->assertSee('SUB-SALE-001');
        $response->assertSee('SUB-SALE-002');
        $response->assertDontSee('SUB-SALE-999');
        $response->assertSee('115');
        $response->assertSee('230');
        $response->assertDontSee('1035');
    }

    public function test_subscriber_user_gold_stock_report_is_scoped_to_his_accessible_branches_only(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('ذهب المشترك', [
            'employee.stock.show',
        ]);

        $secondBranch = $this->createBranch($subscriber->id, 'فرع ذهب ثان');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberOperator('ذهب أجنبي');

        app(BranchContextService::class)->syncUserBranches($user, [$mainBranch->id, $secondBranch->id], $mainBranch->id);

        $financialYearId = $this->createFinancialYear();
        [$craftedTypeId] = $this->createGoldCaratTypes();
        $caratId = $this->createGoldCarat('عيار 21', 'C21', 1, false);

        $firstInvoiceId = $this->insertInvoice([
            'bill_number' => 'GST-001',
            'financial_year' => $financialYearId,
            'branch_id' => $mainBranch->id,
            'type' => 'purchase',
            'date' => '2026-04-03',
        ]);
        $this->insertInvoiceDetail($firstInvoiceId, [
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $craftedTypeId,
        ], [
            'date' => '2026-04-03',
            'in_weight' => 1.111,
            'out_weight' => 0.111,
        ]);

        $secondInvoiceId = $this->insertInvoice([
            'bill_number' => 'GST-002',
            'financial_year' => $financialYearId,
            'branch_id' => $secondBranch->id,
            'type' => 'purchase',
            'date' => '2026-04-03',
        ]);
        $this->insertInvoiceDetail($secondInvoiceId, [
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $craftedTypeId,
        ], [
            'date' => '2026-04-03',
            'in_weight' => 2.222,
            'out_weight' => 0.222,
        ]);

        $foreignInvoiceId = $this->insertInvoice([
            'bill_number' => 'GST-999',
            'financial_year' => $financialYearId,
            'branch_id' => $foreignBranch->id,
            'type' => 'purchase',
            'date' => '2026-04-03',
        ]);
        $this->insertInvoiceDetail($foreignInvoiceId, [
            'gold_carat_id' => $caratId,
            'gold_carat_type_id' => $craftedTypeId,
        ], [
            'date' => '2026-04-03',
            'in_weight' => 9.999,
            'out_weight' => 0.999,
        ]);

        $reportResponse = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->post(route('reports.gold_stock.index', [], false), [
                'date_from' => '2026-04-03',
                'date_to' => '2026-04-03',
            ]);

        $reportResponse->assertOk();
        $reportResponse->assertSee('3.333');
        $reportResponse->assertSee('0.333');
        $reportResponse->assertSee('3.000');
        $reportResponse->assertDontSee('9.999');
        $reportResponse->assertDontSee('0.999');
        $reportResponse->assertDontSee('9.000');
    }

    public function test_subscriber_primary_account_sees_all_his_branches_in_stock_filters_and_can_limit_report_to_selected_branches(): void
    {
        [$subscriber, $mainBranch, $user] = $this->createSubscriberOperator('الحساب الرئيسي', [
            'employee.inventory_reports.show',
        ]);
        $subscriber->update([
            'admin_user_id' => $user->id,
        ]);

        $secondBranch = $this->createBranch($subscriber->id, 'فرع رئيسي ثان');
        $thirdBranch = $this->createBranch($subscriber->id, 'فرع رئيسي ثالث');
        [$foreignSubscriber, $foreignBranch] = $this->createSubscriberOperator('حساب أجنبي');

        $filtersResponse = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->get(route('reports.sales_report.search', [], false));

        $filtersResponse->assertOk();
        $filtersResponse->assertSee('name="branch_ids[]"', false);
        $filtersResponse->assertSee($mainBranch->getTranslation('name', 'ar'));
        $filtersResponse->assertSee($secondBranch->getTranslation('name', 'ar'));
        $filtersResponse->assertSee($thirdBranch->getTranslation('name', 'ar'));
        $filtersResponse->assertDontSee($foreignBranch->getTranslation('name', 'ar'));

        $mainUser = $this->createUser($subscriber->id, $mainBranch->id, 'مستخدم الفرع الرئيسي');
        $secondUser = $this->createUser($subscriber->id, $secondBranch->id, 'مستخدم الفرع الثاني');
        $thirdUser = $this->createUser($subscriber->id, $thirdBranch->id, 'مستخدم الفرع الثالث');
        $foreignUser = $this->createUser($foreignSubscriber->id, $foreignBranch->id, 'مستخدم أجنبي');

        $mainDimensions = $this->prepareInventoryDimensions('عميل الحساب الرئيسي');
        $secondDimensions = $this->prepareInventoryDimensions('عميل الفرع الثاني');
        $thirdDimensions = $this->prepareInventoryDimensions('عميل الفرع الثالث');
        $foreignDimensions = $this->prepareInventoryDimensions('عميل أجنبي جدا');

        $mainInvoiceId = $this->insertInvoice([
            'bill_number' => 'PRIMARY-SALE-001',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $mainBranch->id,
            'user_id' => $mainUser->id,
            'customer_id' => $mainDimensions['customer_id'],
            'date' => '2026-04-04',
            'time' => '09:00:00',
            'lines_total_after_discount' => 100,
            'lines_total' => 100,
            'taxes_total' => 15,
            'net_total' => 115,
        ]);
        $this->insertInvoiceDetail($mainInvoiceId, $mainDimensions, [
            'date' => '2026-04-04',
            'out_quantity' => 1,
            'out_weight' => 1.100,
            'line_total' => 100,
            'line_tax' => 15,
            'net_total' => 115,
        ]);

        $secondInvoiceId = $this->insertInvoice([
            'bill_number' => 'PRIMARY-SALE-002',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $secondBranch->id,
            'user_id' => $secondUser->id,
            'customer_id' => $secondDimensions['customer_id'],
            'date' => '2026-04-04',
            'time' => '10:00:00',
            'lines_total_after_discount' => 200,
            'lines_total' => 200,
            'taxes_total' => 30,
            'net_total' => 230,
        ]);
        $this->insertInvoiceDetail($secondInvoiceId, $secondDimensions, [
            'date' => '2026-04-04',
            'out_quantity' => 1,
            'out_weight' => 2.200,
            'line_total' => 200,
            'line_tax' => 30,
            'net_total' => 230,
        ]);

        $thirdInvoiceId = $this->insertInvoice([
            'bill_number' => 'PRIMARY-SALE-003',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $thirdBranch->id,
            'user_id' => $thirdUser->id,
            'customer_id' => $thirdDimensions['customer_id'],
            'date' => '2026-04-04',
            'time' => '11:00:00',
            'lines_total_after_discount' => 300,
            'lines_total' => 300,
            'taxes_total' => 45,
            'net_total' => 345,
        ]);
        $this->insertInvoiceDetail($thirdInvoiceId, $thirdDimensions, [
            'date' => '2026-04-04',
            'out_quantity' => 1,
            'out_weight' => 3.300,
            'line_total' => 300,
            'line_tax' => 45,
            'net_total' => 345,
        ]);

        $foreignInvoiceId = $this->insertInvoice([
            'bill_number' => 'PRIMARY-SALE-999',
            'type' => 'sale',
            'sale_type' => 'standard',
            'branch_id' => $foreignBranch->id,
            'user_id' => $foreignUser->id,
            'customer_id' => $foreignDimensions['customer_id'],
            'date' => '2026-04-04',
            'time' => '12:00:00',
            'lines_total_after_discount' => 900,
            'lines_total' => 900,
            'taxes_total' => 135,
            'net_total' => 1035,
        ]);
        $this->insertInvoiceDetail($foreignInvoiceId, $foreignDimensions, [
            'date' => '2026-04-04',
            'out_quantity' => 1,
            'out_weight' => 9.900,
            'line_total' => 900,
            'line_tax' => 135,
            'net_total' => 1035,
        ]);

        $allBranchesResponse = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->post(route('reports.sales_total_report.index', [], false), [
                'date_from' => '2026-04-04',
                'date_to' => '2026-04-04',
            ]);

        $allBranchesResponse->assertOk();
        $allBranchesResponse->assertSee('PRIMARY-SALE-001');
        $allBranchesResponse->assertSee('PRIMARY-SALE-002');
        $allBranchesResponse->assertSee('PRIMARY-SALE-003');
        $allBranchesResponse->assertDontSee('PRIMARY-SALE-999');

        $selectedBranchResponse = $this
            ->actingAs($user->fresh(), 'admin-web')
            ->post(route('reports.sales_total_report.index', [], false), [
                'branch_ids' => [$secondBranch->id],
                'date_from' => '2026-04-04',
                'date_to' => '2026-04-04',
            ]);

        $selectedBranchResponse->assertOk();
        $selectedBranchResponse->assertSee('PRIMARY-SALE-002');
        $selectedBranchResponse->assertSee('الفرع: '.$secondBranch->getTranslation('name', 'ar'));
        $selectedBranchResponse->assertDontSee('PRIMARY-SALE-001');
        $selectedBranchResponse->assertDontSee('PRIMARY-SALE-003');
        $selectedBranchResponse->assertDontSee('PRIMARY-SALE-999');
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0:Subscriber,1:Branch,2:User}
     */
    private function createSubscriberOperator(string $name, array $permissions = []): array
    {
        $subscriber = Subscriber::create([
            'name' => $name,
            'login_email' => uniqid('subscriber-admin-', true).'@example.com',
            'status' => true,
        ]);

        $branch = $this->createBranch($subscriber->id, 'الفرع الرئيسي '.$name);

        $role = Role::create([
            'name' => ['ar' => 'دور '.$name, 'en' => 'Role '.$name],
            'guard_name' => 'admin-web',
        ]);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate([
                'name' => $permissionName,
                'guard_name' => 'admin-web',
            ]);

            $role->givePermissionTo($permission);
        }

        $user = User::create([
            'subscriber_id' => $subscriber->id,
            'name' => 'Operator '.$name,
            'email' => uniqid('subscriber-operator-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $user->assignRole($role);

        return [$subscriber, $branch, $user->fresh()];
    }

    private function createBranch(int $subscriberId, string $name): Branch
    {
        return Branch::create([
            'subscriber_id' => $subscriberId,
            'name' => ['ar' => $name, 'en' => $name],
            'phone' => '0555555555',
            'status' => true,
        ]);
    }

    private function createUser(int $subscriberId, int $branchId, string $name): User
    {
        return User::create([
            'subscriber_id' => $subscriberId,
            'name' => $name,
            'email' => uniqid('subscriber-user-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branchId,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);
    }

    /**
     * @return array{customer_id:int,gold_carat_id:int,gold_carat_type_id:int,tax_id:int}
     */
    private function prepareInventoryDimensions(string $customerName): array
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT',
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $goldCaratTypeId = DB::table('gold_carat_types')->where('key', 'crafted')->value('id')
            ?: DB::table('gold_carat_types')->insertGetId([
                'title' => json_encode(['ar' => 'مشغول', 'en' => 'Crafted'], JSON_UNESCAPED_UNICODE),
                'key' => 'crafted',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $goldCaratId = DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => 'عيار 21', 'en' => '21K'], JSON_UNESCAPED_UNICODE),
            'label' => 'C21-'.uniqid(),
            'tax_id' => $taxId,
            'transform_factor' => '1',
            'is_pure' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $customerId = DB::table('customers')->insertGetId([
            'name' => $customerName,
            'phone' => '0500000000',
            'type' => 'customer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'customer_id' => $customerId,
            'gold_carat_id' => $goldCaratId,
            'gold_carat_type_id' => $goldCaratTypeId,
            'tax_id' => $taxId,
        ];
    }

    private function createFinancialYear(): int
    {
        return DB::table('financial_years')->insertGetId([
            'description' => 'FY 2026',
            'from' => '2026-01-01',
            'to' => '2026-12-31',
            'is_closed' => false,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{0:int,1:int,2:int}
     */
    private function createGoldCaratTypes(): array
    {
        $craftedId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'مشغول', 'en' => 'Crafted'], JSON_UNESCAPED_UNICODE),
            'key' => 'crafted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $scrapId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'كسر', 'en' => 'Scrap'], JSON_UNESCAPED_UNICODE),
            'key' => 'scrap',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $pureId = DB::table('gold_carat_types')->insertGetId([
            'title' => json_encode(['ar' => 'صافي', 'en' => 'Pure'], JSON_UNESCAPED_UNICODE),
            'key' => 'pure',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [$craftedId, $scrapId, $pureId];
    }

    private function createGoldCarat(string $titleAr, string $label, float $transformFactor, bool $isPure): int
    {
        $taxId = DB::table('taxes')->insertGetId([
            'title' => 'VAT '.uniqid(),
            'rate' => 15,
            'zatca_code' => 'S',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('gold_carats')->insertGetId([
            'title' => json_encode(['ar' => $titleAr, 'en' => $titleAr], JSON_UNESCAPED_UNICODE),
            'label' => $label,
            'tax_id' => $taxId,
            'transform_factor' => (string) $transformFactor,
            'is_pure' => $isPure,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoice(array $attributes): int
    {
        return DB::table('invoices')->insertGetId(array_merge([
            'bill_number' => null,
            'serial' => null,
            'financial_year' => null,
            'branch_id' => null,
            'warehouse_id' => null,
            'customer_id' => null,
            'bill_client_phone' => null,
            'bill_client_name' => null,
            'bill_client_identity_number' => null,
            'parent_id' => null,
            'type' => 'purchase',
            'account_id' => null,
            'sale_type' => 'simplified',
            'purchase_type' => null,
            'purchase_carat_type_id' => null,
            'supplier_bill_number' => null,
            'notes' => null,
            'payment_type' => 'cash',
            'date' => '2026-04-03',
            'time' => '00:00:00',
            'lines_total' => 0,
            'discount_total' => 0,
            'lines_total_after_discount' => 0,
            'taxes_total' => 0,
            'net_total' => 0,
            'user_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }

    /**
     * @param  array<string, mixed>  $base
     * @param  array<string, mixed>  $attributes
     */
    private function insertInvoiceDetail(int $invoiceId, array $base, array $attributes = []): int
    {
        return DB::table('invoice_details')->insertGetId(array_merge([
            'invoice_id' => $invoiceId,
            'warehouse_id' => null,
            'parent_id' => null,
            'item_id' => null,
            'no_metal' => 0,
            'no_metal_type' => 'fixed',
            'unit_id' => null,
            'gold_carat_id' => $base['gold_carat_id'] ?? null,
            'gold_carat_type_id' => $base['gold_carat_type_id'] ?? null,
            'date' => '2026-04-03',
            'in_quantity' => 0,
            'out_quantity' => 0,
            'in_weight' => 0,
            'out_weight' => 0,
            'unit_cost' => 0,
            'labor_cost_per_gram' => 0,
            'unit_price' => 0,
            'unit_discount' => 0,
            'unit_tax' => 0,
            'unit_tax_rate' => 15,
            'unit_tax_id' => $base['tax_id'] ?? null,
            'line_total' => 0,
            'line_discount' => 0,
            'line_tax' => 0,
            'net_total' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes));
    }
}
