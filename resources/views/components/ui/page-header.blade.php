@props(['title', 'description' => null, 'kicker' => null, 'icon' => null])
<div {{ $attributes->merge(['class' => 'mb-6 flex flex-col gap-4 md:flex-row md:items-end md:justify-between']) }}>
  <div class="max-w-3xl">
    @if($kicker)<div class="mb-3 inline-flex items-center gap-2 rounded-full border border-border bg-secondary px-3 py-1 text-xs font-black uppercase tracking-wider text-secondary-foreground">@if($icon)<x-ui.icon :name="$icon" class="h-3.5 w-3.5" />@endif{{ $kicker }}</div>@endif
    <h1 class="text-3xl font-black tracking-tight text-foreground md:text-4xl">{{ $title }}</h1>
    @if($description)<p class="mt-2 text-sm leading-6 text-muted-foreground md:text-base">{{ $description }}</p>@endif
  </div>
  @if(!$slot->isEmpty())<div class="flex flex-wrap gap-2">{{ $slot }}</div>@endif
</div>
