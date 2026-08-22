@props([
  'href' => null,
  'variant' => 'primary',
  'size' => 'md',
  'type' => 'button',
  'icon' => null,
  'iconRight' => null,
])
@php
$variantClasses = [
  'primary' => 'bg-primary text-primary-foreground shadow-sm shadow-emerald-900/10 hover:bg-primary/90',
  'secondary' => 'bg-secondary text-secondary-foreground border border-border hover:bg-secondary/75',
  'outline' => 'border border-border bg-background hover:bg-accent hover:text-accent-foreground',
  'ghost' => 'hover:bg-accent hover:text-accent-foreground',
  'danger' => 'bg-destructive text-destructive-foreground hover:bg-destructive/90',
  'link' => 'text-primary underline-offset-4 hover:underline px-0',
];
$sizeClasses = [
  'sm' => 'h-9 rounded-xl px-3 text-xs',
  'md' => 'h-10 rounded-xl px-4 py-2 text-sm',
  'lg' => 'h-12 rounded-2xl px-6 text-base',
];
$classes = 'inline-flex items-center justify-center gap-2 whitespace-nowrap font-bold transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 hover:-translate-y-0.5 ' . ($variantClasses[$variant] ?? $variantClasses['primary']) . ' ' . ($sizeClasses[$size] ?? $sizeClasses['md']);
@endphp
@if($href)
  <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>@if($icon)<x-ui.icon :name="$icon" class="h-4 w-4" />@endif<span>{{ $slot }}</span>@if($iconRight)<x-ui.icon :name="$iconRight" class="h-4 w-4" />@endif</a>
@else
  <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>@if($icon)<x-ui.icon :name="$icon" class="h-4 w-4" />@endif<span>{{ $slot }}</span>@if($iconRight)<x-ui.icon :name="$iconRight" class="h-4 w-4" />@endif</button>
@endif
