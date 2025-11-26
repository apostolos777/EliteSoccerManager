<?php
/**
 * Import Players from Spreadsheet Data
 * This script will import player data from CSV or pasted spreadsheet data
 */

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

echo "🏆 VIVO United Player Import Tool\n";
echo "==================================\n\n";

// Function to clean and normalize data
function cleanData($value) {
    return trim(str_replace(['"', "'", '\r', '\n'], '', $value));
}

// Function to find team ID by name (create if doesn't exist)
function getOrCreateTeam($db, $teamName) {
    if (empty($teamName)) return null;
    
    $teamName = cleanData($teamName);
    
    // Check if team exists
    $stmt = $db->prepare("SELECT id FROM teams WHERE name = ?");
    $stmt->execute([$teamName]);
    $result = $stmt->fetch();
    
    if ($result) {
        return $result['id'];
    }
    
    // Create new team
    $stmt = $db->prepare("INSERT INTO teams (name, description) VALUES (?, ?)");
    $stmt->execute([$teamName, "Auto-created from player import"]);
    return $db->lastInsertId();
}

// Function to parse age from various formats
function parseAge($ageValue) {
    if (empty($ageValue)) return null;
    
    // If it's already a number
    if (is_numeric($ageValue)) {
        return (int)$ageValue;
    }
    
    // If it's a date, calculate age
    $ageValue = cleanData($ageValue);

    if (is_numeric($ageValue) && $ageValue > 1000) {
        // Excel date format - convert to actual date
        $excelEpoch = new DateTime('1900-01-01');
        $excelEpoch->add(new DateInterval('P' . ((int)$ageValue - 2) . 'D')); // -2 for Excel leap year bug
        $today = new DateTime();
        $age = $today->diff($excelEpoch)->y;
        return $age;
    }
    
    // Try to parse as date (various formats)
    $formats = ['Y-m-d', 'm/d/Y', 'd/m/Y', 'Y/m/d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $ageValue);
        if ($date !== false) {
            $today = new DateTime();
            $age = $today->diff($date)->y;
            return $age;
        }
    }
    
    // Extract number from string
    preg_match('/(\d+)/', $ageValue, $matches);
    return isset($matches[1]) ? (int)$matches[1] : null;
}

// Sample data structure - replace this with your actual data
// You can paste your spreadsheet data here as an array
$sampleData = [
    // Example format - replace with your actual data
    // ['Name', 'Position', 'Team', 'Jersey Number', 'Age', 'Contact', 'Notes'],
    // ['John Smith', 'Forward', 'Seniors', '10', '25', 'john@email.com', 'Captain'],
    // Add more rows here...
];

echo "📋 Available import methods:\n";
echo "1. CSV file import\n";
echo "2. Direct data array (edit script)\n";
echo "3. Manual data entry\n\n";

// Method 1: Check for CSV file
$csvFile = __DIR__ . '/players_import.csv';
if (file_exists($csvFile)) {
    echo "📄 Found CSV file: players_import.csv\n";
    echo "Processing CSV data...\n\n";
    
    $handle = fopen($csvFile, 'r');
    $headers = fgetcsv($handle); // Get headers
    $players = [];
    
    echo "📝 CSV Headers detected:\n";
    foreach ($headers as $index => $header) {
        echo "  Column $index: " . trim($header) . "\n";
    }
    echo "\n";
    
    while (($data = fgetcsv($handle)) !== false) {
        if (count($data) > 1) { // Skip empty rows
            $players[] = $data;
        }
    }
    fclose($handle);
    
} else {
    echo "❌ No CSV file found (players_import.csv)\n";
    echo "💡 Instructions:\n";
    echo "   1. Export your Google Sheet as CSV\n";
    echo "   2. Save it as 'players_import.csv' in this directory\n";
    echo "   3. Run this script again\n\n";
    
    echo "🔄 Or copy and paste your data below:\n";
    echo "   Edit this script and replace the \$sampleData array\n";
    echo "   with your actual player data.\n\n";
    
    echo "📊 Expected columns (in any order):\n";
    echo "   - Name (required)\n";
    echo "   - Position (e.g., Forward, Midfielder, Defender, Goalkeeper)\n";
    echo "   - Team (e.g., Seniors, Juniors, U16, etc.)\n";
    echo "   - Jersey Number\n";
    echo "   - Age or Date of Birth\n";
    echo "   - Contact/Email (optional)\n";
    echo "   - Notes (optional)\n\n";
    
    // Use sample data if no CSV
    $players = $sampleData;
}

if (empty($players)) {
    echo "⚠️  No player data found to import.\n";
    echo "Please add data using one of the methods above.\n";
    exit;
}

echo "🚀 Starting import process...\n";
echo "Found " . count($players) . " players to import.\n\n";

