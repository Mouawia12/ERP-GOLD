@extends('admin.layouts.master')

@section('content')
@can('employee.accounts.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('error') }}
        </div>
    @endif

    <style>
        .account-summary-card {
            border: 1px solid #e9edf4;
            border-radius: 16px;
            background: linear-gradient(180deg, #ffffff 0%, #f8fbff 100%);
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.06);
            height: 100%;
        }

        .account-summary-card .summary-label {
            color: #6b7280;
            font-size: 0.86rem;
            margin-bottom: 0.35rem;
        }

        .account-summary-card .summary-value {
            color: #111827;
            font-size: 1.7rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .account-summary-card .summary-note {
            color: #6b7280;
            font-size: 0.8rem;
        }

        .account-quick-link {
            display: block;
            border: 1px solid #e9edf4;
            border-radius: 14px;
            padding: 1rem 1.1rem;
            background: #fff;
            height: 100%;
            color: inherit;
            text-decoration: none;
            box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
            transition: transform 0.15s ease, box-shadow 0.15s ease;
        }

        .account-quick-link:hover {
            color: inherit;
            text-decoration: none;
            transform: translateY(-2px);
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.08);
        }

        .account-quick-link__title {
            font-weight: 700;
            color: #111827;
        }

        .account-quick-link__meta {
            color: #6b7280;
            font-size: 0.86rem;
        }

        .account-tree {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .account-tree__item {
            list-style: none;
        }

        .account-tree__row {
            display: flex;
            align-items: flex-start;
            gap: 0.75rem;
            padding: 0.8rem 0.9rem;
            border: 1px solid #edf2f7;
            border-radius: 14px;
            background: #fff;
            margin-bottom: 0.75rem;
        }

        .account-tree__row--root {
            background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
            border-color: #d6e4ff;
        }

        .account-tree__toggle {
            width: 2rem;
            height: 2rem;
            border: 0;
            border-radius: 999px;
            background: #eef4ff;
            color: #1d4ed8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, background-color 0.15s ease;
            flex-shrink: 0;
        }

        .account-tree__toggle[aria-expanded="true"] {
            transform: rotate(90deg);
            background: #dbeafe;
        }

        .account-tree__toggle--placeholder {
            background: #f3f4f6;
            color: #9ca3af;
            cursor: default;
        }

        .account-tree__content {
            flex: 1;
            min-width: 0;
        }

        .account-tree__title {
            font-weight: 700;
            color: #111827;
        }

        .account-tree__meta {
            color: #6b7280;
            font-size: 0.82rem;
            margin-top: 0.35rem;
        }

        .account-tree__children {
            list-style: none;
            margin: 0;
            padding-right: 1.5rem;
        }

        .account-tree__children.d-none {
            display: none;
        }

        .account-level-badge {
            border-radius: 999px;
            padding: 0.2rem 0.6rem;
            font-size: 0.78rem;
            font-weight: 700;
        }
    </style>

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h4 class="alert alert-primary text-center mb-2">{{ __('main.accounts') }}</h4>
                            <div class="text-muted">
                                قسم المحاسبة يعرض مستويات الشجرة، الأرصدة الافتتاحية، والربط السريع مع القيود.
                                @if ($activeFinancialYear)
                                    <span class="badge badge-info mr-2">السنة المالية النشطة: {{ $activeFinancialYear->description }}</span>
                                @else
                                    <span class="badge badge-warning mr-2">لا توجد سنة مالية نشطة</span>
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2">
                            @can('employee.accounts.add')
                                <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary shadow-sm">
                                    <i class="fas fa-plus-circle ml-1"></i>
                                    {{ __('main.add_new') }}
                                </a>
                            @endcan
                            <a href="{{ route('accounts.opening') }}" class="btn btn-sm btn-outline-primary shadow-sm">
                                <i class="fas fa-wallet ml-1"></i>
                                {{ __('main.accounts_opening') }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">إجمالي الحسابات</div>
                                <div class="summary-value">{{ number_format($stats['total_accounts']) }}</div>
                                <div class="summary-note">جميع الحسابات المعرّفة داخل الشجرة</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">الحسابات الرئيسية</div>
                                <div class="summary-value">{{ number_format($stats['root_accounts']) }}</div>
                                <div class="summary-note">المستوى الأول من شجرة الحسابات</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">الحسابات النهائية</div>
                                <div class="summary-value">{{ number_format($stats['leaf_accounts']) }}</div>
                                <div class="summary-note">حسابات بدون أبناء ويمكن البناء عليها تشغيليًا</div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">أعلى مستوى</div>
                                <div class="summary-value">{{ number_format($stats['max_level']) }}</div>
                                <div class="summary-note">أعمق مستوى مستخدم فعليًا في الشجرة</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">حسابات لها رصيد افتتاحي</div>
                                <div class="summary-value">{{ number_format($stats['accounts_with_opening_balance']) }}</div>
                                <div class="summary-note">بحسب السنة المالية النشطة</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">قيود يدوية</div>
                                <div class="summary-value">{{ number_format($stats['manual_journals_count']) }}</div>
                                <div class="summary-note">دفاتر يومية أُنشئت يدويًا من شاشة القيود</div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-12 mb-3">
                            <div class="account-summary-card p-3">
                                <div class="summary-label">مستندات القيود</div>
                                <div class="summary-value">{{ number_format($stats['journal_documents_count']) }}</div>
                                <div class="summary-note">
                                    {{ number_format($stats['transaction_journals_count']) }} قيد مرتبط بحركات النظام
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('accounts.opening') }}" class="account-quick-link">
                                <div class="account-quick-link__title">الأرصدة الافتتاحية</div>
                                <div class="account-quick-link__meta">إدخال ومراجعة أرصدة بداية السنة المالية للحسابات النهائية.</div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('accounts.settings.index') }}" class="account-quick-link">
                                <div class="account-quick-link__title">إعدادات الحسابات</div>
                                <div class="account-quick-link__meta">ربط الموديولات بحساباتها المحاسبية الأساسية.</div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('accounts.journals.index', 'transactions') }}" class="account-quick-link">
                                <div class="account-quick-link__title">قيود العمليات</div>
                                <div class="account-quick-link__meta">مراجعة القيود التي ينشئها النظام من البيع والشراء والسندات.</div>
                            </a>
                        </div>
                        <div class="col-lg-3 col-md-6 mb-3">
                            <a href="{{ route('accounts.journals.index', 'manual') }}" class="account-quick-link">
                                <div class="account-quick-link__title">القيود اليدوية</div>
                                <div class="account-quick-link__meta">إنشاء ومتابعة القيود اليدوية ومراجعة توازنها.</div>
                            </a>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xl-8">
                            <div class="card shadow mb-4">
                                <div class="card-header pb-0">
                                    <h5 class="mb-1">دليل الحسابات</h5>
                                    <p class="text-muted mb-0">عرض تفصيلي للحسابات مع المستوى ونوع الحساب وحساب الأب.</p>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="display w-100 table-bordered" id="example1" style="text-align: center;">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>{{ __('main.code') }}</th>
                                                    <th>{{ __('main.name') }}</th>
                                                    <th>المستوى</th>
                                                    <th>{{ __('main.account_type') }}</th>
                                                    <th>{{ __('main.parent_account') }}</th>
                                                    <th>الحالة الهيكلية</th>
                                                    <th>{{ __('main.actions') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($accounts as $account)
                                                    <tr>
                                                        <td>{{ $account->id }}</td>
                                                        <td>{{ $account->code }}</td>
                                                        <td class="text-right">{{ $account->name }}</td>
                                                        <td>
                                                            <span class="badge badge-info account-level-badge">L{{ $account->level }}</span>
                                                        </td>
                                                        <td>{{ __('main.accounts_types.' . $account->account_type) }}</td>
                                                        <td>{{ $account->parent?->name ?? 'لا يوجد' }}</td>
                                                        <td>
                                                            @if ($account->childrens_count > 0)
                                                                <span class="badge badge-primary">رئيسي</span>
                                                            @else
                                                                <span class="badge badge-secondary">نهائي</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @can('employee.accounts.edit')
                                                                <a href="{{ route('accounts.edit', $account->id) }}" class="btn btn-info btn-sm">
                                                                    <i class="fa fa-pen"></i>
                                                                </a>
                                                            @endcan
                                                            @can('employee.accounts.delete')
                                                                <button
                                                                    type="button"
                                                                    class="btn btn-danger btn-sm deleteBtn"
                                                                    data-id="{{ $account->id }}"
                                                                    data-name="{{ $account->name }}"
                                                                >
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            @endcan
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-xl-4">
                            <div class="card shadow mb-4">
                                <div class="card-header pb-0">
                                    <h5 class="mb-1">شجرة الحسابات حسب المستويات</h5>
                                    <p class="text-muted mb-0">الشجرة تُحمّل من الكنترولر مباشرة بدون استعلامات داخل الواجهة.</p>
                                </div>
                                <div class="card-body">
                                    @if ($roots->isEmpty())
                                        <div class="alert alert-warning mb-0">لا توجد حسابات رئيسية معرّفة بعد.</div>
                                    @else
                                        <ul class="account-tree">
                                            @foreach ($roots as $root)
                                                @include('admin.accounts.partials.tree-node', ['account' => $root, 'depth' => 0])
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modelTitle">{{ __('main.deleteModal') }}</label>
                    <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close" style="color: red; font-size: 20px; font-weight: bold;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <img src="../assets/img/warning.png" class="alertImage">
                    <label class="alertTitle">{{ __('main.delete_alert') }}</label>
                    <br>
                    <label class="alertSubTitle" id="delete-account-name"></label>
                    <div class="row">
                        <div class="col-6 text-center">
                            <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                                <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-check"></i></span>{{ __('main.confirm_btn') }}
                            </button>
                        </div>
                        <div class="col-6 text-center">
                            <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                                <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-close"></i></span>{{ __('main.cancel_btn') }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
<script type="text/javascript">
    let selectedAccountId = 0;

    document.querySelectorAll('[data-tree-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const targetId = this.dataset.target;
            if (!targetId) {
                return;
            }

            const tree = document.getElementById(targetId);
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            tree.classList.toggle('d-none');
            this.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
        });
    });

    $(document).ready(function () {
        $(document).on('click', '.deleteBtn', function () {
            selectedAccountId = $(this).data('id');
            $('#delete-account-name').text($(this).data('name'));
            $('#deleteModal').modal('show');
        });

        $(document).on('click', '.cancel-modal', function () {
            $('#deleteModal').modal('hide');
            selectedAccountId = 0;
        });
    });

    function confirmDelete() {
        let url = "{{ route('accounts.delete', ':id') }}";
        url = url.replace(':id', selectedAccountId);
        document.location.href = url;
    }
</script>
@endsection
