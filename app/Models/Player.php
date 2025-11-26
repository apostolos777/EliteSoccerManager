<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Player extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'date_of_birth',
        'nationality',
        'profile_picture',
        'position',
        'primary_position',
        'secondary_position',
        'third_position',
        'jersey_number',
        'height',
        'weight',
        'school',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_notes',
        'parent_name',
        'parent_email',
        'parent_phone',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all teams this player belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'player_teams', 'player_id', 'team_id')
                    ->withTimestamps();
    }

    /**
     * Get all documents for this player.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(PlayerDocument::class);
    }

    /**
     * Get the primary team (for backward compatibility).
     */
    public function team()
    {
        return $this->teams()->first();
    }

    /**
     * Get all team IDs.
     */
    public function getTeamIds()
    {
        return $this->teams()->pluck('team_id')->toArray();
    }

    /**
     * Set multiple teams for the player.
     */
    public function setTeams(array $teamIds)
    {
        $this->teams()->sync($teamIds);
    }

    /**
     * Get array of position names.
     */
    public function getPositions()
    {
        return array_filter([
            $this->primary_position,
            $this->secondary_position,
            $this->third_position,
        ]);
    }

    /**
     * Check if player has a specific position.
     */
    public function hasPosition($position)
    {
        return in_array($position, $this->getPositions());
    }

    /**
     * Get document by type.
     */
    public function getDocumentByType($type)
    {
        return $this->documents()->where('document_type', $type)->first();
    }

    /**
     * Delete old profile picture if it exists.
     */
    public function deleteOldProfilePicture()
    {
        if (!$this->profile_picture) return;

        // Normalize stored value like "storage/profiles/filename.jpg" -> "profiles/filename.jpg"
        $path = preg_replace('#^storage/#', '', $this->profile_picture);

        // Remove file from public disk if it exists there
        try {
            if (\Storage::disk('public')->exists($path)) {
                \Storage::disk('public')->delete($path);
            }
        } catch (\Exception $e) {
            // fallback: attempt to delete using default storage
            if (\Storage::exists($this->profile_picture)) {
                \Storage::delete($this->profile_picture);
            }
        }
    }
}
