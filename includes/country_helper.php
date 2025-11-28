<?php
/**
 * Country helper — small utility for mapping nationality text to ISO2 codes and flag emojis.
 * This is intentionally lightweight: we provide a best-effort fuzzy match for common names.
 */

function vivo_get_iso_from_nationality(string $name): ?string {
    $n = trim(strtolower($name));
    if ($n === '') return null;

    // If user entered a 2-letter ISO code already, accept it
    if (preg_match('/^[a-z]{2}$/i', $n)) {
        return strtoupper($n);
    }

    // Common name -> ISO2 map (not exhaustive but covers typical usage)
    static $map = null;
    if ($map === null) {
        $map = [
            'south africa' => 'ZA', 'sa' => 'ZA', 'rsa' => 'ZA',
            'united states' => 'US', 'us' => 'US', 'usa' => 'US', 'united states of america' => 'US',
            'united kingdom' => 'GB', 'uk' => 'GB', 'great britain' => 'GB', 'britain' => 'GB',
            'england' => 'GB', 'scotland' => 'GB', 'wales' => 'GB', 'northern ireland' => 'GB',
            'australia' => 'AU', 'new zealand' => 'NZ', 'canada' => 'CA', 'ireland' => 'IE',
            'germany' => 'DE', 'france' => 'FR', 'spain' => 'ES', 'portugal' => 'PT',
            'netherlands' => 'NL', 'belgium' => 'BE', 'italy' => 'IT', 'switzerland' => 'CH',
            'brazil' => 'BR', 'argentina' => 'AR', 'mexico' => 'MX', 'chile' => 'CL',
            'colombia' => 'CO', 'uruguay' => 'UY', 'ghana' => 'GH', 'nigeria' => 'NG',
            'kenya' => 'KE', 'zimbabwe' => 'ZW', 'botswana' => 'BW', 'uganda' => 'UG',
            'india' => 'IN', 'pakistan' => 'PK', 'bangladesh' => 'BD', 'sri lanka' => 'LK',
            'philippines' => 'PH', 'japan' => 'JP', 'south korea' => 'KR', 'korea' => 'KR',
            'china' => 'CN', 'taiwan' => 'TW', 'singapore' => 'SG', 'vietnam' => 'VN',
            'sweden' => 'SE', 'norway' => 'NO', 'denmark' => 'DK', 'finland' => 'FI',
            'poland' => 'PL', 'czech republic' => 'CZ', 'russia' => 'RU', 'ukraine' => 'UA',
            'turkey' => 'TR', 'egypt' => 'EG', 'morocco' => 'MA', 'algeria' => 'DZ',
            'tunisia' => 'TN'
        ];
    }

    // exact match
    if (isset($map[$n])) return $map[$n];

    // try crude substring matching
    foreach ($map as $k => $code) {
        if (strpos($n, $k) !== false) return $code;
    }

    // Last resort: try to extract 2-letter code inside parentheses e.g. (ZA)
    if (preg_match('/\(([A-Za-z]{2})\)/', $name, $m)) return strtoupper($m[1]);

    return null;
}

function vivo_flag_emoji_from_iso(string $iso): string {
    $iso = strtoupper(trim($iso));
    if (!preg_match('/^[A-Z]{2}$/', $iso)) return '';
    $first = ord($iso[0]) - ord('A') + 0x1F1E6;
    $second = ord($iso[1]) - ord('A') + 0x1F1E6;
    return mb_chr($first, 'UTF-8') . mb_chr($second, 'UTF-8');
}

function vivo_get_flag_for_nationality(string $name): string {
    $code = vivo_get_iso_from_nationality($name);
    if (!$code) return '';
    return vivo_flag_emoji_from_iso($code);
}
