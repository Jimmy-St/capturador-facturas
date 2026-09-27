@props([
    'status' => 'media',
])
@php
    $normalized = strtolower(trim((string) $status));
    $configs = [
        'visto' => [
            'label'      => 'Visto',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="m16 9-5.5 5.5L8 12"/></svg>',
            'class'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon_color' => 'text-emerald-600',
        ],
        'baja' => [
            'label'      => 'Baja',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 16 -6-2"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
            'class'      => 'bg-rose-50 text-rose-700 border-rose-200',
            'icon_color' => '',
        ],
        'media' => [
            'label'      => 'Media',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 16 0-6"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
            'class'      => 'bg-orange-50 text-orange-700 border-orange-200',
            'icon_color' => '',
        ],
        'alta' => [
            'label'      => 'Alta',
            'icon'       => '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m12 16 6-2"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/></svg>',
            'class'      => 'bg-sky-50 text-sky-700 border-sky-200',
            'icon_color' => '',
        ],
    ];

    $badge = $configs[$normalized] ?? $configs['media'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold border {$badge['class']}"]) }}>
    {!! $badge['icon'] !!}
    <span>{{ $badge['label'] }}</span>
</span>