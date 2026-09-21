<?php

/**
 * Konfigurasi forward pushNotif → MARKAZ_AL_AZIZ.
 *
 * vano_prefix (6 digit) harus sama dengan QRIS_VANO_PREFIX di .env Markaz.
 * push_notif_url harus bisa dijangkau dari server Islamic Center (bukan localhost
 * kecuali IC dan Markaz di mesin yang sama).
 */
$markazQrisConfig = [
    'push_notif_url' => 'http://localhost:8000/api/finance/qris/push-notif',
    'vano_prefix' => '880088',
    'timeout' => 20,
];