// Import players
$imported = 0;
$errors = 0;

// If we have CSV headers, try to auto-map columns
$columnMap = [];
if (isset($headers)) {
    foreach ($headers as $index => $header) {
        $header = strtolower(trim($header));
        if (strpos($header, 'full name') !== false) {
            $columnMap['name'] = $index;
        } elseif (strpos($header, 'surname') !== false) {
            $columnMap['surname'] = $index;
        } elseif (strpos($header, 'first name') !== false) {
            $columnMap['name'] = $index;
        } elseif (strpos($header, 'position') !== false) {
            $columnMap['position'] = $index;
        } elseif (strpos($header, 'team') !== false) {
            $columnMap['team'] = $index;
        } elseif (strpos($header, 'age group') !== false) {
            $columnMap['age_group'] = $index;
        } elseif (strpos($header, 'jersey') !== false || strpos($header, 'number') !== false) {
            $columnMap['jersey'] = $index;
        } elseif (strpos($header, 'date of birth') !== false || strpos($header, 'birth') !== false) {
            $columnMap['age'] = $index;
        } elseif (strpos($header, 'unique player id') !== false || strpos($header, 'player id') !== false) {
            $columnMap['unique_id'] = $index;
        } elseif (strpos($header, 'contact') !== false || strpos($header, 'email') !== false) {
            $columnMap['contact'] = $index;
        }
    }
    
    echo "🗺️  Column mapping:\n";
    foreach ($columnMap as $field => $index) {
        echo "  $field -> Column $index (" . $headers[$index] . ")\n";
    }
    echo "\n";
}

