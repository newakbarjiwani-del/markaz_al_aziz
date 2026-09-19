<?php

use App\Support\ConfirmDetail;
use Illuminate\Support\HtmlString;

it('encodes detail rows as HTML-escaped JSON for attributes', function () {
    $html = ConfirmDetail::attr([
        ['label' => 'Siswa', 'value' => 'Rina "Hidayat"'],
        ['label' => 'NIS', 'value' => '3000086'],
    ]);

    expect($html)->toBeInstanceOf(HtmlString::class);

    $value = (string) $html;
    expect($value)->not->toContain('JSON.parse');
    expect($value)->toContain('&quot;label&quot;');
    expect(html_entity_decode($value, ENT_QUOTES, 'UTF-8'))
        ->toBe('[{"label":"Siswa","value":"Rina \\"Hidayat\\""},{"label":"NIS","value":"3000086"}]');
});

it('passes plain string detail through HTML escaping', function () {
    expect((string) ConfirmDetail::attr('Hanya teks <b>biasa</b>'))
        ->toBe('Hanya teks &lt;b&gt;biasa&lt;/b&gt;');
});
