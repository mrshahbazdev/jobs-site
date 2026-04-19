@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'rounded-md bg-emerald-50 px-3 py-2 text-sm font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200']) }}>
        {{ $status }}
    </div>
@endif
