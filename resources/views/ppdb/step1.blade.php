@extends('layouts.app')
@php($title = 'PPDB - Data Calon Siswa')
@section('content')
  @include('ppdb._stepper', ['current' => 1])
  <div class="grid gap-6 lg:grid-cols-[1.05fr_.75fr]">
    <div class="ui-card p-6 md:p-8">
      <div class="mb-6">
        <div class="ui-kicker"><x-ui.icon name="user" class="h-4 w-4" />Step 1</div>
        <h1 class="mt-4 text-2xl font-black tracking-tight text-slate-950 md:text-3xl">Data Calon Siswa</h1>
        <p class="mt-2 text-sm leading-7 text-slate-600">Isi data dengan teliti sesuai dokumen resmi agar proses verifikasi berjalan lebih cepat dan meminimalkan revisi.</p>
      </div>
      <form method="post" action="{{ route('ppdb.step1.store') }}" data-loading="Menyimpan Step 1…" class="space-y-5">
        @csrf
        <div class="grid gap-5 md:grid-cols-2">
          <x-ui.field label="Nama Lengkap" name="name" :value="$student->name ?? ''" required />
          <x-ui.field label="Tanggal Lahir" name="dob" type="date" :value="isset($student->dob) ? $student->dob->format('Y-m-d') : ''" required />
          <x-ui.field label="Nomor Ponsel" name="phone" :value="$student->phone ?? ''" hint="Gunakan nomor aktif yang mudah dihubungi panitia." required />
          <x-ui.field label="Tempat Lahir" name="birthplace" :value="$student->birthplace ?? ''" />
        </div>
        <x-ui.textarea label="Alamat" name="address" :value="$student->address ?? ''" rows="4" hint="Tuliskan alamat domisili secara lengkap." required />
        <div class="grid gap-5 md:grid-cols-2">
          <label class="block">
            <span class="ui-field-label">Jenis Kelamin</span>
            @php($g = old('gender', $student->gender ?? ''))
            <select class="ui-select" name="gender">
              <option value="">Pilih jenis kelamin</option>
              <option value="Laki-Laki" {{ $g==='Laki-Laki'?'selected':'' }}>Laki-Laki</option>
              <option value="Perempuan" {{ $g==='Perempuan'?'selected':'' }}>Perempuan</option>
            </select>
            <x-field-error name="gender" />
          </label>
          <label class="block">
            <span class="ui-field-label">Agama</span>
            @php($r = old('religion_child', $student->religion_child ?? ''))
            <select class="ui-select" name="religion_child">
              <option value="">Pilih agama</option>
              @foreach(['Islam','Kristen Protestan','Kristen Katolik','Hindu','Buddha','Konghucu'] as $opt)
                <option value="{{ $opt }}" {{ $r===$opt?'selected':'' }}>{{ $opt }}</option>
              @endforeach
            </select>
            <x-field-error name="religion_child" />
          </label>
        </div>
        <div class="grid gap-5 md:grid-cols-2">
          <x-ui.field label="Hobi" name="student_hobby" :value="$student->student_hobby ?? ''" hint="Opsional, namun membantu kami mengenal calon siswa lebih baik." />
          <x-ui.field label="Cita-cita" name="goal" :value="$student->goal ?? ''" hint="Contoh: dokter, guru, desainer, atau profesi lainnya." />
        </div>
        <x-ui.textarea label="Motivasi Bergabung" name="motivation" :value="$student->motivation ?? ''" rows="3" hint="Tuliskan singkat alasan atau harapan Anda bergabung di SMA Ibnu Aqil." />
        <div class="flex flex-wrap gap-3 pt-2">
          <x-ui.button type="submit" icon="arrow-right" iconRight="arrow-right">Simpan & Lanjut ke Step 2</x-ui.button>
          <x-ui.button :href="route('student.dashboard')" variant="secondary" icon="arrow-left">Kembali ke Dashboard</x-ui.button>
        </div>
      </form>
    </div>
    <div class="space-y-5">
      <x-ui.card title="Panduan pengisian" description="Beberapa catatan agar data Anda lebih mudah diverifikasi.">
        <ul class="space-y-3 text-sm leading-7 text-slate-600">
          <li>• Gunakan nama lengkap sesuai akta atau kartu keluarga.</li>
          <li>• Pastikan tanggal lahir dan alamat tidak berbeda dengan dokumen pendukung.</li>
          <li>• Nomor ponsel sebaiknya aktif untuk menerima konfirmasi lanjutan dari panitia.</li>
        </ul>
      </x-ui.card>
      <x-ui.card title="Setelah step ini selesai" description="Sistem akan mengarahkan Anda ke data orang tua atau wali sebagai tahap berikutnya.">
        <div class="text-sm leading-7 text-slate-600">Step berikutnya berisi data ayah, ibu, dan wali. Isilah dengan informasi yang benar agar sekolah dapat melakukan verifikasi dan komunikasi lebih cepat.</div>
      </x-ui.card>
    </div>
  </div>
@endsection
