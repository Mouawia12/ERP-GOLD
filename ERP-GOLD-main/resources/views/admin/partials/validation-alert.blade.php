@if ($errors->any())
    <div class="alert alert-danger mb-4" role="alert">
        <div class="font-weight-bold mb-2">{{ $title ?? 'تعذر حفظ البيانات بسبب الأخطاء التالية:' }}</div>
        <ul class="mb-0 pr-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
