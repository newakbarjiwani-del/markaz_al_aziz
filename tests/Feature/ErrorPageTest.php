<?php

test('404 page shows branded indonesian message', function () {
    $response = $this->get('/halaman-tidak-ada-untuk-test');

    $response->assertNotFound()
        ->assertSee('Halaman tidak ditemukan')
        ->assertSee('Ke Beranda')
        ->assertSee('Login');
});
