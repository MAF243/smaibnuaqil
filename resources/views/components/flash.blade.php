@if(session('success'))<x-alert type="success" title="Berhasil">{{ session('success') }}</x-alert>@endif
@if(session('error'))<x-alert type="danger" title="Terjadi kesalahan">{{ session('error') }}</x-alert>@endif
@if(session('warning'))<x-alert type="warning" title="Perhatian">{{ session('warning') }}</x-alert>@endif
@if(session('info'))<x-alert type="info" title="Informasi">{{ session('info') }}</x-alert>@endif
@if($errors->any())<div class="mb-4"><x-alert type="danger" title="Validasi belum lengkap"><ul class="mb-0 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></x-alert></div>@endif
