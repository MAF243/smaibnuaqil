@extends('layouts.app')
@php($title = 'PPDB - Data Orang Tua & Wali')
@section('content')
  @include('ppdb._stepper', ['current' => 2])
  <div class="ui-card p-6 md:p-8">
    <div class="mb-6 max-w-3xl">
      <div class="ui-kicker"><x-ui.icon name="users" class="h-4 w-4" />Step 2</div>
      <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">Data Orang Tua / Wali</h1>
      <p class="mt-2 text-sm leading-7 text-slate-600">Lengkapi data ayah, ibu, dan wali dengan teliti. Bagian wali dapat dikosongkan apabila tidak diperlukan.</p>
    </div>
    <form method="post" action="{{ route('ppdb.step2.store') }}" data-loading="Menyimpan Step 2…" class="space-y-8">
      @csrf
      <section class="rounded-[1.35rem] border border-border bg-slate-50 p-5 md:p-6">
        <div class="mb-5 flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="user" class="h-5 w-5" /></div>
          <div><h2 class="text-lg font-black tracking-tight text-slate-950">Data Ayah</h2><p class="text-sm text-slate-500">Informasi dasar ayah calon siswa.</p></div>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <x-ui.field label="Nama Ayah" name="father_name" :value="$student->father_name ?? ''" />
          <x-ui.field label="Nomor Ponsel" name="father_phone" :value="$student->father_phone ?? ''" />
          <x-ui.field label="Pekerjaan" name="father_job" :value="$student->father_job ?? ''" />
          <x-ui.field label="Email" name="father_email" :value="$student->father_email ?? ''" />
          <x-ui.field label="Penghasilan" name="father_income" :value="$student->father_income ?? ''" />
          <x-ui.field label="Tempat Lahir" name="father_birthplace" :value="$student->father_birthplace ?? ''" />
          <x-ui.field label="Tanggal Lahir" type="date" name="father_dob" :value="!empty($student->father_dob) ? \Illuminate\Support\Carbon::parse($student->father_dob)->format('Y-m-d') : ''" />
          <x-ui.field label="Agama" name="father_religion" :value="$student->father_religion ?? ''" />
        </div>
      </section>

      <section class="rounded-[1.35rem] border border-border bg-slate-50 p-5 md:p-6">
        <div class="mb-5 flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-primary/10 text-primary"><x-ui.icon name="user" class="h-5 w-5" /></div>
          <div><h2 class="text-lg font-black tracking-tight text-slate-950">Data Ibu</h2><p class="text-sm text-slate-500">Informasi dasar ibu calon siswa.</p></div>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <x-ui.field label="Nama Ibu" name="mother_name" :value="$student->mother_name ?? ''" />
          <x-ui.field label="Nomor Ponsel" name="mother_phone" :value="$student->mother_phone ?? ''" />
          <x-ui.field label="Pekerjaan" name="mother_job" :value="$student->mother_job ?? ''" />
          <x-ui.field label="Email" name="mother_email" :value="$student->mother_email ?? ''" />
          <x-ui.field label="Penghasilan" name="mother_income" :value="$student->mother_income ?? ''" />
          <x-ui.field label="Tempat Lahir" name="mother_birthplace" :value="$student->mother_birthplace ?? ''" />
          <x-ui.field label="Tanggal Lahir" type="date" name="mother_dob" :value="!empty($student->mother_dob) ? \Illuminate\Support\Carbon::parse($student->mother_dob)->format('Y-m-d') : ''" />
          <x-ui.field label="Agama" name="mother_religion" :value="$student->mother_religion ?? ''" />
        </div>
      </section>

      <section class="rounded-[1.35rem] border border-dashed border-border bg-white p-5 md:p-6">
        <div class="mb-5 flex items-center gap-3">
          <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-secondary text-secondary-foreground"><x-ui.icon name="shield" class="h-5 w-5" /></div>
          <div><h2 class="text-lg font-black tracking-tight text-slate-950">Data Wali</h2><p class="text-sm text-slate-500">Opsional. Isi hanya bila calon siswa menggunakan wali.</p></div>
        </div>
        <x-alert type="info" title="Opsional" class="mb-5">Bagian ini boleh dilewati jika data wali tidak diperlukan dalam proses pendaftaran.</x-alert>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
          <x-ui.field label="Nama Wali" name="guardian_name" :value="$student->guardian_name ?? ''" />
          <x-ui.field label="Hubungan dengan Siswa" name="guardian_relation" :value="$student->guardian_relation ?? ''" />
          <x-ui.field label="Nomor Ponsel" name="guardian_phone" :value="$student->guardian_phone ?? ''" />
          <x-ui.field label="Email" name="guardian_email" :value="$student->guardian_email ?? ''" />
          <x-ui.field label="Penghasilan" name="guardian_income" :value="$student->guardian_income ?? ''" />
          <x-ui.field label="Tempat Lahir" name="guardian_birthplace" :value="$student->guardian_birthplace ?? ''" />
          <x-ui.field label="Tanggal Lahir" type="date" name="guardian_dob" :value="!empty($student->guardian_dob) ? \Illuminate\Support\Carbon::parse($student->guardian_dob)->format('Y-m-d') : ''" />
          <x-ui.field label="Agama" name="guardian_religion" :value="$student->guardian_religion ?? ''" />
        </div>
      </section>

      <div class="flex flex-wrap gap-3 border-t border-border pt-6">
        <x-ui.button :href="route('ppdb.step1')" variant="secondary" icon="arrow-left">Kembali ke Step 1</x-ui.button>
        <x-ui.button type="submit" icon="arrow-right" iconRight="arrow-right">Simpan & Lanjut ke Step 3</x-ui.button>
      </div>
    </form>
  </div>
@endsection
