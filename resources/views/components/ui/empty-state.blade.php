@props(['icon' => 'circle-help', 'title' => 'Belum ada data', 'description' => null, 'action' => null, 'href' => null, 'actionLabel' => null, 'actionHref' => null])
@php($action = $action ?: $actionLabel)
@php($href = $href ?: $actionHref)
<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-[1.35rem] border border-dashed border-border bg-muted/35 px-6 py-10 text-center']) }}>
  <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 text-primary">
    <x-ui.icon :name="$icon" class="h-7 w-7" />
  </div>
  <div class="text-lg font-extrabold tracking-tight text-foreground">{{ $title }}</div>
  @if($description)<p class="mt-2 max-w-xl text-sm leading-6 text-muted-foreground">{{ $description }}</p>@endif
  @if($action && $href)<x-ui.button :href="$href" size="sm" class="mt-5" icon="arrow-right">{{ $action }}</x-ui.button>@endif
</div>
