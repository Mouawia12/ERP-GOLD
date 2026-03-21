<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\FinancialYear;
use App\Models\JournalEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use DataTables;

class JournalEntryController extends Controller
{
    public function journals(Request $request, $type)
    {
        $type = $type;

        if ($request->ajax()) {
            $data = JournalEntry::when($type === 'manual', function ($query) {
                return $query->whereNull('journalable_type');
            })->when($type === 'transactions', function ($query) {
                return $query->whereNotNull('journalable_type');
            })->get();
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('journal_date', function ($row) {
                    return Carbon::parse($row->journal_date)->format('Y-m-d');
                })
                ->addColumn('notes', function ($row) {
                    return $row->notes;
                })
                ->addColumn('debit_total', function ($row) {
                    return round($row->documents->sum('debit'), 2);
                })
                ->addColumn('credit_total', function ($row) {
                    return round($row->documents->sum('credit'), 2);
                })
                ->addColumn('action', function ($row) {
                    $btn = '<button type="button" class="btn btn-labeled btn-success" onclick="showPayments(' . $row->id . ')">
                                    <i class="fa fa-eye"></i> 
                                 </button>  ';

                    if (is_null($row->journalable_type)) {
                        $btn .= '<button type="button" class="btn btn-labeled btn-danger deleteBtn" id="' . $row->id . '"> 
                                    <i class="fa fa-trash"></i> 
                                </button>';
                    }
                    return $btn;
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('admin.accounts.journal_entry.index', compact('type'));
    }

    public function create()
    {
        return view('admin.accounts.journal_entry.form');
    }

    public function preview_journal($id)
    {
        $journal = JournalEntry::find($id);
        $html = view('admin.accounts.journal_entry.preview_journal', compact('journal'))->render();

        return $html;
    }

    public function store(Request $request)
    {
        if ($request->isMethod('GET')) {
            abort(403);
        }
        $validator = Validator::make($request->all(), [
            'date' => 'required',
            'notes' => 'required',
            'account_id' => 'required|array'
        ],
            [
                'date.required' => __('validations.date_required'),
                'notes.required' => __('validations.notes_required'),
                'account_id.required' => __('validations.account_id_required'),
                'account_id.array' => __('validations.account_id_array'),
            ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first()
            ], 422);
        }

        $debits = array_sum($request->debit ?? []);
        $credits = array_sum($request->credit ?? []);
        if (floatval($debits) != floatval($credits)) {
            return response()->json([
                'status' => false,
                'errors' => __('validations.debits_credits_not_equal')
            ], 422);
        }

        try {
            DB::beginTransaction();
            $journal = JournalEntry::create([
                'journal_date' => $request->date,
                'notes' => $request->notes,
                'financial_year' => FinancialYear::where('is_active', true)->first()->id,
                'branch_id' => Branch::first()->id,
            ]);
            foreach ($request->account_id as $key => $value) {
                $documents[] = [
                    'document_date' => $journal->journal_date,
                    'account_id' => $value,
                    'debit' => $request->debit[$key],
                    'credit' => $request->credit[$key],
                ];
            }
            $journal->documents()->createMany($documents);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => __('main.created')
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'errors' => $th->getMessage()
            ], 500);
        }
    }

    public function delete($id)
    {
        try {
            $journal = JournalEntry::find($id);
            $journal->documents()->delete();
            $journal->delete();
            return redirect()->route('accounts.journals.index', ['type' => 'manual'])->with('success', __('main.deleted'));
        } catch (\Throwable $th) {
            return redirect()->route('accounts.journals.index', ['type' => 'manual'])->with('error', $th->getMessage());
        }
    }
}
