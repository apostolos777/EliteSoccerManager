<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlayerDocument extends Model
{
    protected $fillable = [
        'player_id',
        'document_type',
        'filename',
        'file_path',
        'mime_type',
        'file_size',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the player that owns this document.
     */
    public function player()
    {
        return $this->belongsTo(Player::class);
    }

    /**
     * Get document type label.
     */
    public function getTypeLabel()
    {
        $labels = [
            'id' => 'ID/License',
            'passport' => 'Passport',
            'birth_certificate' => 'Birth Certificate',
        ];

        return $labels[$this->document_type] ?? ucfirst(str_replace('_', ' ', $this->document_type));
    }

    /**
     * Delete the physical file from storage.
     */
    public function deleteFile()
    {
        if (!$this->file_path) return;

        // stored file_path may include a leading "storage/" prefix when using public disk
        $path = preg_replace('#^storage/#', '', $this->file_path);

        try {
            if (\Storage::disk('public')->exists($path)) {
                \Storage::disk('public')->delete($path);
                return;
            }
        } catch (\Exception $e) {
            // fallback
        }

        if (\Storage::exists($this->file_path)) {
            \Storage::delete($this->file_path);
        }
    }

    /**
     * Get the download URL for the document.
     */
    public function getDownloadUrl()
    {
        return route('player.documents.download', $this->id);
    }
}
