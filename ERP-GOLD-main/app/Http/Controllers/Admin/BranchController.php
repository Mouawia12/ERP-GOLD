<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountSetting;
use App\Models\AccountsTree;
use App\Models\Branch;
use App\Services\Zatca\OnBoarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:employee.branches.show', ['only' => ['index']]);
        $this->middleware('permission:employee.branches.add', ['only' => ['create', 'store']]);
        $this->middleware('permission:employee.branches.edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:employee.branches.delete', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        $data = Branch::all();
        return view('admin.branches.index', compact('data'));
    }

    public function create()
    {
        return view('admin.branches.create');
    }

    public function store(Request $request)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string',
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
            Branch::create($validated);
            DB::commit();
            return redirect()
                ->route('admin.branches.index')
                ->with('success', 'تم اضافة فرع بنجاح');
        } catch (\Exception $e) {
            dd($e);
            DB::rollBack();
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ اثناء اضافة الفرع');
        }
    }

    public function show($id)
    {
        $branch = Branch::findorfail($id);
        return view('admin.branches.show', compact('branch'));
    }

    public function zatca_form($id)
    {
        $branch = Branch::findorfail($id);
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
        return view('admin.branches.edit', compact('branch'));
    }

    public function update(Request $request, $id)
    {
        $validated = $this->validate($request, [
            'name' => 'required|string',
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
            ], $validated);
            DB::commit();
            return redirect()
                ->route('admin.branches.index')
                ->with('success', 'تم تعديل بيانات الفرع بنجاح');
        } catch (\Exception $e) {
            dd($e);
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
}
