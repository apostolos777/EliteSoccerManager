<?php
/**
 * VIVO United Player Barcode Generator
 * Generates unique barcodes for players using DOB-Initials-5 Digit Add On format
 * Format: 6 Digits (DDMMYY) + 2/3/4 Letters (Initials) + 5 Extra Digits
 */

class PlayerBarcodeGenerator {
    
    /**
     * Generate a unique barcode for a player
     * @param array $player Player data including name, date_of_birth, id
     * @return string Generated barcode
     */
    public static function generateBarcode($player) {
        $barcode = '';
        
        // Part 1: DOB - 6 digits (DDMMYY format)
        $dob_part = self::formatDOB($player['date_of_birth']);
        $barcode .= $dob_part;
        
        // Part 2: Initials - 2/3/4 letters
        $initials_part = self::extractInitials($player['name']);
        $barcode .= $initials_part;
        
        // Part 3: 5 Extra Digits (based on player ID and additional logic)
        $extra_digits = self::generateExtraDigits($player);
        $barcode .= $extra_digits;
        
        return strtoupper($barcode);
    }
    
    /**
     * Format date of birth to DDMMYY
     * @param string $date_of_birth
     * @return string 6-digit date string
     */
    private static function formatDOB($date_of_birth) {
        if (!$date_of_birth) {
            // Default to current date if DOB not available
            return date('dmY');
        }
        
        try {
            $date = new DateTime($date_of_birth);
            return $date->format('dmy'); // DDMMYY format
        } catch (Exception $e) {
            // Fallback to current date
            return date('dmy');
        }
    }
    
    /**
     * Extract initials from player name (2-4 letters)
     * @param string $name
     * @return string Initials
     */
    private static function extractInitials($name) {
        if (!$name) {
            return 'UN'; // Unknown
        }
        
        // Split name into words
        $words = explode(' ', trim($name));
        $initials = '';
        
        // Take first letter of each word, maximum 4 letters
        foreach ($words as $word) {
            if (strlen($initials) >= 4) break;
            if (!empty($word)) {
                $initials .= strtoupper(substr($word, 0, 1));
            }
        }
        
        // Ensure minimum 2 letters
        if (strlen($initials) < 2) {
            $initials = str_pad($initials, 2, 'X');
        }
        
        return $initials;
    }
    
    /**
     * Generate 5 extra digits based on player data
     * @param array $player
     * @return string 5-digit string
     */
    private static function generateExtraDigits($player) {
        $digits = '';
        
        // Use player ID (first 2-3 digits)
        $player_id = str_pad($player['id'], 3, '0', STR_PAD_LEFT);
        $digits .= substr($player_id, -3); // Last 3 digits of padded ID
        
        // Add team-based digit if available
        if (isset($player['team_id']) && $player['team_id']) {
            $digits .= substr(str_pad($player['team_id'], 2, '0', STR_PAD_LEFT), -1);
        } else {
            $digits .= '0';
        }
        
        // Add checksum digit
        $checksum = self::calculateChecksum($digits . $player['name']);
        $digits .= $checksum;
        
        return str_pad($digits, 5, '0', STR_PAD_LEFT);
    }
    
    /**
     * Calculate a simple checksum for verification
     * @param string $data
     * @return string Single digit checksum
     */
    private static function calculateChecksum($data) {
        $sum = 0;
        for ($i = 0; $i < strlen($data); $i++) {
            if (is_numeric($data[$i])) {
                $sum += intval($data[$i]);
            } else {
                $sum += ord($data[$i]);
            }
        }
        return substr(strval($sum), -1);
    }
    
    /**
     * Generate barcode as SVG for display
     * @param string $barcode
     * @return string SVG markup
     */
    public static function generateBarcodeSVG($barcode) {
        $width = 250;
        $height = 60;
        $bar_width = ($width - 20) / strlen($barcode);
        
        $svg = '<svg width="' . $width . '" height="' . $height . '" xmlns="http://www.w3.org/2000/svg" style="border: 1px solid #ccc; border-radius: 4px;">';
        $svg .= '<rect width="' . $width . '" height="' . $height . '" fill="white" rx="4"/>';
        
        // Simple barcode representation - alternating black/white bars
        for ($i = 0; $i < strlen($barcode); $i++) {
            $char = $barcode[$i];
            $x = 10 + ($i * $bar_width);
            
            // Determine bar color based on character
            if (is_numeric($char)) {
                $color = ($char % 2 == 0) ? 'black' : 'white';
            } else {
                $color = (ord($char) % 2 == 0) ? 'black' : 'white';
            }
            
            if ($color == 'black') {
                $svg .= '<rect x="' . $x . '" y="5" width="' . $bar_width . '" height="35" fill="black"/>';
            }
        }
        
        // Add text below barcode
        $svg .= '<text x="' . ($width/2) . '" y="52" text-anchor="middle" font-family="monospace" font-size="11" font-weight="bold" fill="black">' . $barcode . '</text>';
        $svg .= '</svg>';
        
        return $svg;
    }
    
    /**
     * Validate barcode format
     * @param string $barcode
     * @return bool
     */
    public static function validateBarcode($barcode) {
        // Check length (6 digits + 2-4 letters + 5 digits = 13-15 characters)
        if (strlen($barcode) < 13 || strlen($barcode) > 15) {
            return false;
        }
        
        // Check if first 6 characters are digits (DOB)
        if (!ctype_digit(substr($barcode, 0, 6))) {
            return false;
        }
        
        // Check if last 5 characters are digits
        if (!ctype_digit(substr($barcode, -5))) {
            return false;
        }
        
        // Check if middle part contains only letters
        $middle_part = substr($barcode, 6, -5);
        if (!ctype_alpha($middle_part)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Parse barcode components
     * @param string $barcode
     * @return array|false Components of the barcode or false if invalid
     */
    public static function parseBarcode($barcode) {
        if (!self::validateBarcode($barcode)) {
            return false;
        }
        
        return [
            'dob_part' => substr($barcode, 0, 6),
            'initials' => substr($barcode, 6, -5),
            'extra_digits' => substr($barcode, -5),
            'full_barcode' => $barcode
        ];
    }
}

/**
 * Legacy QR Code Generator (kept for compatibility)
 */
class QRCodeGenerator {
    
    public static function generateQR($data, $size = 150) {
        $encodedData = urlencode($data);
        return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data={$encodedData}";
    }
    
    public static function generateBarcode($data, $width = 200, $height = 50) {
        // Simple barcode using Code 128
        $encodedData = urlencode($data);
        return "https://api.qrserver.com/v1/create-barcode/?size={$width}x{$height}&data={$encodedData}&type=code128";
    }
}
?>
