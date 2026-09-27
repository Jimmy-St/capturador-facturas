@props([
    'status' => 'adeudado',
])
@php
    $normalized = strtolower(trim((string) $status));
    $configs = [
        'pagado' => [
            'label'      => 'Pagado',
            'icon'       => 'check-check',
            'class'      => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        ],
        'adeudado' => [
            'label'      => 'Adeudado',
            'icon'       => 'clock',
            'class'      => 'bg-amber-50 text-amber-700  border-amber-200',            
        ]
    ];
    $badge = $configs[$normalized] ?? $configs['adeudado'];
@endphp

<span class="inline-flex items-center space-x-1 px-2.5 py-1 rounded-lg text-xs font-bold border {{ $badge['class'] }}">
    <i data-lucide="{{ $badge['icon'] }}" class="w-3.5 h-3.5"></i>
    <span>{{ strtoupper($badge['label']) }}</span>
</span>