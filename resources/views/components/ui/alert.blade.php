@props(['type' => 'info', 'title' => null, 'icon' => null])
@php
$map = [
 'success' => ['border-emerald-200 bg-emerald-50 text-emerald-900','check-circle'],
 'danger' => ['border-red-200 bg-red-50 text-red-900','circle-alert'],
 'warning' => ['border-amber-200 bg-amber-50 text-amber-900','circle-alert'],
 'info' => ['border-sky-200 bg-sky-50 text-sky-900','circle-help'],
];
[$classes, $defaultIcon] = $map[$type] ?? $map['info'];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-2xl border p-4 '.$classes]) }}>
  <div class="flex gap-3">
    <x-ui.icon :name="$icon ?: $defaultIcon" class="mt-0.5 h-5 w-5" />
    <div class="min-w-0 flex-1">
      @if($title)<div class="font-extrabold">{{ $title }}</div>@endif
      <div class="text-sm leading-6 {{ $title ? 'mt-1' : '' }}">{{ $slot }}</div>
    </div>
  </div>
</div>
