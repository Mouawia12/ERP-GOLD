<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountSetting;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\FinancialVoucher;
use App\Models\GoldCarat;
use App\Models\Invoice;
use App\Models\User;
use App\Services\Branches\BranchContextService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    private const ALLOWED_TYPES = ['customer', 'supplier'];
    private const REPORTABLE_INVOICE_TYPES = ['sale', 'sale_return', 'purchase', 'purchase_return', 'manufacturing_order', 'manufacturing_receipt', 'manufacturing_return', 'manufacturing_loss_settlement'];
    private const REPORTABLE_VOUCHER_TYPES = ['receipt', 'payment'];

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, $type)
    {
        return $this->renderIndex($request, $type, false);
    }

    public function cashDirectory(Request $request, $type)
    {
        return $this->renderIndex($request, $type, true);
    }

    private function renderIndex(Request $request, $type, bool $cashDirectory)
    {
        $type = $this->normalizeType($type);
        $this->authorizeTypePermission($type, 'show');
        $currentUser = $request->user('admin-web');

        $cashOnly = $cashDirectory || $request->boolean('cash_only');
        $identityNumber = $this->normalizeOptionalFilter($request->input('identity_number'));

        $customers = Customer::query()
            ->visibleToUser($currentUser)
            ->where('type', $type)
            ->when($cashOnly, function ($query) {
                return $query->where('is_cash_party', true);
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
            'identity_number' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'vat_no' => 'nullable|string|max:255',
            'region' => 'required_with:vat_no|string|max:255',
            'city' => 'required_with:vat_no|string|max:255',
            'district' => 'required_with:vat_no|string|max:255',
            'street_name' => 'required_with:vat_no|string|max:255',
            'building_number' => 'required_with:vat_no|string|max:255',
            'plot_identification' => 'required_with:vat_no|string|max:255',
            'postal_code' => 'required_with:vat_no|string|max:255',
            'type' => 'required|in:' . implode(',', self::ALLOWED_TYPES),
        ],
            [
                'name.required' => __('validations.customer_name_required', ['type' => $request->type == 'customer' ? __('main.customer') : __('main.supplier')]),
                'region.required_with' => __('validations.region_required_with', ['vat_no' => __('main.vat_no')]),
                'city.required_with' => __('validations.city_required_with', ['vat_no' => __('main.vat_no')]),
                'district.required_with' => __('validations.district_required_with', ['vat_no' => __('main.vat_no')]),
                'street_name.required_with' => __('validations.street_name_required_with', ['vat_no' => __('main.vat_no')]),
                'building_number.required_with' => __('validations.building_number_required_with', ['vat_no' => __('main.vat_no')]),
                'plot_identification.required_with' => __('validations.plot_identification_required_with', ['vat_no' => __('main.vat_no')]),
                'postal_code.required_with' => __('validations.postal_code_required_with', ['vat_no' => __('main.vat_no')]),
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
            $payload = [
                'name' => $request->name,
                'phone' => $request->phone,
                'is_cash_party' => $request->boolean('is_cash_party'),
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
        $isCashParty = $request->boolean('is_cash_party');
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

    public function report(Request $request, $id)
    {
        $currentUser = $request->user('admin-web');
        $customer = Customer::query()->visibleToUser($currentUser)->findOrFail($id);
        $this->authorizeTypePermission($customer->type, 'show');
        $accessibleBranchIds = $this->accessibleBranchIds($currentUser);

        $filters = Validator::make($request->all(), [
            'from_date' => 'nullable|date',
            'to_date' => 'nullable|date',
            'from_time' => 'nullable|string|max:8',
            'to_time' => 'nullable|string|max:8',
            'branch_id' => 'nullable|exists:branches,id',
            'user_id' => 'nullable|exists:users,id',
            'carat_id' => 'nullable|exists:gold_carats,id',
            'invoice_number' => 'nullable|string|max:191',
            'operation_type' => 'nullable|in:' . implode(',', array_merge(self::REPORTABLE_INVOICE_TYPES, self::REPORTABLE_VOUCHER_TYPES)),
        ])->validate();

        $filters['invoice_number'] = $this->normalizeOptionalFilter(
            $filters['invoice_number'] ?? $request->input('billNumber')
        );
        $filters['from_time'] = $this->normalizeTime($filters['from_time'] ?? null);
        $filters['to_time'] = $this->normalizeTime($filters['to_time'] ?? null);

        $selectedCaratId = isset($filters['carat_id']) ? (int) $filters['carat_id'] : null;

        $shouldQueryInvoices = ! ($filters['operation_type'] ?? null)
            || in_array($filters['operation_type'], self::REPORTABLE_INVOICE_TYPES, true);
        $shouldQueryVouchers = ! ($filters['operation_type'] ?? null)
            || in_array($filters['operation_type'], self::REPORTABLE_VOUCHER_TYPES, true);

        $invoiceTransactions = collect();
        if ($shouldQueryInvoices) {
            $invoiceTransactions = Invoice::query()
                ->with(['branch', 'user', 'customer', 'account', 'details.carat', 'manufacturingLossSettlementLines.carat'])
                ->where('customer_id', $customer->id)
                ->whereIn('type', self::REPORTABLE_INVOICE_TYPES)
                ->when($accessibleBranchIds !== [], function ($query) use ($accessibleBranchIds) {
                    return $query->whereIn('branch_id', $accessibleBranchIds);
                })
                ->when($filters['from_date'] ?? null, function ($query, $value) {
                    return $query->whereDate('date', '>=', $value);
                })
                ->when($filters['to_date'] ?? null, function ($query, $value) {
                    return $query->whereDate('date', '<=', $value);
                })
                ->when($filters['from_time'] ?? null, function ($query, $value) {
                    return $query->where('time', '>=', $value);
                })
                ->when($filters['to_time'] ?? null, function ($query, $value) {
                    return $query->where('time', '<=', $value);
                })
                ->when($filters['branch_id'] ?? null, function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($filters['user_id'] ?? null, function ($query, $value) {
                    return $query->where('user_id', $value);
                })
                ->when($filters['invoice_number'] ?? null, function ($query, $value) {
                    return $query->where('bill_number', $value);
                })
                ->when($filters['operation_type'] ?? null, function ($query, $value) {
                    return $query->where('type', $value);
                })
                ->when($selectedCaratId, function ($query, $value) {
                    return $query->whereHas('details', function ($detailsQuery) use ($value) {
                        $detailsQuery->where('gold_carat_id', $value);
                    });
                })
                ->orderByDesc('date')
                ->orderByDesc('time')
                ->orderByDesc('id')
                ->get()
                ->map(function (Invoice $invoice) use ($selectedCaratId) {
                    return $this->mapCustomerReportTransaction($invoice, $selectedCaratId);
                })
                ->filter(function (array $transaction) {
                    return $transaction['details_count'] > 0;
                })
                ->values();
        }

        $voucherTransactions = collect();
        if ($shouldQueryVouchers && $customer->account_id && ! $selectedCaratId) {
            $voucherTransactions = FinancialVoucher::query()
                ->with(['branch', 'shift.user', 'fromAccount', 'toAccount', 'bankAccount'])
                ->where(function ($query) use ($customer) {
                    $query->where('from_account_id', $customer->account_id)
                        ->orWhere('to_account_id', $customer->account_id);
                })
                ->when($accessibleBranchIds !== [], function ($query) use ($accessibleBranchIds) {
                    return $query->whereIn('branch_id', $accessibleBranchIds);
                })
                ->when($filters['from_date'] ?? null, function ($query, $value) {
                    return $query->whereDate('date', '>=', $value);
                })
                ->when($filters['to_date'] ?? null, function ($query, $value) {
                    return $query->whereDate('date', '<=', $value);
                })
                ->when($filters['from_time'] ?? null, function ($query, $value) {
                    return $query->whereTime('created_at', '>=', $value);
                })
                ->when($filters['to_time'] ?? null, function ($query, $value) {
                    return $query->whereTime('created_at', '<=', $value);
                })
                ->when($filters['branch_id'] ?? null, function ($query, $value) {
                    return $query->where('branch_id', $value);
                })
                ->when($filters['user_id'] ?? null, function ($query, $value) {
                    return $query->whereHas('shift', function ($shiftQuery) use ($value) {
                        $shiftQuery->where('user_id', $value);
                    });
                })
                ->when($filters['invoice_number'] ?? null, function ($query, $value) {
                    return $query->where('bill_number', $value);
                })
                ->when($filters['operation_type'] ?? null, function ($query, $value) {
                    return $query->where('type', $value);
                })
                ->orderByDesc('date')
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->get()
                ->map(function (FinancialVoucher $voucher) {
                    return $this->mapCustomerReportVoucher($voucher);
                })
                ->values();
        }

        $transactions = $invoiceTransactions
            ->concat($voucherTransactions)
            ->sortByDesc(function (array $transaction) {
                return sprintf(
                    '%s %s %010d',
                    $transaction['date'],
                    $transaction['time_sort'] ?? $transaction['time'],
                    $transaction['sort_id'] ?? $transaction['id'],
                );
            })
            ->values();

        $operationSummary = $this->buildOperationSummary($transactions);
        $caratSummary = $this->buildCaratSummary($transactions);

        return view('admin.customers.report', [
            'customer' => $customer,
            'transactions' => $transactions,
            'operationSummary' => $operationSummary,
            'caratSummary' => $caratSummary,
            'branches' => $this->branchesQuery($currentUser)->orderBy('name')->get(),
            'users' => $this->usersQuery($currentUser)->orderBy('name')->get(),
            'carats' => GoldCarat::query()->orderBy('id')->get(),
            'filters' => $filters,
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

    private function operationLabel(string $type): string
    {
        return match ($type) {
            'sale' => 'بيع',
            'sale_return' => 'مرتجع بيع',
            'purchase' => 'شراء',
            'purchase_return' => 'مرتجع شراء',
            'manufacturing_order' => 'إرسال للتصنيع',
            'manufacturing_receipt' => 'استلام من التصنيع',
            'manufacturing_return' => 'إرجاع تصنيع',
            'manufacturing_loss_settlement' => 'تسوية فاقد تصنيع',
            'receipt' => 'سند قبض',
            'payment' => 'سند صرف',
            default => $type,
        };
    }

    private function paymentTypeLabel(?string $paymentType): string
    {
        return match ($paymentType) {
            'cash' => 'نقدي',
            'credit_card' => 'شبكة / بطاقة',
            'bank_transfer' => 'تحويل بنكي',
            default => $paymentType ?? '-',
        };
    }

    private function normalizeExistingCustomerId(mixed $value): ?int
    {
        if (blank($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomerReportTransaction(Invoice $invoice, ?int $selectedCaratId): array
    {
        $details = $invoice->details
            ->when($selectedCaratId, function ($collection) use ($selectedCaratId) {
                return $collection->where('gold_carat_id', $selectedCaratId);
            })
            ->values();

        $settlementLines = $invoice->manufacturingLossSettlementLines
            ->when($selectedCaratId, function ($collection) use ($selectedCaratId) {
                return $collection->where('gold_carat_id', $selectedCaratId);
            })
            ->values();

        if ($invoice->type === 'manufacturing_loss_settlement') {
            $caratSummary = $settlementLines
                ->groupBy(function ($line) {
                    return $line->gold_carat_id ?: 'none';
                })
                ->map(function ($group) {
                    $firstLine = $group->first();

                    return [
                        'carat_id' => $firstLine?->gold_carat_id,
                        'carat_title' => $firstLine?->carat?->title ?? 'بدون عيار',
                        'line_count' => $group->count(),
                        'in_weight' => 0.0,
                        'out_weight' => round((float) $group->sum('settled_weight'), 3),
                        'line_total' => round((float) $group->sum('line_total'), 2),
                        'tax_total' => 0.0,
                        'net_total' => round((float) $group->sum('line_total'), 2),
                    ];
                })
                ->values();

            return [
                'id' => $invoice->id,
                'sort_id' => $invoice->id,
                'bill_number' => $invoice->bill_number,
                'type' => $invoice->type,
                'operation_label' => $this->operationLabel($invoice->type),
                'date' => $invoice->date,
                'time' => $invoice->time,
                'time_sort' => $invoice->time,
                'party_name' => $invoice->customer_name ?? '-',
                'party_phone' => $invoice->customer_phone ?? '-',
                'branch_name' => $invoice->branch?->name ?? '-',
                'user_name' => $invoice->user?->name ?? '-',
                'payment_type' => null,
                'payment_type_label' => $invoice->account?->name ?? '-',
                'line_total' => round((float) $settlementLines->sum('line_total'), 2),
                'tax_total' => 0.0,
                'net_total' => round((float) $settlementLines->sum('line_total'), 2),
                'in_weight' => 0.0,
                'out_weight' => round((float) $settlementLines->sum('settled_weight'), 3),
                'details_count' => $settlementLines->count(),
                'carat_summary' => $caratSummary,
            ];
        }

        $caratSummary = $details
            ->groupBy(function ($detail) {
                return $detail->gold_carat_id ?: 'none';
            })
            ->map(function ($group) {
                $firstDetail = $group->first();

                return [
                    'carat_id' => $firstDetail?->gold_carat_id,
                    'carat_title' => $firstDetail?->carat?->title ?? 'بدون عيار',
                    'line_count' => $group->count(),
                    'in_weight' => round((float) $group->sum('in_weight'), 3),
                    'out_weight' => round((float) $group->sum('out_weight'), 3),
                    'line_total' => round((float) $group->sum('line_total'), 2),
                    'tax_total' => round((float) $group->sum('line_tax'), 2),
                    'net_total' => round((float) $group->sum('net_total'), 2),
                ];
            })
            ->values();

        if ($invoice->type === 'manufacturing_return') {
            $isFromManufacturer = $invoice->manufacturing_return_direction === 'from_manufacturer';

            return [
                'id' => $invoice->id,
                'sort_id' => $invoice->id,
                'bill_number' => $invoice->bill_number,
                'type' => $invoice->type,
                'operation_label' => $invoice->manufacturing_return_direction_label,
                'date' => $invoice->date,
                'time' => $invoice->time,
                'time_sort' => $invoice->time,
                'party_name' => $invoice->customer_name ?? '-',
                'party_phone' => $invoice->customer_phone ?? '-',
                'branch_name' => $invoice->branch?->name ?? '-',
                'user_name' => $invoice->user?->name ?? '-',
                'payment_type' => null,
                'payment_type_label' => $invoice->manufacturing_return_direction_label,
                'line_total' => round((float) $details->sum('line_total'), 2),
                'tax_total' => round((float) $details->sum('line_tax'), 2),
                'net_total' => round((float) $details->sum('net_total'), 2),
                'in_weight' => round((float) ($isFromManufacturer ? $details->sum('in_weight') : 0), 3),
                'out_weight' => round((float) ($isFromManufacturer ? 0 : $details->sum('out_weight')), 3),
                'details_count' => $details->count(),
                'carat_summary' => $caratSummary,
            ];
        }

        return [
            'id' => $invoice->id,
            'sort_id' => $invoice->id,
            'bill_number' => $invoice->bill_number,
            'type' => $invoice->type,
            'operation_label' => $this->operationLabel($invoice->type),
            'date' => $invoice->date,
            'time' => $invoice->time,
            'time_sort' => $invoice->time,
            'party_name' => $invoice->customer_name ?? '-',
            'party_phone' => $invoice->customer_phone ?? '-',
            'branch_name' => $invoice->branch?->name ?? '-',
            'user_name' => $invoice->user?->name ?? '-',
            'payment_type' => $invoice->payment_type,
            'payment_type_label' => $invoice->payment_type_label,
            'line_total' => round((float) $details->sum('line_total'), 2),
            'tax_total' => round((float) $details->sum('line_tax'), 2),
            'net_total' => round((float) $details->sum('net_total'), 2),
            'in_weight' => round((float) $details->sum('in_weight'), 3),
            'out_weight' => round((float) $details->sum('out_weight'), 3),
            'details_count' => $details->count(),
            'carat_summary' => $caratSummary,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapCustomerReportVoucher(FinancialVoucher $voucher): array
    {
        $time = optional($voucher->created_at)->format('H:i:s') ?? '00:00:00';

        return [
            'id' => $voucher->id,
            'sort_id' => $voucher->id,
            'bill_number' => $voucher->bill_number,
            'type' => $voucher->type,
            'operation_label' => $this->operationLabel($voucher->type),
            'date' => $voucher->date,
            'time' => $time,
            'time_sort' => $time,
            'party_name' => '-',
            'party_phone' => '-',
            'branch_name' => $voucher->branch?->name ?? '-',
            'user_name' => $voucher->shift?->user?->name ?? '-',
            'payment_type' => null,
            'payment_type_label' => sprintf(
                'من %s إلى %s',
                $voucher->fromAccount?->name ?? '-',
                $voucher->toAccount?->name ?? '-',
            ) . (($voucher->payment_method ?? 'cash') !== 'cash' ? ' | ' . $voucher->payment_channel_label : ''),
            'line_total' => round((float) $voucher->total_amount, 2),
            'tax_total' => 0.0,
            'net_total' => round((float) $voucher->total_amount, 2),
            'in_weight' => 0.0,
            'out_weight' => 0.0,
            'details_count' => 1,
            'carat_summary' => [],
        ];
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $transactions
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildOperationSummary($transactions)
    {
        return collect(array_merge(self::REPORTABLE_INVOICE_TYPES, self::REPORTABLE_VOUCHER_TYPES))
            ->map(function ($type) use ($transactions) {
                $group = $transactions->where('type', $type)->values();

                if ($group->isEmpty()) {
                    return null;
                }

                return [
                    'type' => $type,
                    'label' => $this->operationLabel($type),
                    'count' => $group->count(),
                    'line_total' => round((float) $group->sum('line_total'), 2),
                    'tax_total' => round((float) $group->sum('tax_total'), 2),
                    'net_total' => round((float) $group->sum('net_total'), 2),
                    'in_weight' => round((float) $group->sum('in_weight'), 3),
                    'out_weight' => round((float) $group->sum('out_weight'), 3),
                ];
            })
            ->filter()
            ->values();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, array<string, mixed>>  $transactions
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    private function buildCaratSummary($transactions)
    {
        return $transactions
            ->flatMap(function (array $transaction) {
                return collect($transaction['carat_summary'])->map(function (array $summary) use ($transaction) {
                    return [
                        'type' => $transaction['type'],
                        'operation_label' => $transaction['operation_label'],
                        'carat_id' => $summary['carat_id'],
                        'carat_title' => $summary['carat_title'],
                        'invoice_count' => 1,
                        'line_count' => $summary['line_count'],
                        'in_weight' => $summary['in_weight'],
                        'out_weight' => $summary['out_weight'],
                        'line_total' => $summary['line_total'],
                        'tax_total' => $summary['tax_total'],
                        'net_total' => $summary['net_total'],
                    ];
                });
            })
            ->groupBy(function (array $row) {
                return $row['type'] . '|' . ($row['carat_id'] ?? 'none');
            })
            ->map(function ($group) {
                $firstRow = $group->first();

                return [
                    'type' => $firstRow['type'],
                    'operation_label' => $firstRow['operation_label'],
                    'carat_id' => $firstRow['carat_id'],
                    'carat_title' => $firstRow['carat_title'],
                    'invoice_count' => $group->count(),
                    'line_count' => $group->sum('line_count'),
                    'in_weight' => round((float) $group->sum('in_weight'), 3),
                    'out_weight' => round((float) $group->sum('out_weight'), 3),
                    'line_total' => round((float) $group->sum('line_total'), 2),
                    'tax_total' => round((float) $group->sum('tax_total'), 2),
                    'net_total' => round((float) $group->sum('net_total'), 2),
                ];
            })
            ->sortBy(function (array $row) {
                return $row['operation_label'] . '|' . $row['carat_title'];
            })
            ->values();
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

    private function normalizeTime(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return strlen($value) === 5 ? $value . ':00' : $value;
    }

    /**
     * @return array<int>
     */
    private function accessibleBranchIds(?User $user): array
    {
        if (! $user) {
            return [];
        }

        return app(BranchContextService::class)->accessibleBranchIds($user);
    }

    private function branchesQuery(?User $user)
    {
        $accessibleBranchIds = $this->accessibleBranchIds($user);

        return Branch::query()
            ->when(
                filled($user?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $user->subscriber_id)
            )
            ->when($accessibleBranchIds !== [], fn ($query) => $query->whereIn('id', $accessibleBranchIds));
    }

    private function usersQuery(?User $user)
    {
        return User::query()
            ->when(
                filled($user?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $user->subscriber_id)
            );
    }
}
