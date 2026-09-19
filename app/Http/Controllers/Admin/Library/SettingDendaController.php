<?php

namespace App\Http\Controllers\Admin\Library;

use App\Http\Controllers\Controller;
use App\Models\PeminjamanBuku;
use App\Support\LibrarySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingDendaController extends Controller
{
    public function index(): View
    {
        return view('admin.perpustakaan.setting-denda', $this->viewData());
    }

    public function update(Request $request): JsonResponse
    {
        $validated = LibrarySettings::validateFineSettings($request->all());
        LibrarySettings::saveFineSettings($validated);

        return $this->jsonSuccess('Nominal denda berhasil disimpan.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function viewData(): array
    {
        $formData = LibrarySettings::fineSettingsFormData();

        return [
            'title' => 'Setting Nominal Denda',
            'finePerDay' => $formData['fine_per_day'],
            'kondisiFines' => $formData['kondisi_fines'],
            'kondisiChoices' => $this->kondisiChoices(),
            'formAction' => route('admin.perpustakaan.setting-denda.update'),
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function kondisiChoices(): array
    {
        return collect(PeminjamanBuku::kondisiOptions())
            ->except(PeminjamanBuku::KONDISI_BAIK)
            ->all();
    }

    protected function jsonSuccess(string $message, mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status);
    }
}
