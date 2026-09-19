<?php

namespace App\Http\Traits;

use App\Http\Requests\Import\ImportSpreadsheetConfirmRequest;
use App\Http\Requests\Import\ImportSpreadsheetRequest;
use App\Services\SpreadsheetImportPreviewService;
use App\Support\AdminSekolahResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

trait HandlesSpreadsheetImportPreview
{
    abstract protected function importPreviewType(): string;

    abstract protected function importPreviewPermission(): string;

    /** @return class-string */
    abstract protected function importPreviewTemplateClass(): string;

    protected function importPreviewRequiresSekolah(): bool
    {
        return true;
    }

    protected function resolveImportSekolah(\Illuminate\Http\Request $request): array
    {
        if ($this->importPreviewRequiresSekolah()) {
            return AdminSekolahResolver::resolve($request->user(), $request->input('sekolah_id'));
        }

        return AdminSekolahResolver::resolveOptional($request->user(), $request->input('sekolah_id'));
    }

    protected function importPreviewRoutes(string $routePrefix): array
    {
        return [
            'previewRoute' => route($routePrefix.'.preview'),
            'confirmRoute' => route($routePrefix.'.confirm'),
            'clearPreviewRoute' => route($routePrefix.'.preview.clear'),
            'cachedPreviewRoute' => route($routePrefix.'.preview.show'),
        ];
    }

    public function showPreview(Request $request, SpreadsheetImportPreviewService $previewService): JsonResponse
    {
        $this->authorize($this->importPreviewPermission());

        $cached = $previewService->cachedPreview((int) $request->user()->id, $this->importPreviewType());

        if ($cached === null) {
            return $this->jsonSuccess('Tidak ada pratinjau import tersimpan.', ['preview' => null]);
        }

        return $this->jsonSuccess('Pratinjau import ditemukan.', ['preview' => $cached]);
    }

    public function storePreview(ImportSpreadsheetRequest $request, SpreadsheetImportPreviewService $previewService): JsonResponse
    {
        $this->authorize($this->importPreviewPermission());

        $resolved = $this->resolveImportSekolah($request);

        if ($resolved['error'] !== null) {
            return $this->jsonError($resolved['error']);
        }

        $sekolah = $resolved['sekolah'];
        $uploadedPath = $request->file('file')?->getRealPath();

        if (! is_string($uploadedPath) || ! is_file($uploadedPath)) {
            return $this->jsonError('File impor tidak valid.');
        }

        $templateClass = $this->importPreviewTemplateClass();
        $rows = $templateClass::parseRows($uploadedPath);

        if ($rows === []) {
            return $this->jsonError('File kosong atau format kolom tidak sesuai template.');
        }

        $preview = $previewService->buildPreview(
            (int) $request->user()->id,
            $this->importPreviewType(),
            $sekolah,
            $rows,
            (string) $request->file('file')?->getClientOriginalName()
        );

        return $this->jsonSuccess(
            sprintf(
                'Pratinjau siap: %d baris (%d baru, %d perbarui, %d tidak valid).',
                $preview['summary']['total'] ?? 0,
                $preview['summary']['will_create'] ?? 0,
                $preview['summary']['will_update'] ?? 0,
                $preview['summary']['invalid'] ?? 0
            ),
            ['preview' => $preview]
        );
    }

    public function confirmImport(ImportSpreadsheetConfirmRequest $request, SpreadsheetImportPreviewService $previewService): JsonResponse
    {
        $this->authorize($this->importPreviewPermission());

        $resolved = $this->resolveImportSekolah($request);

        if ($resolved['error'] !== null) {
            return $this->jsonError($resolved['error']);
        }

        try {
            $result = $previewService->confirm(
                (int) $request->user()->id,
                $this->importPreviewType(),
                $resolved['sekolah'],
                (string) $request->input('method')
            );
        } catch (InvalidArgumentException $e) {
            return $this->jsonError($e->getMessage());
        }

        $message = sprintf(
            'Import selesai: %d ditambahkan, %d diperbarui, %d dilewati.',
            $result['created'],
            $result['updated'],
            $result['skipped']
        );

        if ($result['errors'] !== []) {
            $message .= ' Beberapa baris gagal: '.implode(' ', array_slice($result['errors'], 0, 3));
            if (count($result['errors']) > 3) {
                $message .= ' …';
            }
        }

        return $this->jsonSuccess($message, $result);
    }

    public function clearPreview(Request $request, SpreadsheetImportPreviewService $previewService): JsonResponse
    {
        $this->authorize($this->importPreviewPermission());

        $previewService->clear((int) $request->user()->id, $this->importPreviewType());

        return $this->jsonSuccess('Data import sementara berhasil dihapus.');
    }
}
