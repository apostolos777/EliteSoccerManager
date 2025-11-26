<?php

use App\Http\Controllers\PlayerController;
use Illuminate\Support\Facades\Route;

// Player profile routes
Route::middleware(['auth'])->group(function () {
    // Profile view and update
    Route::get('/player/{id}/edit-profile', [PlayerController::class, 'editProfile'])->name('player.edit.profile');
    Route::post('/player/{id}/update-profile', [PlayerController::class, 'updateProfile'])->name('player.update.profile');

    // Profile picture upload
    Route::post('/player/{id}/upload-profile-picture', [PlayerController::class, 'uploadProfilePicture'])->name('player.upload.profile_picture');

    // Document operations
    Route::post('/player/{id}/upload-document', [PlayerController::class, 'uploadDocument'])->name('player.upload.document');
    Route::delete('/player/{playerId}/document/{documentId}', [PlayerController::class, 'deleteDocument'])->name('player.delete.document');
    Route::get('/player/{playerId}/document/{documentId}/download', [PlayerController::class, 'downloadDocument'])->name('player.documents.download');
    Route::get('/player/{id}/documents', [PlayerController::class, 'getDocuments'])->name('player.get.documents');
});
