@props(['type' => 'info', 'title' => null, 'message' => null, 'dismissible' => false])
<x-ui.alert :type="$type" :title="$title" {{ $attributes }}>
  {{ $slot->isEmpty() ? $message : $slot }}
</x-ui.alert>
