<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\DataTableTrait;
use App\Support\AdminSchoolScope;
use App\Support\CashlessSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ModuleSettingController extends Controller
{
    use DataTableTrait;

    public function show(string $module): View
    {
        $config = config("module-settings.{$module}");
        abort_unless($config, 404);

        $stored = $module === 'cashless'
            ? CashlessSettings::all($this->cashlessSekolahId())
            : $this->loadSettings($module);

        $fields = collect($config['fields'])->map(function ($field) use ($stored) {
            $field['value'] = $stored[$field['key']] ?? $field['default'] ?? '';

            return $field;
        });

        return view('admin.partials.module-settings', [
            'title' => $config['title'],
            'module' => $module,
            'fields' => $fields,
        ]);
    }

    public function update(Request $request, string $module): JsonResponse
    {
        $config = config("module-settings.{$module}");
        abort_unless($config, 404);

        $keys = collect($config['fields'])->pluck('key')->all();
        $data = $request->only($keys);

        foreach ($config['fields'] as $field) {
            if ($field['type'] === 'checkbox') {
                $data[$field['key']] = $request->boolean($field['key']);
            }
        }

        if ($module === 'cashless') {
            CashlessSettings::updateForSekolah($this->cashlessSekolahId(), $data);

            return $this->jsonSuccess('Pengaturan berhasil disimpan.');
        }

        Storage::disk('local')->put("settings/{$module}.json", json_encode($data, JSON_PRETTY_PRINT));

        return $this->jsonSuccess('Pengaturan berhasil disimpan.');
    }

    private function cashlessSekolahId(): int
    {
        return AdminSchoolScope::resolveForStore(request());
    }

    private function loadSettings(string $module): array
    {
        $path = "settings/{$module}.json";
        if (! Storage::disk('local')->exists($path)) {
            return [];
        }

        return json_decode(Storage::disk('local')->get($path), true) ?? [];
    }
}
