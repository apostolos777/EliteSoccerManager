<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\PlayerDocument;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class PlayerController extends Controller
{
    // Football positions
    const FOOTBALL_POSITIONS = [
        'GK' => 'Goalkeeper',
        'RB' => 'Right Back',
        'LB' => 'Left Back',
        'CB' => 'Centre Back',
        'RWB' => 'Right Wing Back',
        'LWB' => 'Left Wing Back',
        'CDM' => 'Central Defensive Midfielder',
        'CM' => 'Central Midfielder',
        'CAM' => 'Central Attacking Midfielder',
        'RM' => 'Right Midfielder',
        'LM' => 'Left Midfielder',
        'RW' => 'Right Winger',
        'LW' => 'Left Winger',
        'CF' => 'Centre Forward',
        'SS' => 'Second Striker',
        'ST' => 'Striker',
    ];

    /**
     * Show the player profile edit form.
     */
    public function editProfile($id)
    {
        $player = Player::with('teams', 'documents')->findOrFail($id);
        $teams = Team::all();
        $positions = self::FOOTBALL_POSITIONS;
        $countries = $this->getCountries();
        $selectedTeams = $player->getTeamIds();

        return view('player.edit_profile', compact(
            'player',
            'teams',
            'positions',
            'countries',
            'selectedTeams'
        ));
    }

    /**
     * Update player profile information.
     */
    public function updateProfile(Request $request, $id)
    {
        $player = Player::findOrFail($id);

        $validated = $this->validatePlayerProfile($request);

        // Update basic player information
        $player->update($validated);

        // Update multiple teams
        if ($request->has('teams')) {
            $teamIds = array_filter((array) $request->input('teams'));
            $player->setTeams($teamIds);
        }

        return response()->json([
            'success' => true,
            'message' => 'Player profile updated successfully.',
            'player' => $player->load('teams'),
        ]);
    }

    /**
     * Upload and replace player profile picture.
     */
    public function uploadProfilePicture(Request $request, $id)
    {
        $player = Player::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120', // 5MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Delete old profile picture
            $player->deleteOldProfilePicture();

            // Store new picture
            $file = $request->file('profile_picture');
            $filename = 'player_' . $player->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('profiles', $filename, 'public');

            // Update player record
            $player->update(['profile_picture' => 'storage/' . $path]);

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'profile_picture_url' => asset($player->profile_picture),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading profile picture: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Upload player documents (ID, Passport, Birth Certificate).
     */
    public function uploadDocument(Request $request, $id)
    {
        $player = Player::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'document_type' => 'required|in:id,passport,birth_certificate',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:10240', // 10MB
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $file = $request->file('document_file');
            $documentType = $request->input('document_type');

            // Delete existing document of same type if it exists
            $existingDoc = $player->getDocumentByType($documentType);
            if ($existingDoc) {
                $existingDoc->deleteFile();
                $existingDoc->delete();
            }

            // Store new document
            $filename = 'player_' . $player->id . '_' . $documentType . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('documents', $filename, 'public');

            // Create document record
            $document = PlayerDocument::create([
                'player_id' => $player->id,
                'document_type' => $documentType,
                'filename' => $filename,
                'file_path' => 'storage/' . $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document uploaded successfully.',
                'document' => $document,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error uploading document: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a player document.
     */
    public function deleteDocument($playerId, $documentId)
    {
        $player = Player::findOrFail($playerId);
        $document = PlayerDocument::where('id', $documentId)
                                   ->where('player_id', $playerId)
                                   ->firstOrFail();

        $document->deleteFile();
        $document->delete();

        return response()->json([
            'success' => true,
            'message' => 'Document deleted successfully.',
        ]);
    }

    /**
     * Download a player document.
     */
    public function downloadDocument($playerId, $documentId)
    {
        $player = Player::findOrFail($playerId);
        $document = PlayerDocument::where('id', $documentId)
                                   ->where('player_id', $playerId)
                                   ->firstOrFail();

        $filePath = str_replace('storage/', '', $document->file_path);

        if (!Storage::exists($filePath)) {
            abort(404, 'Document not found.');
        }

        return Storage::download($filePath, $document->filename);
    }

    /**
     * Get all documents for a player.
     */
    public function getDocuments($id)
    {
        $player = Player::findOrFail($id);
        $documents = $player->documents()->get()->map(function ($doc) {
            return [
                'id' => $doc->id,
                'type' => $doc->document_type,
                'type_label' => $doc->getTypeLabel(),
                'filename' => $doc->filename,
                'file_size' => $this->formatFileSize($doc->file_size),
                'uploaded_at' => $doc->created_at->format('Y-m-d H:i:s'),
                'download_url' => $doc->getDownloadUrl(),
            ];
        });

        return response()->json([
            'success' => true,
            'documents' => $documents,
        ]);
    }

    /**
     * Validate player profile data.
     */
    private function validatePlayerProfile(Request $request)
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'date_of_birth' => 'nullable|date',
            'nationality' => 'nullable|string|max:100',
            'position' => 'nullable|string|max:100',
            'primary_position' => 'nullable|in:' . implode(',', array_keys(self::FOOTBALL_POSITIONS)),
            'secondary_position' => 'nullable|in:' . implode(',', array_keys(self::FOOTBALL_POSITIONS)),
            'third_position' => 'nullable|in:' . implode(',', array_keys(self::FOOTBALL_POSITIONS)),
            'jersey_number' => 'nullable|integer|between:1,99',
            'height' => 'nullable|string|max:50',
            'weight' => 'nullable|string|max:50',
            'school' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'medical_notes' => 'nullable|string|max:1000',
            'parent_name' => 'nullable|string|max:255',
            'parent_email' => 'nullable|email',
            'parent_phone' => 'nullable|string|max:20',
        ]);
    }

    /**
     * Get list of countries with flag emojis.
     */
    private function getCountries()
    {
        return [
            'ZA' => ['name' => 'South Africa', 'flag' => '🇿🇦'],
            'UK' => ['name' => 'United Kingdom', 'flag' => '🇬🇧'],
            'US' => ['name' => 'United States', 'flag' => '🇺🇸'],
            'AU' => ['name' => 'Australia', 'flag' => '🇦🇺'],
            'CA' => ['name' => 'Canada', 'flag' => '🇨🇦'],
            'NZ' => ['name' => 'New Zealand', 'flag' => '🇳🇿'],
            'IN' => ['name' => 'India', 'flag' => '🇮🇳'],
            'KE' => ['name' => 'Kenya', 'flag' => '🇰🇪'],
            'NG' => ['name' => 'Nigeria', 'flag' => '🇳🇬'],
            'EG' => ['name' => 'Egypt', 'flag' => '🇪🇬'],
            'FR' => ['name' => 'France', 'flag' => '🇫🇷'],
            'DE' => ['name' => 'Germany', 'flag' => '🇩🇪'],
            'ES' => ['name' => 'Spain', 'flag' => '🇪🇸'],
            'IT' => ['name' => 'Italy', 'flag' => '🇮🇹'],
            'BR' => ['name' => 'Brazil', 'flag' => '🇧🇷'],
            'AR' => ['name' => 'Argentina', 'flag' => '🇦🇷'],
            'MX' => ['name' => 'Mexico', 'flag' => '🇲🇽'],
            'JP' => ['name' => 'Japan', 'flag' => '🇯🇵'],
            'CN' => ['name' => 'China', 'flag' => '🇨🇳'],
        ];
    }

    /**
     * Format file size in human readable format.
     */
    private function formatFileSize($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
