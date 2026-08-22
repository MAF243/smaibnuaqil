@props(['icon' => 'circle-help', 'title' => 'Belum ada data', 'description' => null, 'action' => null, 'href' => null])
<x-ui.empty-state :icon="$icon" :title="$title" :description="$description" :action="$action" :href="$href" {{ $attributes }} />
