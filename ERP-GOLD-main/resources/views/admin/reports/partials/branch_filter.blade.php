@php
    $branchFieldId = $branchFieldId ?? 'report_branch_ids';
    $branchHiddenFieldId = $branchHiddenFieldId ?? ($branchFieldId . '_legacy');
    $branchLabelText = $branchLabelText ?? 'الفرع';
    $branchHelpText = $branchHelpText ?? 'اتركه بدون اختيار لعرض جميع الفروع المسموح بها لك.';
    $branches = collect($branches ?? []);

    $selectedBranchIds = collect(old('branch_ids', $defaultFilters['branch_ids'] ?? []))
        ->when(
            function ($collection) use ($defaultFilters) {
                return $collection->isEmpty() && filled(old('branch_id', $defaultFilters['branch_id'] ?? null));
            },
            function ($collection) use ($defaultFilters) {
                $collection->push(old('branch_id', $defaultFilters['branch_id'] ?? null));

                return $collection;
            }
        )
        ->map(fn ($branchId) => (int) $branchId)
        ->filter()
        ->unique()
        ->values()
        ->all();

    $singleBranch = $branches->count() === 1 ? $branches->first() : null;
@endphp

<div class="form-group">
    <label>{{ $branchLabelText }}</label>

    @if($branches->count() > 1)
        <select
            id="{{ $branchFieldId }}"
            class="form-control selectpicker report-branch-select"
            name="branch_ids[]"
            multiple
            data-hidden-target="{{ $branchHiddenFieldId }}"
            data-live-search="true"
            data-actions-box="true"
            data-selected-text-format="count > 2"
            title="جميع الفروع المسموح بها"
        >
            @foreach($branches as $branch)
                <option value="{{ $branch->id }}" @selected(in_array((int) $branch->id, $selectedBranchIds, true))>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        <input type="hidden" id="{{ $branchHiddenFieldId }}" name="branch_id" value="{{ count($selectedBranchIds) === 1 ? $selectedBranchIds[0] : '' }}">
        <small class="text-muted d-block mt-2">{{ $branchHelpText }}</small>
    @elseif($singleBranch)
        <input class="form-control" type="text" readonly value="{{ $singleBranch->name }}"/>
        <input type="hidden" name="branch_ids[]" value="{{ $singleBranch->id }}">
        <input type="hidden" id="{{ $branchHiddenFieldId }}" name="branch_id" value="{{ $singleBranch->id }}">
    @else
        <input class="form-control" type="text" readonly value="لا توجد فروع متاحة"/>
        <input type="hidden" id="{{ $branchHiddenFieldId }}" name="branch_id" value="">
    @endif
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof $ === 'undefined' || typeof $.fn.selectpicker === 'undefined') {
                return;
            }

            $('.report-branch-select').each(function () {
                const $select = $(this);
                const hiddenId = $select.data('hidden-target');
                const $hidden = $('#' + hiddenId);

                $select.selectpicker('render');
                $select.selectpicker('refresh');

                const syncBranchHiddenInput = () => {
                    const selectedValues = ($select.val() || []).filter(Boolean);
                    $hidden.val(selectedValues.length === 1 ? selectedValues[0] : '');
                };

                syncBranchHiddenInput();
                $select.on('changed.bs.select', syncBranchHiddenInput);
            });
        });
    </script>
@endonce
