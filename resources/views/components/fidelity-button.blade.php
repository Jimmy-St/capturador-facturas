@props([
    'status' => 'media',
])
@php
    $normalized = strtolower(trim((string) $status));
    $configs = [
        'todas' => [
            'label'      => 'Todas',
            'route'       => '',
            'class'      => 'bg-gray-100 text-gray-600',
        ],
        'baja' => [
            'label'      => 'Baja',
            'route'       => '',
            'class'      => 'bg-rose-50 text-rose-700 border-rose-200',
        ],
        'media' => [
            'label'      => 'Media',
            'route'       => '',
            'class'      => 'bg-orange-50 text-orange-700 border-orange-200',
        ],
        'alta' => [
            'label'      => 'Alta',
            'route'       => '',
            'class'      => 'bg-sky-50 text-sky-700 border-sky-200',
        ],
        'visto' => [
            'label'      => 'Visto',
            'route'       => '',
            'class'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ],
    ];

    $badge = $configs[$normalized] ?? $configs['media'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold border {$badge['class']}"]) }}>
    {!! $badge['icon'] !!}
    <span>{{ $badge['label'] }}</span>
</span>

<a href="{{ route('dashboard', array_merge(request()->query(), ['fidelity' => 'baja'])) }}" 
    class="px-2.5 py-1 rounded-lg text-xs font-semibold transition-all {{ request('fidelity') == 'baja' ? 'bg-rose-600 text-white shadow-sm' : 'bg-rose-50 text-rose-700 hover:bg-rose-100 border border-rose-200/60' }}">
    Baja
</a>