<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSetting;
use App\Models\AccountsTree;
use App\Models\Branch;
use App\Models\Subscriber;
use App\Services\Accounts\SubscriberChartProvisioner;
use App\Services\Zatca\OnBoarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BranchController extends Controller
{
    public function __construct(
        private readonly SubscriberChartProvisioner $subscriberChartProvisioner,
    )
    {
        $this->middleware('permission:employee.branches.show', ['only' => ['index', 'show']]);
        $this->middleware('permission:employee.branches.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.branches.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.branches.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $currentUser = $request->user('admin-web');

        $data = Branch::query()
            ->when(
                filled($currentUser?->subscriber_id),
                fn ($query) => $query->where('subscriber_id', $currentUser->subscriber_id)
            )
            ->withCount([
                'activeAssignedUsers as users_count',
            ])
            ->latest()
            ->get();

        return view('admin.branches.index', compact('data'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $subscriber = $this->currentSubscriber($request);
        $this->ensureSubscriberCanAddBranch($subscriber);

        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($subscriber) {
                    $duplicateExists = Branch::query()
                        ->when(
                            filled($subscriber?->id),
                            fn ($query) => $query->where('subscriber_id', $subscriber->id)
                        )
                        ->get()
                        ->contains(fn (Branch $branch) => trim((string) $branch->name) === trim((string) $value));

                    if ($duplicateExists) {
                        $fail('اسم الفرع مستخدم مسبقًا لهذا المشترك.');
                    }
                },
            ],
            'email' => 'required|email',
            'phone' => 'required|string',
            'commercial_register' => 'required|digits:10',
            'tax_number' => 'required|digits:15',
            'street_name' => 'required|string',
            'building_number' => 'required|digits:4',
            'plot_identification' => 'required|digits:4',
            'country' => 'required|string',
            'region' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'postal_code' => 'required|digits:5',
            'short_address' => 'required|string',
        ], [
            'name.required' => __('dashboard.tax_settings.validations.name_required'),
            'name.unique' => 'اسم الفرع مستخدم مسبقًا لهذا المشترك.',
            'email' => [
                'required' => __('dashboard.tax_settings.validations.email_required'),
                'email' => __('dashboard.tax_settings.validations.email_email'),
            ],
            'commercial_register' => [
                'required' => __('dashboard.tax_settings.validations.commercial_register_required'),
                'digits' => __('dashboard.tax_settings.validations.commercial_register_digits', ['digits' => 10]),
            ],
            'tax_number' => [
                'required' => __('dashboard.tax_settings.validations.tax_number_required'),
                'digits' => __('dashboard.tax_settings.validations.tax_number_digits', ['digits' => 15]),
            ],
            'street_name.required' => __('dashboard.tax_settings.validations.street_name_required'),
            'building_number' => [
                'required' => __('dashboard.tax_settings.validations.building_number_required'),
                'digits' => __('dashboard.tax_settings.validations.building_number_digits', ['digits' => 4]),
            ],
            'plot_identification' => [
                'required' => __('dashboard.tax_settings.validations.plot_identification_required'),
                'digits' => __('dashboard.tax_settings.validations.plot_identification_digits', ['digits' => 4]),
            ],
            'country.required' => __('dashboard.tax_settings.validations.country_required'),
            'region.required' => __('dashboard.tax_settings.validations.region_required'),
            'city.required' => __('dashboard.tax_settings.validations.city_required'),
            'district.required' => __('dashboard.tax_settings.validations.district_required'),
            'postal_code' => [
                'required' => __('dashboard.tax_settings.validations.postal_code_required'),
                'digits' => __('dashboard.tax_settings.validations.postal_code_digits', ['digits' => 5]),
            ],
            'short_address.required' => __('dashboard.tax_settings.validations.short_address_required'),
        ]);
        try {
            DB::beginTransaction();
            $branch = Branch::create([
                ...$validated,
                'subscriber_id' => $subscriber?->id,
            ]);
            if ($branch && $subscriber) {
                $this->subscriberChartProvisioner->ensureBranchAccountSettings($subscriber, $branch);
            }
            DB::commit();
            return redirect()
                ->route('admin.branches.index')
                ->with('success', 'تم اضافة فرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ اثناء اضافة الفرع');
        }
    }

    public function show($id)
    {
        $currentUser = request()->user('admin-web');
        $branch = Branch::with([
                'activeAssignedUsers' => function ($query) {
                    $query->with('roles');
                },
            ])
            ->withCount([
                'activeAssignedUsers as users_count',
            ])
            ->findOrFail($id);
        $this->ensureBranchBelongsToSubscriber($currentUser?->subscriber_id, $branch);

        return view('admin.branches.show', compact('branch'));
    }

    public function zatca_form($id)
    {
        $branch = Branch::findorfail($id);
        $this->ensureBranchBelongsToSubscriber(request()->user('admin-web')?->subscriber_id, $branch);
        return view('admin.branches.zatca', compact('branch'));
    }

    public function zatca(Request $request, $id)
    {
        $this->validate($request, [
            'invoice_type' => 'required|in:' . implode(',', config('settings.invoices_issuing_types')),
            'business_category' => 'required',
            'otp' => 'required|digits:6',
            'stage' => 'required|in:developer-portal,simulation,core',
        ], [
            'invoice_type' => [
                'required' => __('dashboard.tax_settings.validations.invoice_type_required'),
                'in' => __('dashboard.tax_settings.validations.invoice_type_in'),
            ],
            'business_category' => [
                'required' => __('dashboard.tax_settings.validations.business_category_required'),
            ],
            'otp' => [
                'required' => __('dashboard.tax_settings.validations.otp_required'),
                'digits' => __('dashboard.tax_settings.validations.otp_digits', ['digits' => 6]),
            ],
            'stage' => [
                'required' => __('dashboard.tax_settings.validations.stage_required'),
                'in' => __('dashboard.tax_settings.validations.stage_in'),
            ],
        ]);

        $branch = Branch::findorfail($id);
        $this->ensureBranchBelongsToSubscriber($request->user('admin-web')?->subscriber_id, $branch);
        $branch->zatca_settings()->updateOrCreate([
            'branch_id' => $id,
        ], [
            'otp' => $request->otp,
            'zatca_stage' => $request->stage,
            'invoice_type' => $request->invoice_type,
            'business_category' => $request->business_category,
        ]);
        $branch->refresh();
        $response = (new OnBoarding())
            ->setZatcaEnv($branch->zatca_settings->zatca_stage)
            ->setZatcaLang(app()->getLocale() == 'ar' ? 'ar' : 'en')
            ->setEmailAddress($branch->email)
            ->setCommonName($branch->name)
            ->setCountryCode('SA')
            ->setOrganizationUnitName($branch->name)
            ->setOrganizationName($branch->name)
            ->setEgsSerialNumber($branch->zatca_settings->egs_serial_number)
            ->setVatNumber($branch->tax_number)
            ->setInvoiceType($branch->zatca_settings->invoice_type)
            ->setRegisteredAddress($branch->short_address)
            ->setAuthOtp($branch->zatca_settings->otp)
            ->setBusinessCategory($branch->zatca_settings->business_category)
            ->getAuthorization();
        if ($response && $response['success']) {
            $data = $response['data'];
            $branch->zatca_settings()->update([
                'cnf' => $data['configData'],
                'private_key' => $data['privateKey'],
                'public_key' => $data['publicKey'],
                'csr_request' => $data['csrKey'],
                'certificate' => $data['complianceCertificate'],
                'secret' => $data['complianceSecret'],
                'csid' => $data['complianceRequestID'],
                'production_certificate' => $data['productionCertificate'],
                'production_secret' => $data['productionCertificateSecret'],
                'production_csid' => $data['productionCertificateRequestID'],
            ]);
            return redirect()->route('admin.branches.zatca', $branch->id)->with('success', $response['message']);
        } else {
            return redirect()->route('admin.branches.zatca', $branch->id)->with('errors', collect([$response['message']]));
        }
    }

    public function edit($id)
    {
        $branch = Branch::findOrFail($id);
        $this->ensureBranchBelongsToSubscriber(request()->user('admin-web')?->subscriber_id, $branch);
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $subscriber = $this->currentSubscriber($request);
        $this->ensureBranchBelongsToSubscriber($subscriber?->id, $branch);

        $validated = $this->validate($request, [
            'name' => [
                'required',
                'string',
                function ($attribute, $value, $fail) use ($subscriber, $branch) {
                    $duplicateExists = Branch::query()
                        ->whereKeyNot($branch->id)
                        ->when(
                            filled($subscriber?->id),
                            fn ($query) => $query->where('subscriber_id', $subscriber->id)
                        )
                        ->get()
                        ->contains(fn (Branch $candidate) => trim((string) $candidate->name) === trim((string) $value));

                    if ($duplicateExists) {
                        $fail('اسم الفرع مستخدم مسبقًا لهذا المشترك.');
                    }
                },
            ],
            'email' => 'required|email',
            'phone' => 'required|string',
            'commercial_register' => 'required|digits:10',
            'tax_number' => 'required|digits:15',
            'street_name' => 'required|string',
            'building_number' => 'required|digits:4',
            'plot_identification' => 'required|digits:4',
            'country' => 'required|string',
            'region' => 'required|string',
            'city' => 'required|string',
            'district' => 'required|string',
            'postal_code' => 'required|digits:5',
            'short_address' => 'required|string',
        ], [
            'name.required' => __('dashboard.tax_settings.validations.name_required'),
            'name.unique' => 'اسم الفرع مستخدم مسبقًا لهذا المشترك.',
            'email' => [
                'required' => __('dashboard.tax_settings.validations.email_required'),
                'email' => __('dashboard.tax_settings.validations.email_email'),
            ],
            'commercial_register' => [
                'required' => __('dashboard.tax_settings.validations.commercial_register_required'),
                'digits' => __('dashboard.tax_settings.validations.commercial_register_digits', ['digits' => 10]),
            ],
            'tax_number' => [
                'required' => __('dashboard.tax_settings.validations.tax_number_required'),
                'digits' => __('dashboard.tax_settings.validations.tax_number_digits', ['digits' => 15]),
            ],
            'street_name.required' => __('dashboard.tax_settings.validations.street_name_required'),
            'building_number' => [
                'required' => __('dashboard.tax_settings.validations.building_number_required'),
                'digits' => __('dashboard.tax_settings.validations.building_number_digits', ['digits' => 4]),
            ],
            'plot_identification' => [
                'required' => __('dashboard.tax_settings.validations.plot_identification_required'),
                'digits' => __('dashboard.tax_settings.validations.plot_identification_digits', ['digits' => 4]),
            ],
            'country.required' => __('dashboard.tax_settings.validations.country_required'),
            'region.required' => __('dashboard.tax_settings.validations.region_required'),
            'city.required' => __('dashboard.tax_settings.validations.city_required'),
            'district.required' => __('dashboard.tax_settings.validations.district_required'),
            'postal_code' => [
                'required' => __('dashboard.tax_settings.validations.postal_code_required'),
                'digits' => __('dashboard.tax_settings.validations.postal_code_digits', ['digits' => 5]),
            ],
            'short_address.required' => __('dashboard.tax_settings.validations.short_address_required'),
        ]);
        try {
            DB::beginTransaction();
            Branch::updateOrCreate([
                'id' => $id,
            ], [
                ...$validated,
                'subscriber_id' => $subscriber?->id ?? $branch->subscriber_id,
            ]);
            DB::commit();
            return redirect()
                ->route('admin.branches.index')
                ->with('success', 'تم تعديل بيانات الفرع بنجاح');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ اثناء تعديل بيانات الفرع');
        }
    }

    public function print_selected()
    {
        $branches = Branch::all();
        return view('admin.branches.print', compact('branches'));
    }

    private function currentSubscriber(Request $request): ?Subscriber
    {
        $user = $request->user('admin-web');

        if (! $user || ! filled($user->subscriber_id)) {
            return null;
        }

        return $user->subscriber ?: Subscriber::query()->find($user->subscriber_id);
    }

    private function ensureSubscriberCanAddBranch(?Subscriber $subscriber): void
    {
        if (! $subscriber || blank($subscriber->max_branches) || (int) $subscriber->max_branches <= 0) {
            return;
        }

        $branchesCount = Branch::query()
            ->where('subscriber_id', $subscriber->id)
            ->count();

        if ($branchesCount >= (int) $subscriber->max_branches) {
            throw ValidationException::withMessages([
                'name' => ['تم الوصول إلى الحد الأقصى للفروع في هذا الاشتراك.'],
            ]);
        }
    }

    private function ensureBranchBelongsToSubscriber(?int $subscriberId, Branch $branch): void
    {
        if (! $subscriberId) {
            return;
        }

        abort_unless(
            (int) $branch->subscriber_id === (int) $subscriberId,
            403,
            'لا يمكنك الوصول إلى فرع خارج حساب المشترك الحالي.'
        );
    }
}
