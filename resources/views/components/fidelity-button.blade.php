@props([
    'status' => 'todas',
])

@php
    $normalized = strtolower(trim((string) $status));

    $configs = [
        'todas' => [
            'label'    => 'Todas',
            'active'   => 'bg-slate-900 text-white shadow-sm',
            'inactive' => 'bg-gray-100 text-gray-600 hover:bg-gray-200',
        ],
        'baja' => [
            'label'    => 'Baja',
            'active'   => 'bg-rose-600 text-white shadow-sm',
            'inactive' => 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60',
        ],
        'media' => [
            'label'    => 'Media',
            'active'   => 'bg-orange-600 text-white shadow-sm',
            'inactive' => 'bg-orange-50 text-orange-700 hover:bg-orange-100 border border-orange-200/60',
        ],
        'alta' => [
            'label'    => 'Alta',
            'active'   => 'bg-sky-600 text-white shadow-sm',
            'inactive' => 'bg-sky-50 text-sky-700 hover:bg-sky-100 border border-sky-200/60',
        ],
        'vista' => [
            'label'    => 'Vista',
            'active'   => 'bg-emerald-600 text-white shadow-sm',
            'inactive' => 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-200/60',
        ],
    ];

    if ($normalized === 'visto') {
        $normalized = 'vista';
    }

    $btn = $configs[$normalized] ?? $configs['todas'];

    $isActive = ($normalized === 'todas') 
        ? empty(request('fidelity')) 
        : (request('fidelity') === $normalized);

    $url = ($normalized === 'todas')
        ? route('dashboard', request()->except('fidelity'))
        : route('dashboard', array_merge(request()->query(), ['fidelity' => $normalized]));
@endphp

<a href="{{ $url }}" 
   {{ $attributes->merge(['class' => 'px-2.5 py-1 rounded-lg text-xs font-semibold transition-all ' . ($isActive ? $btn['active'] : $btn['inactive'])]) }}>
    {{ $btn['label'] }}
</a>