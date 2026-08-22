<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: {
          sans: ['Inter', 'ui-sans-serif', 'system-ui', 'sans-serif'],
        },
      }
    }
  }
</script>
<style type="text/tailwindcss">
  @layer base {
    html { scroll-behavior: smooth; }
    body { @apply bg-slate-50 text-slate-900 antialiased; }
    h1,h2,h3,h4,h5,h6 { @apply text-slate-950; }
    a { @apply transition-colors duration-200; }
    ::selection { @apply bg-emerald-200 text-slate-900; }
  }

  @layer components {
    .shell { @apply mx-auto max-w-7xl px-4 sm:px-6 lg:px-8; }
    .site-shell { @apply mx-auto max-w-7xl px-4 sm:px-6 lg:px-8; }
    .section { @apply py-14 sm:py-16 lg:py-20; }
    .section-tight { @apply py-10 sm:py-12; }
    .surface { @apply rounded-3xl border border-slate-200/80 bg-white shadow-[0_20px_60px_-24px_rgba(15,23,42,0.18)]; }
    .surface-muted { @apply rounded-3xl border border-white/50 bg-white/80 backdrop-blur shadow-[0_12px_40px_-20px_rgba(15,23,42,0.18)]; }
    .admin-surface { @apply rounded-3xl border border-slate-800 bg-slate-950/70 shadow-[0_16px_60px_-28px_rgba(0,0,0,0.6)]; }
    .section-title { @apply text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl; }
    .section-lead { @apply mt-2 max-w-2xl text-sm leading-7 text-slate-600 sm:text-base; }
    .eyebrow { @apply mb-3 inline-flex items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-emerald-700; }

    .btn { @apply inline-flex items-center justify-center gap-2 rounded-2xl px-4 py-2.5 text-sm font-semibold transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:pointer-events-none disabled:opacity-60; }
    .btn-sm { @apply rounded-xl px-3 py-2 text-xs; }
    .btn-lg { @apply rounded-2xl px-5 py-3 text-base; }
    .btn-brand { @apply btn bg-emerald-600 text-white shadow-lg shadow-emerald-600/20 hover:bg-emerald-700 focus:ring-emerald-500; }
    .btn-soft { @apply btn border border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-100 focus:ring-slate-300; }
    .btn-ghost { @apply btn bg-transparent text-slate-700 hover:bg-slate-100 focus:ring-slate-300; }
    .btn-dark { @apply btn bg-slate-950 text-white hover:bg-slate-800 focus:ring-slate-500; }
    .btn-danger { @apply btn bg-rose-600 text-white hover:bg-rose-700 focus:ring-rose-500; }
    .btn-outline-light { @apply btn border border-white/30 bg-white/10 text-white hover:bg-white/20 focus:ring-white/50; }

    .field-label { @apply mb-2 block text-sm font-semibold text-slate-700; }
    .field-help { @apply mt-2 text-xs leading-6 text-slate-500; }
    .field-error { @apply mt-2 text-sm font-medium text-rose-600; }
    .form-control, .form-select, .form-textarea {
        @apply block w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 placeholder:text-slate-400 shadow-sm outline-none transition duration-200;
        @apply focus:border-emerald-400 focus:ring-4 focus:ring-emerald-100;
    }
    .form-textarea { @apply min-h-[120px]; }
    .form-check { @apply flex items-start gap-3; }
    .form-check-input { @apply mt-1 h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500; }
    .form-check-label { @apply text-sm text-slate-600; }

    .badge-status { @apply inline-flex items-center gap-1 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-[0.16em]; }
    .badge-pending { @apply border-amber-200 bg-amber-50 text-amber-700; }
    .badge-verified { @apply border-emerald-200 bg-emerald-50 text-emerald-700; }
    .badge-rejected { @apply border-rose-200 bg-rose-50 text-rose-700; }
    .badge-incomplete { @apply border-slate-200 bg-slate-100 text-slate-600; }
    .badge-draft { @apply border-sky-200 bg-sky-50 text-sky-700; }

    .alert { @apply rounded-2xl border px-4 py-4 shadow-sm; }
    .alert-success { @apply border-emerald-200 bg-emerald-50 text-emerald-800; }
    .alert-danger { @apply border-rose-200 bg-rose-50 text-rose-800; }
    .alert-warning { @apply border-amber-200 bg-amber-50 text-amber-800; }
    .alert-info { @apply border-sky-200 bg-sky-50 text-sky-800; }

    .data-table-wrap { @apply overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-[0_16px_50px_-28px_rgba(15,23,42,0.2)]; }
    .data-table { @apply min-w-full divide-y divide-slate-200 text-sm; }
    .data-table thead { @apply bg-slate-50; }
    .data-table th { @apply px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-500; }
    .data-table td { @apply px-4 py-4 align-top text-slate-700; }
    .data-table tbody tr { @apply border-t border-slate-100; }
    .data-table tbody tr:hover { @apply bg-slate-50/80; }

    .dark-table-wrap { @apply overflow-hidden rounded-3xl border border-slate-800 bg-slate-950/70; }
    .dark-table { @apply min-w-full divide-y divide-slate-800 text-sm text-slate-200; }
    .dark-table thead { @apply bg-slate-900/80; }
    .dark-table th { @apply px-4 py-3 text-left text-xs font-bold uppercase tracking-[0.16em] text-slate-400; }
    .dark-table td { @apply px-4 py-4 align-top text-slate-200; }
    .dark-table tbody tr { @apply border-t border-slate-800; }
    .dark-table tbody tr:hover { @apply bg-slate-900/40; }

    .hero-wrap { @apply relative overflow-hidden bg-slate-950 text-white; }
    .hero-overlay { @apply absolute inset-0 bg-gradient-to-r from-slate-950/90 via-slate-900/70 to-slate-900/30; }
    .hero-grid { @apply relative grid gap-10 py-20 sm:py-24 lg:grid-cols-[1.15fr,0.85fr] lg:items-center lg:py-28; }
    .hero-card { @apply rounded-[28px] border border-white/10 bg-white/10 p-6 backdrop-blur-md shadow-[0_20px_70px_-30px_rgba(0,0,0,0.6)]; }
    .hero-metric { @apply rounded-2xl border border-white/10 bg-white/10 p-4 backdrop-blur; }

    .site-nav-link { @apply rounded-xl px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-900; }
    .site-nav-link-active { @apply bg-slate-900 text-white hover:bg-slate-900 hover:text-white; }
    .admin-nav-link { @apply flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-medium text-slate-300 transition hover:bg-slate-900 hover:text-white; }
    .admin-nav-link-active { @apply bg-emerald-500/15 text-emerald-200 ring-1 ring-inset ring-emerald-400/30; }
    .icon-chip { @apply inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-50 text-xl text-emerald-600; }
    .icon-chip-dark { @apply inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/10 text-xl text-white; }
    .stat-card { @apply rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_16px_50px_-32px_rgba(15,23,42,0.22)]; }
    .info-card { @apply rounded-3xl border border-slate-200 bg-white p-5 shadow-[0_14px_40px_-28px_rgba(15,23,42,0.18)]; }

    .prose-lite { @apply max-w-none text-sm leading-7 text-slate-700 sm:text-base; }
    .prose-lite p { @apply mb-4; }
    .prose-lite h1,.prose-lite h2,.prose-lite h3 { @apply mb-3 mt-8 font-bold tracking-tight text-slate-950; }
    .prose-lite ul { @apply mb-4 list-disc pl-5; }
    .prose-lite img { @apply my-6 rounded-3xl border border-slate-200 shadow-sm; }

    .empty-state { @apply flex flex-col items-center justify-center rounded-3xl border border-dashed border-slate-300 bg-slate-50 px-6 py-14 text-center; }
    .empty-icon { @apply mb-4 inline-flex h-16 w-16 items-center justify-center rounded-3xl bg-emerald-100 text-3xl text-emerald-600; }
    .empty-title { @apply text-lg font-bold text-slate-900; }
    .empty-desc { @apply mt-2 max-w-md text-sm leading-6 text-slate-500; }

    .stepper-wrap { @apply rounded-[28px] border border-slate-200 bg-white p-5 shadow-[0_16px_50px_-28px_rgba(15,23,42,0.18)]; }
    .step-pill { @apply inline-flex items-center gap-3 rounded-full border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-500; }
    .step-pill-active { @apply border-emerald-200 bg-emerald-50 text-emerald-700; }
    .step-pill-done { @apply border-emerald-200 bg-white text-slate-700; }
    .step-dot { @apply inline-flex h-8 w-8 items-center justify-center rounded-full bg-white text-xs font-bold ring-1 ring-slate-200; }

    .footer-link { @apply text-sm text-slate-300 hover:text-white; }
    .auth-panel { @apply rounded-[30px] border border-slate-200 bg-white p-6 shadow-[0_20px_60px_-28px_rgba(15,23,42,0.22)] sm:p-8; }
    .admin-input { @apply form-control border-slate-700 bg-slate-950/70 text-slate-100 placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-500/10; }
    .admin-textarea { @apply form-textarea border-slate-700 bg-slate-950/70 text-slate-100 placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-500/10; }
    .admin-select { @apply form-select border-slate-700 bg-slate-950/70 text-slate-100 placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-500/10; }
    .admin-muted { @apply text-slate-400; }
  }
</style>
