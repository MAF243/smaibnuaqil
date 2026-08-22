@props(['name'])
@error($name)
  <p {{ $attributes->merge(['class' => 'mt-1 text-sm font-semibold text-red-600']) }}>{{ $message }}</p>
@enderror
