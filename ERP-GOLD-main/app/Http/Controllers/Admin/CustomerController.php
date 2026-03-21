<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Customer;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($type)
    {
        $customers = Customer::where('type', $type)->get();

        $accounts = Account::all();

        return view('admin.customers.index', ['type' => $type, 'customers' =>
            $customers, 'accounts' => $accounts]);
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
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'nullable',
            'email' => 'nullable|',
            'vat_no' => 'nullable',
            'region' => 'required_with:vat_no',
            'city' => 'required_with:vat_no',
            'district' => 'required_with:vat_no',
            'street_name' => 'required_with:vat_no',
            'building_number' => 'required_with:vat_no',
            'plot_identification' => 'required_with:vat_no',
            'postal_code' => 'required_with:vat_no',
            'type' => 'required'
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
                'errors' => $validator->errors()->all()
            ], 422);
        }

        try {
            $company = Customer::updateOrCreate([
                'id' => $request->id ?? null
            ], [
                'name' => $request->name,
                'phone' => $request->phone,
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
            ]);

            return response()->json([
                'status' => true,
                'message' => __('main.saved')
            ]);
        } catch (QueryException $ex) {
            return response()->json([
                'status' => false,
                'errors' => [$ex->getMessage()]
            ]);
        }
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
        $customer = Customer::find($id);
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
        $customer = Customer::find($id);
        if ($customer) {
            $customer->delete();
            return response()->json([
                'status' => true,
                'message' => __('main.deleted')
            ]);
        }
    }
}
