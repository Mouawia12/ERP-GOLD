@foreach($sections as $section)
    @php
        $sectionPatterns = $section['active_patterns'] ?? [];
        $sectionActive = ! empty($sectionPatterns) && request()->routeIs(...$sectionPatterns);
    @endphp
    <li class="slide {{ $sectionActive ? 'is-expanded' : '' }}">
        <a class="side-menu__item {{ $sectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
            <i class="{{ $section['icon'] }} side-menu__icon"></i>
            <span class="side-menu__label">
                {{ $section['label'] }}
            </span>
            <i class="angle fe fe-chevron-down"></i>
        </a>
        <ul class="slide-menu">
            @foreach($section['items'] as $item)
                @php
                    $itemPatterns = $item['active_patterns'] ?? [];
                    $itemActive = ! empty($itemPatterns) && request()->routeIs(...$itemPatterns);
                @endphp
                <li>
                    <a class="slide-item {{ $itemActive ? 'active' : '' }}" href="{{ route($item['route']) }}">
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>
    </li>
@endforeach