foreach ($players as $rowIndex => $playerData) {
    try {
        // Extract data based on column mapping or assume order
        if (!empty($columnMap)) {
            $fullName = isset($columnMap['name']) ? cleanData($playerData[$columnMap['name']]) : '';
            $surname = isset($columnMap['surname']) ? cleanData($playerData[$columnMap['surname']]) : '';
            
            // Extract first name from full name by removing surname
            $firstName = $fullName;
            if (!empty($surname) && !empty($fullName)) {
                // Remove surname from full name to get first name
                $firstName = trim(str_replace($surname, '', $fullName));
                // If that leaves nothing, use the full name as first name
                if (empty($firstName)) {
                    $firstName = $fullName;
                }
            }
            
            $position = isset($columnMap['position']) ? cleanData($playerData[$columnMap['position']]) : '';
            $teamName = isset($columnMap['team']) ? cleanData($playerData[$columnMap['team']]) : '';
            $ageGroup = isset($columnMap['age_group']) ? cleanData($playerData[$columnMap['age_group']]) : '';
            $jerseyNumber = isset($columnMap['jersey']) ? cleanData($playerData[$columnMap['jersey']]) : '';
            $age = isset($columnMap['age']) ? parseAge($playerData[$columnMap['age']]) : null;
            $uniqueId = isset($columnMap['unique_id']) ? cleanData($playerData[$columnMap['unique_id']]) : '';
        } else {
            // Assume standard order: Full Name, Surname, Date Of Birth, Age Group, Jersey, Position, Unique ID
            $fullName = isset($playerData[0]) ? cleanData($playerData[0]) : '';
            $surname = isset($playerData[1]) ? cleanData($playerData[1]) : '';
            
            // Extract first name from full name by removing surname
            $firstName = $fullName;
            if (!empty($surname) && !empty($fullName)) {
                $firstName = trim(str_replace($surname, '', $fullName));
                if (empty($firstName)) {
                    $firstName = $fullName;
                }
            }
            $age = isset($playerData[2]) ? parseAge($playerData[2]) : null;
            $ageGroup = isset($playerData[3]) ? cleanData($playerData[3]) : '';
            $jerseyNumber = isset($playerData[4]) ? cleanData($playerData[4]) : '';
            $position = isset($playerData[5]) ? cleanData($playerData[5]) : '';
            $uniqueId = isset($playerData[6]) ? cleanData($playerData[6]) : '';
        }
        
        // Combine first name and surname properly
        $fullName = trim($firstName . ' ' . $surname);
        if (empty($fullName)) {
            continue;
        }
        
        // Use age group as team name if available, otherwise use generic team
        $teamName = !empty($ageGroup) ? strtoupper($ageGroup) : 'General';
        
        // Convert age group to more readable format
        $teamDisplayName = $teamName;
        if (preg_match('/u(\d+)/', strtolower($teamName), $matches)) {
            $teamDisplayName = 'Under ' . $matches[1];
        } elseif (stripos($teamName, 'first') !== false) {
            $teamDisplayName = 'First Team';
        } elseif (stripos($teamName, 'senior') !== false) {
            $teamDisplayName = 'Seniors';
        }
        
        // Get or create team
        $teamId = getOrCreateTeam($db, $teamDisplayName);
        
        // Clean jersey number
        $jerseyNumber = is_numeric($jerseyNumber) ? (int)$jerseyNumber : null;
        
            // Introspect player table columns for adaptive insert
            static $presentCols = null;
            if ($presentCols === null) {
                $colsInfo = $db->query("PRAGMA table_info(players)")->fetchAll(PDO::FETCH_ASSOC);
                $presentCols = [];
                foreach ($colsInfo as $ci) { $presentCols[$ci['name']] = true; }
            }

            $hasFirst = isset($presentCols['first_name']);
            $hasLast  = isset($presentCols['last_name']);
            $hasName  = isset($presentCols['name']);
            $hasStatus= isset($presentCols['status']);
            $hasIsActive = isset($presentCols['is_active']);
            $hasAge   = isset($presentCols['age']);
            $hasJersey= isset($presentCols['jersey_number']);
            $hasTeam  = isset($presentCols['team_id']);
            $hasPos   = isset($presentCols['position']);

            // Build duplicate check dynamically
            if ($hasFirst || $hasLast) {
                $dupSql = "SELECT id FROM players WHERE ";
                $conds = [];$params=[];
                if ($hasFirst) { $conds[]='first_name = ?'; $params[]=$firstName; }
                if ($hasLast) { $conds[]='last_name = ?'; $params[]=$surname; }
                if ($hasTeam) { $conds[]='team_id = ?'; $params[]=$teamId; }
                $dupSql .= implode(' AND ', $conds) . ' LIMIT 1';
                $stmt = $db->prepare($dupSql); $stmt->execute($params);
                if ($stmt->fetch()) {
                    echo "⚠️  Player '$firstName $surname' already exists in team '$teamDisplayName'\n"; continue;
                }
            } elseif ($hasName) {
                $stmt = $db->prepare("SELECT id FROM players WHERE name = ? AND team_id = ? LIMIT 1");
                $stmt->execute([$firstName, $teamId]);
                if ($stmt->fetch()) { echo "⚠️  Player '$firstName' already exists in team '$teamDisplayName'\n"; continue; }
            }

            // Prepare insert data
            $data = [];
            if ($hasFirst) $data['first_name'] = $firstName;
            if ($hasLast)  $data['last_name']  = $surname;
            if ($hasName) $data['name'] = trim($firstName . ' ' . $surname);
            if ($hasPos)   $data['position'] = $position;
            if ($hasTeam)  $data['team_id'] = $teamId;
            if ($hasJersey) $data['jersey_number'] = $jerseyNumber;
            if ($hasAge)   $data['age'] = $age;
            if ($hasStatus) $data['status'] = 'active';
            if ($hasIsActive && !$hasStatus) $data['is_active'] = 1;

            $cols = array_keys($data);
            $placeholders = array_map(fn($c)=>':'.$c, $cols);
            $sqlInsert = 'INSERT INTO players (' . implode(', ',$cols) . ') VALUES (' . implode(', ',$placeholders) . ')';
            $stmt = $db->prepare($sqlInsert);
            $exec = [];
            foreach ($data as $k=>$v) { $exec[':'.$k] = $v; }
            $stmt->execute($exec);
        
        $imported++;
        echo "✅ Imported: $firstName $surname ($position) - Team: $teamDisplayName - Jersey: $jerseyNumber - Age: $age\n";
        
    } catch (Exception $e) {
        $errors++;
        $playerName = isset($playerData[0]) ? $playerData[0] : "Row " . ($rowIndex + 1);
        echo "❌ Error importing $playerName: " . $e->getMessage() . "\n";
    }
}

echo "\n🎯 Import Summary:\n";
echo "================\n";
echo "✅ Successfully imported: $imported players\n";
echo "❌ Errors: $errors\n";

if ($imported > 0) {
    echo "\n📊 Updated team counts:\n";
    $result = $db->query("
        SELECT t.name, COUNT(p.id) as player_count
        FROM teams t
        LEFT JOIN players p ON t.id = p.team_id
        WHERE p.id IS NOT NULL
        GROUP BY t.id, t.name
        ORDER BY player_count DESC
    ");
    
    while ($row = $result->fetch()) {
        echo "  {$row['name']}: {$row['player_count']} players\n";
    }
    
    echo "\n📋 Sample players imported:\n";
    $result = $db->query("
        SELECT CONCAT(name, ' ', surname) as full_name, 
               (SELECT t.name FROM teams t WHERE t.id = p.team_id) as team_name
        FROM players p 
        WHERE p.id > 15 
        ORDER BY p.id 
        LIMIT 10
    ");
    
    while ($row = $result->fetch()) {
        echo "  - {$row['full_name']} ({$row['team_name']})\n";
    }
}

echo "\n🚀 Import completed!\n";
?>
