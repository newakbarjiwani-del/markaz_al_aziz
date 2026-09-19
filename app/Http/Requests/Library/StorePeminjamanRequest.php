<?php

namespace App\Http\Requests\Library;

use App\Models\PeminjamanBuku;
use App\Support\SoftDeleteRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePeminjamanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $type = $this->input('borrower_type', PeminjamanBuku::BORROWER_SISWA);

        return [
            'borrower_type' => ['required', Rule::in(array_keys(PeminjamanBuku::borrowerTypeOptions()))],
            'siswa_id' => [
                Rule::requiredIf($type === PeminjamanBuku::BORROWER_SISWA),
                'nullable',
                SoftDeleteRules::exists('siswa'),
            ],
            'guru_id' => [
                Rule::requiredIf($type === PeminjamanBuku::BORROWER_GURU),
                'nullable',
                SoftDeleteRules::exists('guru'),
            ],
            'tamu_nama' => [
                Rule::requiredIf($type === PeminjamanBuku::BORROWER_TAMU),
                'nullable',
                'string',
                'max:255',
            ],
            'tamu_asal' => ['nullable', 'string', 'max:255'],
            'tamu_telepon' => ['nullable', 'string', 'max:30'],
            'buku_ids' => ['required', 'array', 'min:1'],
            'buku_ids.*' => ['required', 'integer', SoftDeleteRules::exists('buku')],
            'loan_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:loan_date'],
            'catatan_pinjam' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'borrower_type.required' => 'Pilih tipe peminjam.',
            'siswa_id.required' => 'Pilih siswa peminjam.',
            'guru_id.required' => 'Pilih guru peminjam.',
            'tamu_nama.required' => 'Nama tamu wajib diisi.',
            'buku_ids.required' => 'Pilih minimal satu buku.',
            'buku_ids.min' => 'Pilih minimal satu buku.',
            'buku_ids.*.distinct' => 'Buku yang sama tidak boleh dipilih lebih dari sekali.',
            'due_date.after_or_equal' => 'Jatuh tempo tidak boleh sebelum tanggal pinjam.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('borrower_type')) {
            $this->merge(['borrower_type' => PeminjamanBuku::BORROWER_SISWA]);
        }

        if ($this->input('borrower_type') !== PeminjamanBuku::BORROWER_SISWA) {
            $this->merge(['siswa_id' => null]);
        }

        if ($this->input('borrower_type') !== PeminjamanBuku::BORROWER_GURU) {
            $this->merge(['guru_id' => null]);
        }

        if ($this->input('borrower_type') !== PeminjamanBuku::BORROWER_TAMU) {
            $this->merge([
                'tamu_nama' => null,
                'tamu_asal' => null,
                'tamu_telepon' => null,
            ]);
        }

        $bukuIds = $this->input('buku_ids');
        if (! is_array($bukuIds) || $bukuIds === []) {
            if ($this->filled('buku_id')) {
                $bukuIds = [$this->input('buku_id')];
            } else {
                $bukuIds = [];
            }
        }

        $this->merge([
            'buku_ids' => collect($bukuIds)
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all(),
        ]);
    }
}
