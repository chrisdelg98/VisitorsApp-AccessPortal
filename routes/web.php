<?php

use Illuminate\Support\Facades\Route;

// El panel Filament ocupa la raíz — no hay rutas web públicas.

// Proxy autenticado para imágenes almacenadas en el storage privado de la API.
Route::get('/portal-image/{path}', function (string $path) {
    abort_unless(auth()->check(), 403);

    $root = rtrim((string) config('filesystems.disks.api_images.root', storage_path('app/private')), '/\\');
    $file = $root . DIRECTORY_SEPARATOR . ltrim(str_replace(['..', "\0"], '', $path), '/\\');

    // Evitar path traversal
    $real = realpath($file);
    $base = realpath($root);
    abort_if(!$real || !$base || !str_starts_with($real, $base . DIRECTORY_SEPARATOR), 404);
    abort_unless(file_exists($real), 404);

    return response()->file($real, [
        'Content-Type'  => mime_content_type($real),
        'Cache-Control' => 'private, max-age=3600',
    ]);
})->where('path', '.*')->name('portal.image')->middleware('web');
