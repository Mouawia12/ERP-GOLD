@foreach ($items as $item)
            <!-- أول صف: اسم الصنف -->
            <tr>
                <td rowspan="{{ $item->units->count() + 1 }}">
                    {{ $item->title }}
                </td>
                <td rowspan="{{ $item->units->count() + 1 }}">{{ $item->goldCarat->title }}</td>
            </tr>

            <!-- الصفوف الخاصة بالـ units -->
            @foreach ($item->units as $unit)
                <tr>

                    <td>{{ $unit->barcode }}</td>
                    <td>{{ $unit->weight }}</td>
                </tr>
            @endforeach
        @endforeach