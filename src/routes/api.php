<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NodeController;

Route::prefix('v1')->group(function () {
    Route::post('/nodes', [NodeController::class, 'store']); // crear
    Route::get('/nodes/roots', [NodeController::class, 'index']); // listar raíces
    Route::get('/nodes/{parent}/children', [NodeController::class, 'listChildren']); // listar hijos (depth optional ?depth=2)
    Route::delete('/nodes/{id}', [NodeController::class, 'destroy']); // eliminar
});
