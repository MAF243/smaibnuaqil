@props(['title' => null, 'description' => null, 'padding' => 'p-6'])
<div {{ $attributes->merge(['class' => 'rounded-2xl border border-border bg-card text-card-foreground shadow-soft']) }}>
  @if($title || $description)
    <div class="border-b border-border px-6 py-5">
      @if($title)<h3 class="text-lg font-extrabold tracking-tight">{{ $title }}</h3>@endif
      @if($description)<p class="mt-1 text-sm leading-6 text-muted-foreground">{{ $description }}</p>@endif
    </div>
  @endif
  <div class="{{ $padding }}">{{ $slot }}</div>
</div>
