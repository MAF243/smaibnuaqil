<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\AdminActivity;
use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteSettingController extends Controller
{
    private function get(string $key, ?string $default = null): ?string
    {
        $row = SiteSetting::query()->find($key);
        $val = $row?->setting_value;
        $val = is_string($val) ? trim($val) : null;
        return ($val !== null && $val !== '') ? $val : $default;
    }

    private function put(string $key, ?string $value): void
    {
        $value = is_string($value) ? trim($value) : $value;
        SiteSetting::query()->updateOrCreate(
            ['setting_name' => $key],
            ['setting_value' => $value]
        );
    }

    private function storeHeroImage(Request $request, ?string $currentPath): ?string
    {
        if (!$request->hasFile('hero_image')) {
            return $currentPath;
        }

        $file = $request->file('hero_image');
        if (!$file || !$file->isValid()) {
            return $currentPath;
        }

        $dir = public_path('uploads/hero');
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $safeBase = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $name = 'hero_' . now()->format('Ymd_His') . '_' . Str::random(6) . '_' . ($safeBase ?: 'image') . '.' . $ext;

        $file->move($dir, $name);
        $newPath = 'uploads/hero/' . $name;

        if ($currentPath && str_starts_with($currentPath, 'uploads/hero/')) {
            $old = public_path($currentPath);
            if (is_file($old)) {
                @unlink($old);
            }
        }

        return $newPath;
    }

    public function hero()
    {
        return view('admin.settings.hero', [
            'hero_title' => $this->get('hero_title', "SMA Ibnu'Aqil"),
            'hero_subtitle' => $this->get('hero_subtitle', 'Serba Bisa, Pasti Bisa SUKSES!'),
            'hero_image_path' => $this->get('hero_image_path', 'asset/gedunghd.png'),
        ]);
    }

    public function heroUpdate(Request $request)
    {
        $data = $request->validate([
            'hero_title' => ['nullable','string','max:120'],
            'hero_subtitle' => ['nullable','string','max:255'],
            'hero_image' => ['nullable','image','max:4096'],
            'remove_hero_image' => ['nullable'],
        ]);

        $current = $this->get('hero_image_path', null);

        if ($request->boolean('remove_hero_image')) {
            if ($current && str_starts_with($current, 'uploads/hero/')) {
                $old = public_path($current);
                if (is_file($old)) {
                    @unlink($old);
                }
            }
            $current = null;
        }

        $current = $this->storeHeroImage($request, $current);

        $this->put('hero_title', $data['hero_title'] ?? $this->get('hero_title'));
        $this->put('hero_subtitle', $data['hero_subtitle'] ?? $this->get('hero_subtitle'));
        $this->put('hero_image_path', $current);
        AdminActivity::log((int) session('admin_id'), 'settings.hero_updated', 'site_settings', null, 'Memperbarui pengaturan hero');

        return back()->with('ok', 'Pengaturan hero berhasil disimpan.');
    }

    public function map()
    {
        return view('admin.settings.map', [
            'map_iframe_src' => $this->get('map_iframe_src', ''),
            'map_title' => $this->get('map_title', 'Lokasi Sekolah'),
            'map_address' => $this->get('map_address', "[Alamat Lengkap SMA Ibnu'Aqil]"),
        ]);
    }

    public function mapUpdate(Request $request)
    {
        $data = $request->validate([
            'map_iframe_src' => ['nullable','string','max:2000'],
            'map_title' => ['nullable','string','max:120'],
            'map_address' => ['nullable','string','max:255'],
        ]);

        $this->put('map_iframe_src', $data['map_iframe_src'] ?? '');
        $this->put('map_title', $data['map_title'] ?? 'Lokasi Sekolah');
        $this->put('map_address', $data['map_address'] ?? '');
        AdminActivity::log((int) session('admin_id'), 'settings.map_updated', 'site_settings', null, 'Memperbarui pengaturan peta');

        return back()->with('ok', 'Pengaturan peta berhasil disimpan.');
    }
}
