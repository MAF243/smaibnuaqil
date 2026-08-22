{{--
  Blade-first UI assets for shared hosting.
  Uses Tailwind CDN plus a local design layer defined directly in Blade
  so the views remain attractive even without npm / Vite build.
--}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<script>
  tailwind = window.tailwind || {};
  tailwind.config = {
    darkMode: 'class',
    theme: {
      extend: {
        fontFamily: { sans: ['Inter','ui-sans-serif','system-ui','sans-serif'] },
        colors: {
          border: 'hsl(var(--border))', input: 'hsl(var(--input))', ring: 'hsl(var(--ring))',
          background: 'hsl(var(--background))', foreground: 'hsl(var(--foreground))',
          primary: { DEFAULT: 'hsl(var(--primary))', foreground: 'hsl(var(--primary-foreground))' },
          secondary: { DEFAULT: 'hsl(var(--secondary))', foreground: 'hsl(var(--secondary-foreground))' },
          muted: { DEFAULT: 'hsl(var(--muted))', foreground: 'hsl(var(--muted-foreground))' },
          accent: { DEFAULT: 'hsl(var(--accent))', foreground: 'hsl(var(--accent-foreground))' },
          destructive: { DEFAULT: 'hsl(var(--destructive))', foreground: 'hsl(var(--destructive-foreground))' },
          card: { DEFAULT: 'hsl(var(--card))', foreground: 'hsl(var(--card-foreground))' },
        },
        boxShadow: {
          soft: '0 8px 32px rgba(15,23,42,.08)',
          panel: '0 14px 40px rgba(2,6,23,.08)',
          halo: '0 10px 30px rgba(16,185,129,.12)',
        },
        borderRadius: { xl: '1rem', '2xl': '1.25rem', '3xl': '1.5rem' }
      }
    }
  };
</script>
<script src="https://cdn.tailwindcss.com"></script>
<style>
  :root{
    --background: 0 0% 100%;
    --foreground: 222.2 47.4% 11.2%;
    --card: 0 0% 100%;
    --card-foreground: 222.2 47.4% 11.2%;
    --primary: 160 84% 32%;
    --primary-foreground: 0 0% 100%;
    --secondary: 210 40% 96.1%;
    --secondary-foreground: 222.2 47.4% 11.2%;
    --muted: 210 40% 96.1%;
    --muted-foreground: 215.4 16.3% 46.9%;
    --accent: 210 40% 96.1%;
    --accent-foreground: 222.2 47.4% 11.2%;
    --destructive: 0 84.2% 60.2%;
    --destructive-foreground: 210 40% 98%;
    --border: 214.3 31.8% 91.4%;
    --input: 214.3 31.8% 91.4%;
    --ring: 160 84% 32%;
  }
  html{scroll-behavior:smooth}
  body{font-family:Inter,ui-sans-serif,system-ui,sans-serif}
  .ui-gradient{background:
    radial-gradient(circle at top left, rgba(16,185,129,.10), transparent 34%),
    radial-gradient(circle at top right, rgba(59,130,246,.08), transparent 30%),
    linear-gradient(180deg,#f8fafc 0%,#f8fafc 55%,#f1f5f9 100%);
  }
  .ui-container{width:100%;max-width:1240px;margin-inline:auto;padding-inline:1rem}
  @media (min-width:768px){.ui-container{padding-inline:1.5rem}}
  @media (min-width:1280px){.ui-container{padding-inline:2rem}}
  .ui-section{padding-block:4rem}
  @media (min-width:768px){.ui-section{padding-block:5rem}}
  .ui-card,.ui-surface,.admin-surface,.admin-card{border:1px solid rgba(226,232,240,.9);background:rgba(255,255,255,.86);backdrop-filter:blur(12px);border-radius:1.5rem;box-shadow:0 10px 35px rgba(15,23,42,.06)}
  .ui-dark-card,.admin-surface,.admin-card{background:linear-gradient(180deg,rgba(15,23,42,.88),rgba(2,6,23,.92));border-color:rgba(51,65,85,.75);box-shadow:0 18px 45px rgba(2,6,23,.30)}
  .ui-kicker{display:inline-flex;align-items:center;gap:.5rem;border-radius:999px;border:1px solid rgba(16,185,129,.18);background:rgba(236,253,245,.9);padding:.45rem .8rem;color:#047857;font-size:.72rem;font-weight:800;letter-spacing:.12em;text-transform:uppercase}
  .ui-title{font-size:clamp(2rem,4vw,4rem);line-height:1.03;font-weight:900;letter-spacing:-.04em;color:#0f172a}
  .ui-lead{font-size:1.05rem;line-height:1.9;color:#475569}
  .ui-btn{display:inline-flex;align-items:center;justify-content:center;gap:.65rem;border-radius:1rem;padding:.8rem 1rem;font-size:.92rem;font-weight:800;transition:all .18s ease;border:1px solid transparent}
  .ui-btn:hover{transform:translateY(-1px)}
  .ui-btn-primary{background:#059669;color:white;box-shadow:0 12px 30px rgba(5,150,105,.18)}
  .ui-btn-primary:hover{background:#047857}
  .ui-btn-secondary{background:white;color:#0f172a;border-color:rgba(203,213,225,.95)}
  .ui-btn-secondary:hover{background:#f8fafc}
  .ui-btn-outline{background:transparent;color:inherit;border-color:rgba(148,163,184,.3)}
  .ui-btn-outline:hover{background:rgba(255,255,255,.08)}
  .ui-btn-sm{padding:.58rem .82rem;border-radius:.9rem;font-size:.8rem}
  .ui-btn-lg{padding:.95rem 1.2rem;border-radius:1rem;font-size:1rem}
  .ui-field-label{display:block;margin-bottom:.55rem;font-size:.86rem;font-weight:800;color:#0f172a}
  .ui-input,.ui-select,.ui-textarea{width:100%;border-radius:1rem;border:1px solid rgba(203,213,225,.95);background:white;padding:.82rem .95rem;font-size:.93rem;color:#0f172a;box-shadow:0 1px 2px rgba(15,23,42,.04)}
  .ui-input:focus,.ui-select:focus,.ui-textarea:focus{outline:none;border-color:rgba(16,185,129,.55);box-shadow:0 0 0 4px rgba(16,185,129,.12)}
  .ui-help{margin-top:.4rem;font-size:.76rem;line-height:1.45;color:#64748b}
  .ui-table{width:100%;border-collapse:separate;border-spacing:0;overflow:hidden;border-radius:1rem}
  .ui-table th,.ui-table td{padding:.95rem 1rem;text-align:left;border-bottom:1px solid rgba(226,232,240,.8);vertical-align:top}
  .ui-table thead th{font-size:.75rem;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#64748b;background:#f8fafc}
  .ui-table tbody tr:hover{background:rgba(248,250,252,.8)}
  .prose-clean{color:#334155;line-height:1.95;font-size:1rem}
  .prose-clean p+ p{margin-top:1rem}
  .prose-clean h2,.prose-clean h3{margin-top:1.4rem;margin-bottom:.75rem;font-weight:800;color:#0f172a}
  .admin-body{background:radial-gradient(circle at top left, rgba(16,185,129,.12), transparent 28%),linear-gradient(180deg,#020617 0%,#0f172a 100%)}
  .admin-navlink{display:flex;align-items:center;gap:.7rem;border-radius:1rem;padding:.82rem .95rem;font-size:.9rem;font-weight:700;color:#cbd5e1;transition:all .18s ease}
  .admin-navlink:hover{background:rgba(30,41,59,.75);color:#fff}
  .admin-navlink.active{background:linear-gradient(135deg,rgba(16,185,129,.18),rgba(59,130,246,.10));color:#fff;border:1px solid rgba(16,185,129,.25)}
  .admin-kpi{border-radius:1.35rem;border:1px solid rgba(51,65,85,.8);background:linear-gradient(180deg,rgba(15,23,42,.82),rgba(2,6,23,.88));padding:1.25rem}
  .admin-kpi-label{font-size:.82rem;font-weight:800;color:#94a3b8}
  .admin-kpi-value{margin-top:.65rem;font-size:2rem;font-weight:900;letter-spacing:-.04em;color:#fff}
  .ui-stat-chip{display:inline-flex;align-items:center;gap:.5rem;border-radius:999px;background:#f8fafc;padding:.55rem .8rem;font-size:.78rem;font-weight:800;color:#334155;border:1px solid rgba(226,232,240,.95)}
  .ui-hero-panel{border-radius:1.75rem;border:1px solid rgba(255,255,255,.16);background:linear-gradient(180deg,rgba(255,255,255,.12),rgba(255,255,255,.06));box-shadow:0 20px 50px rgba(2,6,23,.25);backdrop-filter:blur(16px)}
  .ui-disclosure summary{list-style:none}
  .ui-disclosure summary::-webkit-details-marker{display:none}
  .ui-disclosure-panel{margin-top:1rem;border-radius:1rem;border:1px solid rgba(226,232,240,.9);background:#f8fafc;padding:1rem}
  .timeline-line{position:relative;padding-left:1.15rem}
  .timeline-line:before{content:'';position:absolute;left:.35rem;top:1.3rem;bottom:-1rem;width:2px;background:linear-gradient(180deg,rgba(16,185,129,.35),rgba(148,163,184,.18))}
  .timeline-line:last-child:before{display:none}
</style>
