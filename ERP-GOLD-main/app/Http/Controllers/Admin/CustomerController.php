<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    private const ALLOWED_TYPES = ['customer', 'supplier'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type)
    {
        return $this->renderIndex($request, $type, false, false);
    }

    public function cashDirectory(Request $request, $type)
    {
        return $this->renderIndex($request, $type, true, false);
    }

    public function reportDirectory(Request $request, $type)
    {
        return $this->renderIndex($request, $type, false, true);
    }

    public function cashReportDirectory(Request $request, $type)
    {
        return $this->renderIndex($request, $type, true, true);
    }

    private function renderIndex(Request $request, $type, bool $cashDirectory, bool $reportDirectory)
    {
        $type = $this->normalizeType($type);
        $this->authorizeTypePermission($type, 'show');
        $currentUser = $request->user('admin-web');

        $cashOnly = $cashDirectory || $request->boolean('cash_only');
        $regularDirectoryOnly = ! $reportDirectory && ! $cashOnly;
        $identityNumber = $this->normalizeOptionalFilter($request->input('identity_number'));

        $customers = Customer::query()
            ->visibleToUser($currentUser)
            ->where('type', $type)
            ->when($cashOnly, function ($query) {
                return $query->where('is_cash_party', true);
            })
            ->when($regularDirectoryOnly, function ($query) {
                return $query->where(function ($query) {
                    $query->where('is_cash_party', false)
                        ->orWhereNull('is_cash_party');
                });
            })
            ->when($identityNumber, function ($query, $value) {
                return $query->where('identity_number', 'like', '%' . $value . '%');
            })
            ->orderByDesc('is_cash_party')
            ->orderBy('name')
            ->get();

        $accounts = Account::all();

        return view('admin.customers.index', ['type' => $type, 'customers' =>
            $customers,
            'accounts' => $accounts,
            'cashOnly' => $cashOnly,
            'cashDirectory' => $cashDirectory,
            'reportDirectory' => $reportDirectory,
            'identityNumber' => $identityNumber,
        ]);
    }

    public function clientAccount($id)
    {
        $client = Company::find($id);
        $company = CompanyInfo::all()->first();
        $type = $client->group_id;
        $movements = CompanyMovement::where('company_id', '=', $id)->get();
        $slag = $type == 3 ? 5 : 4;
        $subSlag = 4;
        $period = ' ';
        $period_ar = '';

        return view('admin.Company.accountMovement', compact('type', 'movements', 'slag', 'subSlag', 'client', 'company', 'period', 'period_ar'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreCompanyRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, $type)
    {
        $type = $this->normalizeType($type);
        $existingCustomerId = $this->normalizeExistingCustomerId($request->input('id'));
        $this->authorizeTypePermission($type, $existingCustomerId ? 'edit' : 'add');
        $request->merge(['type' => $type]);
        $currentUser = $request->user('admin-web');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_cash_party' => 'nullable|boolean',
            'force_cash_party' => 'nullable|boolean',
            'identity_number' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'vat_no' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'street_name' => 'nullable|string|max:255',
            'building_number' => 'nullable|string|max:255',
            'plot_identification' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:255',
            'type' => 'required|in:' . implode(',', self::ALLOWED_TYPES),
        ],
            [
                'name.required' => __('validations.customer_name_required', ['type' => $request->type == 'customer' ? __('main.customer') : __('main.supplier')]),
            ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'تعذر حفظ البيانات. يرجى مراجعة الحقول المطلوبة.',
                'errors' => $validator->errors()->all(),
                'field_errors' => $validator->errors()->toArray(),
            ], 422);
        }

        if (! $existingCustomerId) {
            $accountingError = $this->validateAccountingSetupForParty($type, $currentUser);

            if ($accountingError !== null) {
                return response()->json([
                    'status' => false,
                    'message' => $accountingError,
                    'errors' => [$accountingError],
                    'field_errors' => [],
                ], 422);
            }
        }

        try {
            $isCashParty = $request->boolean('is_cash_party') || $request->boolean('force_cash_party');
            $payload = [
                'name' => $request->name,
                'phone' => $request->phone,
                'is_cash_party' => $isCashParty,
                'identity_number' => $request->identity_number,
                'email' => $request->email,
                'tax_number' => $request->vat_no,
                'region' => $request->region,
                'city' => $request->city,
                'district' => $request->district,
                'street_name' => $request->street_name,
                'building_number' => $request->building_number,
                'plot_identification' => $request->plot_identification,
                'postal_code' => $request->postal_code,
                'type' => $request->type,
            ];

            if ($existingCustomerId) {
                $company = Customer::query()
                    ->visibleToUser($currentUser)
                    ->where('type', $type)
                    ->findOrFail($existingCustomerId);
                $company->update($payload);
            } else {
                $company = Customer::create($payload);
            }

            return response()->json([
                'status' => true,
                'message' => __('main.saved')
            ]);
        } catch (QueryException $ex) {
            return response()->json([
                'status' => false,
                'message' => sprintf('تعذر حفظ %s بسبب خطأ في البيانات أو الربط المحاسبي.', $this->partyLabel($type)),
                'errors' => [sprintf('تعذر حفظ %s بسبب خطأ في البيانات أو الربط المحاسبي.', $this->partyLabel($type))],
                'field_errors' => [],
            ], 422);
        }
    }

    public function quickStore(Request $request, $type)
    {
        $type = $this->normalizeType($type);
        $this->authorizeTypePermission($type, 'add');
        $currentUser = $request->user('admin-web');

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:50',
            'is_cash_party' => 'nullable|boolean',
            'force_cash_party' => 'nullable|boolean',
            'identity_number' => 'nullable|string|max:100',
        ], [
            'name.required' => __('validations.customer_name_required', [
                'type' => $type === 'customer' ? __('main.customer') : __('main.supplier'),
            ]),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->all(),
                'field_errors' => $validator->errors()->toArray(),
            ], 422);
        }

        $name = trim((string) $request->input('name'));
        $phone = trim((string) $request->input('phone'));
        $isCashParty = $request->boolean('is_cash_party') || $request->boolean('force_cash_party');
        $identityNumber = trim((string) $request->input('identity_number'));

        $customer = Customer::query()
            ->visibleToUser($currentUser)
            ->where('type', $type)
            ->when($phone !== '', function ($query) use ($phone) {
                return $query->where('phone', $phone);
            })
            ->first();

        if (! $customer && $identityNumber !== '') {
            $customer = Customer::query()
                ->visibleToUser($currentUser)
                ->where('type', $type)
                ->where('identity_number', $identityNumber)
                ->first();
        }

        if (! $customer) {
            $customer = Customer::query()
                ->visibleToUser($currentUser)
                ->where('type', $type)
                ->where('name', $name)
                ->first();
        }

        $created = false;

        if (! $customer) {
            $accountingError = $this->validateAccountingSetupForParty($type, $currentUser);

            if ($accountingError !== null) {
                return response()->json([
                    'status' => false,
                    'message' => $accountingError,
                    'errors' => [$accountingError],
                    'field_errors' => [],
                ], 422);
            }

            $customer = Customer::create([
                'name' => $name,
                'phone' => $phone !== '' ? $phone : null,
                'type' => $type,
                'is_cash_party' => $isCashParty,
                'identity_number' => $identityNumber !== '' ? $identityNumber : null,
            ]);
            $created = true;
        } else {
            $updates = [];

            if (empty($customer->phone) && $phone !== '') {
                $updates['phone'] = $phone;
            }

            if ($isCashParty && ! $customer->is_cash_party) {
                $updates['is_cash_party'] = true;
            }

            if (empty($customer->identity_number) && $identityNumber !== '') {
                $updates['identity_number'] = $identityNumber;
            }

            if ($updates !== []) {
                $customer->update($updates);
            }
        }

        return response()->json([
            'status' => true,
            'created' => $created,
            'message' => $created
                ? sprintf('تم حفظ %s بنجاح وإتاحته للاستخدام.', $this->partyLabel($type))
                : sprintf('تم استخدام %s المحفوظ مسبقًا.', $this->partyLabel($type)),
            'customer_id' => $customer->id,
            'customer_name' => $customer->name,
            'phone' => $customer->phone,
            'identity_number' => $customer->identity_number,
            'type' => $customer->type,
            'is_cash_party' => (bool) $customer->is_cash_party,
        ]);
    }


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function show(Company $company)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $customer = Customer::query()->visibleToUser(auth('admin-web')->user())->find($id);
        abort_if(! $customer, 404);
        $this->authorizeTypePermission($customer->type, 'edit');

        return response()->json($customer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Company  $company
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $customer = Customer::query()->visibleToUser(auth('admin-web')->user())->find($id);
        if ($customer) {
            $this->authorizeTypePermission($customer->type, 'delete');
            $customer->delete();
            return response()->json([
                'status' => true,
                'message' => __('main.deleted')
            ]);
        }
    }

    private function normalizeType(?string $type): string
    {
        abort_unless(in_array($type, self::ALLOWED_TYPES, true), 404);

        return $type;
    }

    private function authorizeTypePermission(string $type, string $ability): void
    {
        abort_unless(auth()->user()?->can($this->permissionName($type, $ability)), 403);
    }

    private function permissionName(string $type, string $ability): string
    {
        $resource = $type === 'customer' ? 'customers' : 'suppliers';

        return sprintf('employee.%s.%s', $resource, $ability);
    }

    private function partyLabel(string $type): string
    {
        return $type === 'customer' ? 'العميل' : 'المورد';
    }

    private function validateAccountingSetupForParty(string $type, ?User $user): ?string
    {
        $branchSetting = AccountSetting::query()
            ->when(
                filled($user?->branch_id),
                fn ($query) => $query->where('branch_id', $user->branch_id)
            )
            ->orderBy('branch_id')
            ->first();

        if (! $branchSetting) {
            return sprintf('لا يمكن إضافة %s قبل ضبط الروابط المحاسبية للفرع الحالي.', $this->partyLabel($type));
        }

        $parentAccountId = $type === 'customer'
            ? $branchSetting->clients_account
            : $branchSetting->suppliers_account;

        if (! $parentAccountId || ! Account::query()->find($parentAccountId)) {
            return sprintf('لا يمكن إضافة %s لأن حساب الربط المحاسبي للفرع الحالي غير محدد أو غير صالح.', $this->partyLabel($type));
        }

        return null;
    }


    private function normalizeExistingCustomerId(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function normalizeOptionalFilter($value): mixed
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        return $value === '' ? null : $value;
    }
}
