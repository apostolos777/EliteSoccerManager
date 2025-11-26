<?php
// google_calendar_helper.php
// Helper for Google Calendar API integration (OAuth2 + event creation)

// CONFIG: Set these with your Google API Console credentials
const GCAL_CLIENT_ID = '121290603087-9boco3ib28kvbs8jbpo47bdcecdidjb0.apps.googleusercontent.com';
const GCAL_CLIENT_SECRET = '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';

// Determine redirect URI based on environment
function getRedirectUri() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $script = $_SERVER['SCRIPT_NAME'] ?? '/add_event.php';
    
    // For production
    if (strpos($host, 'vivounited.org') !== false) {
        return 'https://www.vivounited.org/vivoapp/add_event.php?gcal_auth=1';
    }
    
    // For development/local
    return $protocol . '://' . $host . '/add_event.php?gcal_auth=1';
}

const GCAL_SCOPES = 'https://www.googleapis.com/auth/calendar.events';

function getGoogleAuthUrl() {
    $params = [
        'client_id' => GCAL_CLIENT_ID,
        'redirect_uri' => getRedirectUri(),
        'response_type' => 'code',
        'scope' => GCAL_SCOPES,
        'access_type' => 'offline',
        'prompt' => 'consent',
    ];
    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

function exchangeCodeForToken($code) {
    $post = [
        'code' => $code,
        'client_id' => GCAL_CLIENT_ID,
        'client_secret' => GCAL_CLIENT_SECRET,
        'redirect_uri' => getRedirectUri(),
        'grant_type' => 'authorization_code',
    ];
    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function createGoogleCalendarEvent($access_token, $calendarId, $eventData) {
    $ch = curl_init("https://www.googleapis.com/calendar/v3/calendars/" . urlencode($calendarId) . "/events");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $access_token,
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($eventData));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

// You will need to store and refresh tokens per user/session for production use.
