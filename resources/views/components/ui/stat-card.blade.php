@props(['label', 'value', 'icon' => 'activity', 'description' => null, 'tone' => 'emerald'])
<x-ui.card padding="p-5" {{ $attributes }}>
  <div class="flex items-center justify-between gap-4">
    <div>
      <div class="text-sm font-bold text-muted-foreground">{{ $label }}</div>
      <div class="mt-2 text-3xl font-black tracking-tight">{{ $value }}</div>
      @if($description)<p class="mt-1 text-xs font-semibold text-muted-foreground">{{ $description }}</p>@endif
    </div>
    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon :name="$icon" class="h-6 w-6" /></div>
  </div>
</x-ui.card>
