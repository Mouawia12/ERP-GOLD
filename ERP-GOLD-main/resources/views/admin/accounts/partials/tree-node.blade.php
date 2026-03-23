@php
    $hasChildren = $account->childrensRecursive->isNotEmpty();
    $nodeId = 'account-tree-node-' . $account->id;
    $childContainerId = $nodeId . '-children';
@endphp

<li class="account-tree__item">
    <div class="account-tree__row {{ $depth === 0 ? 'account-tree__row--root' : '' }}">
        @if ($hasChildren)
            <button
                type="button"
                class="account-tree__toggle"
                data-tree-toggle
                data-target="{{ $childContainerId }}"
                aria-expanded="{{ $depth === 0 ? 'true' : 'false' }}"
                aria-controls="{{ $childContainerId }}"
            >
                <i class="fas fa-chevron-left"></i>
            </button>
        @else
            <button type="button" class="account-tree__toggle account-tree__toggle--placeholder" disabled>
                <i class="fas fa-minus"></i>
            </button>
        @endif

        <div class="account-tree__content">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="account-tree__title">{{ $account->name }}</span>
                <span class="badge badge-light">{{ $account->code }}</span>
                <span class="badge badge-info">L{{ $account->level }}</span>
                <span class="badge badge-{{ $hasChildren ? 'primary' : 'secondary' }}">
                    {{ $hasChildren ? 'رئيسي' : 'نهائي' }}
                </span>
            </div>

            <div class="account-tree__meta">
                النوع: {{ __('main.accounts_types.' . $account->account_type) }}
                <span class="mx-2">|</span>
                التحويل: {{ __('main.transfers_sides.' . $account->transfer_side) }}
            </div>
        </div>
    </div>

    @if ($hasChildren)
        <ul id="{{ $childContainerId }}" class="account-tree__children {{ $depth === 0 ? '' : 'd-none' }}">
            @foreach ($account->childrensRecursive as $child)
                @include('admin.accounts.partials.tree-node', ['account' => $child, 'depth' => $depth + 1])
            @endforeach
        </ul>
    @endif
</li>
