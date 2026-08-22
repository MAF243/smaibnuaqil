@props(['label' => null, 'name', 'type' => 'text', 'value' => null, 'hint' => null])
<label class="block">
  @if($label)<span class="mb-2 block text-sm font-bold text-foreground">{{ $label }}</span>@endif
  <input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" {{ $attributes->merge(['class' => 'block w-full rounded-xl border border-input bg-background px-3 py-2.5 text-sm text-foreground shadow-sm transition focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20']) }}>
  @if($hint)<span class="mt-1 block text-xs font-medium text-muted-foreground">{{ $hint }}</span>@endif
  <x-field-error :name="$name" />
</label>
