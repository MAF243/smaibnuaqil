@props(['status' => null, 'variant' => null])
@php
$status = strtolower((string) ($status ?: $variant ?: 'default'));
$labelMap = [
 'draft'=>'Draft','submitted'=>'Submitted','pending'=>'Pending','under_review'=>'Under Review','review'=>'Under Review','interview'=>'Interview','accepted'=>'Accepted','verified'=>'Verified','completed'=>'Completed','rejected'=>'Rejected','incomplete'=>'Incomplete','valid'=>'Valid','invalid'=>'Invalid','published'=>'Published','archived'=>'Archived','active'=>'Active','inactive'=>'Inactive'
];
$classMap = [
 'draft'=>'bg-slate-100 text-slate-700 border-slate-200','submitted'=>'bg-sky-50 text-sky-700 border-sky-200','pending'=>'bg-amber-50 text-amber-800 border-amber-200','under_review'=>'bg-violet-50 text-violet-700 border-violet-200','review'=>'bg-violet-50 text-violet-700 border-violet-200','interview'=>'bg-orange-50 text-orange-700 border-orange-200','accepted'=>'bg-emerald-50 text-emerald-700 border-emerald-200','verified'=>'bg-emerald-50 text-emerald-700 border-emerald-200','completed'=>'bg-teal-50 text-teal-700 border-teal-200','rejected'=>'bg-red-50 text-red-700 border-red-200','incomplete'=>'bg-zinc-100 text-zinc-700 border-zinc-200','valid'=>'bg-emerald-50 text-emerald-700 border-emerald-200','invalid'=>'bg-red-50 text-red-700 border-red-200','published'=>'bg-emerald-50 text-emerald-700 border-emerald-200','archived'=>'bg-zinc-100 text-zinc-700 border-zinc-200','active'=>'bg-emerald-50 text-emerald-700 border-emerald-200','inactive'=>'bg-zinc-100 text-zinc-700 border-zinc-200'
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-extrabold '.($classMap[$status] ?? 'bg-secondary text-secondary-foreground border-border')]) }}>{{ $slot->isEmpty() ? ($labelMap[$status] ?? ucfirst(str_replace('_',' ', $status))) : $slot }}</span>
