<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SubscriberUiIsolationFeatureTest extends TestCase
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

    public function test_subscriber_user_only_sees_his_own_customers_and_suppliers_in_forms(): void
    {
        [$subscriber, $branch, $admin] = $this->createSubscriberAdmin('مشترك الواجهات', [
            'employee.simplified_tax_invoices.add',
            'employee.purchase_invoices.add',
            'employee.manufacturing_orders.add',
            'employee.suppliers.show',
        ]);
        [$otherSubscriber] = $this->createSubscriberAdmin('مشترك آخر');

        $ownCustomer = $this->insertParty($subscriber->id, 'customer', 'عميل المشترك');
        $foreignCustomer = $this->insertParty($otherSubscriber->id, 'customer', 'عميل مشترك آخر');
        $ownSupplier = $this->insertParty($subscriber->id, 'supplier', 'مورد المشترك');
        $foreignSupplier = $this->insertParty($otherSubscriber->id, 'supplier', 'مورد مشترك آخر');

        $salesResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('sales.create', ['type' => 'simplified'], false));

        $salesResponse->assertOk();
        $salesResponse->assertSee($ownCustomer['name']);
        $salesResponse->assertDontSee($foreignCustomer['name']);

        $purchasesResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('purchases.create', [], false));

        $purchasesResponse->assertOk();
        $purchasesResponse->assertSee($ownSupplier['name']);
        $purchasesResponse->assertDontSee($foreignSupplier['name']);

        $manufacturingResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('manufacturing_orders.create', [], false));

        $manufacturingResponse->assertOk();
        $manufacturingResponse->assertSee($ownSupplier['name']);
        $manufacturingResponse->assertDontSee($foreignSupplier['name']);
    }

    public function test_subscriber_user_cannot_submit_foreign_customer_or_supplier_ids(): void
    {
        [$subscriber, $branch, $admin] = $this->createSubscriberAdmin('مشترك الحفظ', [
            'employee.simplified_tax_invoices.add',
            'employee.purchase_invoices.add',
        ]);
        [$otherSubscriber] = $this->createSubscriberAdmin('مشترك حفظ آخر');

        $foreignCustomer = $this->insertParty($otherSubscriber->id, 'customer', 'عميل أجنبي');
        $foreignSupplier = $this->insertParty($otherSubscriber->id, 'supplier', 'مورد أجنبي');

        $salesResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('sales.store', ['type' => 'simplified'], false), [
                'bill_date' => '2026-04-03',
                'branch_id' => $branch->id,
                'customer_id' => $foreignCustomer['id'],
            ], [
                'Accept' => 'application/json',
            ]);

        $salesResponse->assertStatus(422);
        $salesResponse->assertJsonFragment([
            __('validations.customer_id_exists'),
        ]);

        $purchasesResponse = $this
            ->actingAs($admin, 'admin-web')
            ->post(route('purchases.store', [], false), [
                'bill_date' => '2026-04-03',
                'branch_id' => $branch->id,
                'carat_type' => 'non_gold',
                'purchase_type' => 'normal',
                'supplier_id' => $foreignSupplier['id'],
                'weight' => [],
            ], [
                'Accept' => 'application/json',
            ]);

        $purchasesResponse->assertStatus(422);
        $purchasesResponse->assertJsonFragment([
            __('validations.supplier_id_exists'),
        ]);
    }

    public function test_subscriber_user_only_sees_his_own_branches_and_users_in_reports(): void
    {
        [$subscriber, $branch, $admin] = $this->createSubscriberAdmin('مشترك التقارير', [
            'employee.customers.show',
            'employee.accounts.show',
        ]);
        [$otherSubscriber, $otherBranch] = $this->createSubscriberAdmin('مشترك تقارير آخر');

        $ownOperator = User::create([
            'subscriber_id' => $subscriber->id,
            'name' => 'مستخدم المشترك',
            'email' => uniqid('subscriber-user-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $foreignOperator = User::create([
            'subscriber_id' => $otherSubscriber->id,
            'name' => 'مستخدم مشترك آخر',
            'email' => uniqid('foreign-user-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $otherBranch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $customer = $this->insertParty($subscriber->id, 'customer', 'عميل التقرير');

        $customerReportResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('customers.report', ['id' => $customer['id']], false));

        $customerReportResponse->assertOk();
        $customerReportResponse->assertSee($branch->getTranslation('name', 'ar'));
        $customerReportResponse->assertDontSee($otherBranch->getTranslation('name', 'ar'));
        $customerReportResponse->assertSee($ownOperator->name);
        $customerReportResponse->assertDontSee($foreignOperator->name);

        $taxDeclarationResponse = $this
            ->actingAs($admin, 'admin-web')
            ->get(route('tax.declaration.index', [], false));

        $taxDeclarationResponse->assertOk();
        $taxDeclarationResponse->assertSee($branch->getTranslation('name', 'ar'));
        $taxDeclarationResponse->assertDontSee($otherBranch->getTranslation('name', 'ar'));
        $taxDeclarationResponse->assertSee($ownOperator->name);
        $taxDeclarationResponse->assertDontSee($foreignOperator->name);
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array{0:Subscriber,1:Branch,2:User}
     */
    private function createSubscriberAdmin(string $name, array $permissions = []): array
    {
        $subscriber = Subscriber::create([
            'name' => $name,
            'login_email' => uniqid('subscriber-', true).'@example.com',
            'status' => true,
        ]);

        $branch = Branch::create([
            'subscriber_id' => $subscriber->id,
            'name' => ['ar' => 'فرع '.$name, 'en' => 'Branch '.$name],
            'phone' => '777888999',
            'status' => true,
        ]);

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

        $admin = User::create([
            'subscriber_id' => $subscriber->id,
            'name' => 'Admin '.$name,
            'email' => uniqid('subscriber-admin-', true).'@example.com',
            'password' => Hash::make('secret123'),
            'branch_id' => $branch->id,
            'status' => true,
            'is_admin' => false,
            'profile_pic' => 'default.png',
        ]);

        $admin->assignRole($role);

        return [$subscriber, $branch, $admin->fresh()];
    }

    /**
     * @return array{id:int,name:string}
     */
    private function insertParty(int $subscriberId, string $type, string $name): array
    {
        $accountId = DB::table('accounts')->insertGetId([
            'subscriber_id' => $subscriberId,
            'name' => json_encode(['ar' => $name.' حساب', 'en' => $name.' Account'], JSON_UNESCAPED_UNICODE),
            'code' => 'T-'.uniqid(),
            'old_id' => null,
            'level' => '2',
            'parent_account_id' => null,
            'account_type' => 'assets',
            'transfer_side' => 'budget',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $partyId = DB::table('customers')->insertGetId([
            'name' => $name,
            'phone' => '0500000000',
            'email' => null,
            'region' => null,
            'city' => null,
            'district' => null,
            'street_name' => null,
            'building_number' => null,
            'plot_identification' => null,
            'postal_code' => null,
            'tax_number' => null,
            'crn_number' => null,
            'identity_number' => null,
            'account_id' => $accountId,
            'is_cash_party' => false,
            'type' => $type,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return ['id' => $partyId, 'name' => $name];
    }
}
