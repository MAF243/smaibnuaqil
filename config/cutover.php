<?php

return [
    'ppdb' => [
        'read_from_v3' => env('CUTOVER_PPDB_READ_FROM_V3', true),
        'write_to_v3' => env('CUTOVER_PPDB_WRITE_TO_V3', true),
        // Stage 3: legacy mirror PPDB dimatikan secara default setelah data nyata stabil.
        // Aktifkan kembali sementara bila butuh rollback/read legacy: CUTOVER_PPDB_MIRROR_LEGACY=true
        'mirror_legacy' => env('CUTOVER_PPDB_MIRROR_LEGACY', false),
    ],
    'cms' => [
        'read_from_v3' => env('CUTOVER_CMS_READ_FROM_V3', true),
        'write_to_v3' => env('CUTOVER_CMS_WRITE_TO_V3', true),
        'mirror_legacy' => env('CUTOVER_CMS_MIRROR_LEGACY', false),
    ],
    'audit' => [
        'primary_v3' => env('CUTOVER_AUDIT_PRIMARY_V3', true),
        'mirror_legacy' => env('CUTOVER_AUDIT_MIRROR_LEGACY', false),
    ],
];
