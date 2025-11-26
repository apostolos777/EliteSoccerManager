<?php
/**
 * Import Players Script - 2025
 * Deletes all existing players and imports new player data with age group calculation
 */

require_once 'database_factory.php';

$db = DatabaseFactory::getConnection();

echo "=== VIVO United Player Import Script ===\n\n";

// Step 1: Delete all existing players
echo "Step 1: Deleting all existing players...\n";
try {
    // Disable foreign key checks temporarily
    $db->exec("PRAGMA foreign_keys = OFF");
    $stmt = $db->exec("DELETE FROM players");
    $db->exec("PRAGMA foreign_keys = ON");
    echo "✓ All players deleted successfully\n\n";
} catch (Exception $e) {
    die("Error deleting players: " . $e->getMessage() . "\n");
}

// Step 2: Define player data
$players = [
    ['Gabriele', 'Gioia', '2019-10-02'],
    ['Will', 'Dignum', '2019-06-20'],
    ['Reece', 'Labuschagne', '2019-02-25'],
    ['Ronan Alexander', 'Glöss', '2019-02-09'],
    ['Matteo Joshua', 'Ferreira', '2019-11-03'],
    ['Liam Jeffrey', 'Brummer', '2018-05-12'],
    ['Raife Stephen Dahl', 'Prain', '2018-03-11'],
    ['Jamie Kerr', 'Smith', '2018-09-07'],
    ['Ruben', 'Vogt', '2018-10-10'],
    ['Lucca', 'Wood', '2018-06-18'],
    ['Daniel', 'Bothma', '2017-11-30'],
    ['Miles', 'Dignum', '2017-04-07'],
    ['D\'vaunte Sidney', 'Williams', '2017-10-18'],
    ['Alexander', 'Labuschagne', '2017-03-23'],
    ['Caleb Mason', 'Ackerberg', '2017-01-11'],
    ['Gabrijel', 'Marojević', '2017-11-20'],
    ['Jean-Luc', 'Vorster', '2017-08-31'],
    ['James', 'Wood', '2017-01-24'],
    ['Zane', 'Blignaut', '2017-04-26'],
    ['Tyler', 'Marara', '2017-01-20'],
    ['Blake', 'McFarland', '2016-04-16'],
    ['Nicholas', 'Van Wyk', '2016-11-01'],
    ['Hugo', 'Vogt', '2016-02-08'],
    ['Benjamin', 'Van Zyl', '2016-02-03'],
    ['Eric Adam', 'Haering', '2015-02-24'],
    ['Noah Daniel', 'Müller', '2015-09-05'],
    ['Daniel Engelbrecht', 'Vorster', '2015-03-14'],
    ['Daniel', 'Olivier', '2015-12-08'],
    ['Hlela', 'Mathem', '2015-01-14'],
    ['Micheal Malveen', 'Manyera', '2015-11-01'],
    ['Wianré', 'Gerber', '2015-11-17'],
    ['Ava Lee', 'Hartman', '2015-11-23'],
    ['Shemaiah Kyle', 'Moyo', '2015-02-16'],
    ['Hanru', 'Kilian', '2015-08-09'],
    ['Wesley', 'Gwasira', '2014-01-05'],
    ['Luke Daniel', 'Burt', '2014-03-02'],
    ['Dwayne', 'Rangwana', '2014-05-28'],
    ['Kaleb Werner', 'Joubert', '2014-02-10'],
    ['Mikhail', 'Ferreira', '2014-10-31'],
    ['Junior Fortewu', 'Nzabala', '2014-02-01'],
    ['Gideon (Reenen)', 'Van Der Merwe', '2014-02-14'],
    ['Tyler', 'Steenberg', '2014-04-02'],
    ['Tristan', 'Cooper', '2013-11-13'],
    ['Logan', 'Stravino', '2013-12-03'],
    ['Cameron Frank', 'Mather', '2013-08-09'],
    ['Yves', 'Mainguy', '2013-05-19'],
    ['Jean-Luc', 'Ferreira', '2013-06-18'],
    ['Jacobus', 'Olivier', '2013-11-15'],
    ['Jamaine Kiragu', 'Gaitho', '2013-09-08'],
    ['Blessing', 'Mzingelwa', '2013-08-06'],
    ['Regardt', 'Kemp', '2013-02-27'],
    ['Christiaan Cornelius', 'Groenewald', '2013-07-02'],
    ['Conner', 'Bothma', '2012-06-22'],
    ['Lee', 'Skots', '2012-08-20'],
    ['Keagan', 'Coetzee', '2012-09-03'],
    ['Blessmore', 'Munhanda', '2012-09-23'],
    ['Thomason', 'Hempe', '2011-09-20'],
    ['Olothando Olo', 'Norala', '2011-10-15'],
    ['Endinako', 'Sithonga', '2011-05-19'],
    ['Ikkena', 'Enenchukwu', '2011-07-04'],
    ['Kwanga', 'Vinindwa', '2011-09-19'],
    ['Cole Chris', 'Marescia', '2011-09-16'],
    ['Onethemba', 'Tukutezi', '2010-04-02'],
    ['Bongani', 'Ndlovu', '2010-05-01'],
    ['Sange', 'Matham', '2010-02-19'],
    ['Yonwaba', 'Gandashe', '2010-05-22'],
    ['Hlumelo', 'Kepkey', '2010-09-30'],
    ['Siyabonga', 'Mathakatha', '2009-12-22'],
    ['Mishean', 'Grobbelaar', '2009-01-02'],
    ['Amkhitha Ntando', 'Jika', '2009-06-23'],
    ['Junior', 'Ponoane', '2008-10-21'],
    ['Masimbonge', 'Kise', '2008-02-22'],
    ['Liam', 'Martin', '2008-03-17'],
    ['Lucas', 'Isaacs', '2008-07-29'],
    ['Linomtha Lino', 'Gxagxama', '2008-08-12'],
    ['Anda', 'Lolwana', '2008-02-16'],
    ['Thanda', 'Mbetane', '2007-01-16'],
    ['Enoch', 'Mills', '2007-08-16'],
    ['Lindokuhle', 'Xhakaliva', '2007-06-30'],
    ['Luzuko', 'Nyontso', '2007-10-11'],
    ['Mncedi', 'Ngqwemla', '2007-07-11'],
    ['Mihlali', 'Mbovane', '2007-01-01'],
    ['Melusi', 'Chakuchichi', '2006-03-25'],
    ['Ndomiso', 'Ngqwemla', '2006-03-24'],
    ['Brandon Elton', 'Gwature', '2006-05-16'],
    ['Ambrose', 'Kepkey', '2006-05-05'],
    ['BJ', 'MDE', '2006-08-15'],
    ['Sihle', 'Malundo', '2006-09-06'],
    ['Oyena', 'Macotha', '2006-03-19'],
    ['Tadiwanashe', 'Chireva', '2005-02-22'],
    ['Joseph', 'Jangeta', '2005-06-09'],
    ['Brandon', 'Manyera', '2005-12-23'],
    ['Beasley Tinotenda', 'Matava', '2005-08-26'],
    ['Qaqambile', 'Taboyi', '2004-12-12'],
    ['Jesse Jerelle', 'Steyn', '2003-09-28'],
    ['Siyamthanda', 'Mbizo', '2003-09-10'],
    ['Athandile', 'Jonas', '2003-12-29'],
    ['Xanti', 'Mgoqi', '2003-09-08'],
    ['Shaun', 'Mnganisa', '2002-06-09'],
    ['Mpho', 'Ntakakazi', '2002-06-25'],
    ['Milani', 'Gobondwana', '2000-05-23'],
    ['Sithsaba', 'Citwa', '2000-05-29'],
    ['Thandolienkosi', 'Nkomo', '1994-08-05'],
    ['Kevin', 'Nyandoro', '1993-08-23'],
    ['Sibonginkosi', 'Nkomo', '1989-04-14']
];

// Function to calculate age group based on birth year
function calculateAgeGroup($dateOfBirth) {
    $birthDate = new DateTime($dateOfBirth);
    $currentDate = new DateTime('2025-11-17');
    $age = $currentDate->diff($birthDate)->y;
    
    // Age the player turns this year (2025)
    $birthYear = (int)$birthDate->format('Y');
    $currentYear = 2025;
    $ageTurnsThisYear = $currentYear - $birthYear;
    
    // Apply age group mapping based on age turned this year
    if ($ageTurnsThisYear < 5) return 'Too Young';
    elseif ($ageTurnsThisYear == 5) return 'U6';
    elseif ($ageTurnsThisYear == 6) return 'U7';
    elseif ($ageTurnsThisYear == 7) return 'U8';
    elseif ($ageTurnsThisYear == 8) return 'U9';
    elseif ($ageTurnsThisYear == 9) return 'U10';
    elseif ($ageTurnsThisYear == 10) return 'U11';
    elseif ($ageTurnsThisYear == 11) return 'U12';
    elseif ($ageTurnsThisYear == 12) return 'U13';
    elseif ($ageTurnsThisYear == 13) return 'U14';
    elseif ($ageTurnsThisYear == 14) return 'U15';
    elseif ($ageTurnsThisYear == 15) return 'U16';
    elseif ($ageTurnsThisYear == 16) return 'U17';
    elseif ($ageTurnsThisYear == 17) return 'U18';
    elseif ($ageTurnsThisYear == 18) return 'U19';
    elseif ($ageTurnsThisYear == 19) return 'U20';
    elseif ($ageTurnsThisYear == 20) return 'U21';
    else return 'Senior';
}

// Check database schema
$schemaColumns = [];
try {
    $result = $db->query("PRAGMA table_info(players)");
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        $schemaColumns[] = $row['name'];
    }
} catch (Exception $e) {
    die("Error checking database schema: " . $e->getMessage() . "\n");
}

$hasFirstLastName = in_array('first_name', $schemaColumns) && in_array('last_name', $schemaColumns);
$hasNameSurname = in_array('name', $schemaColumns) && in_array('surname', $schemaColumns);

echo "Step 2: Importing " . count($players) . " players...\n";

$imported = 0;
$failed = 0;

foreach ($players as $playerData) {
    list($firstName, $lastName, $dob) = $playerData;
    
    try {
        // Calculate age and age group
        $birthDate = new DateTime($dob);
        $currentDate = new DateTime('2025-11-17');
        $age = $currentDate->diff($birthDate)->y;
        $ageGroup = calculateAgeGroup($dob);
        
        // Build insert data based on schema
        $insertData = [];
        
        if ($hasFirstLastName && !$hasNameSurname) {
            $insertData['first_name'] = $firstName;
            $insertData['last_name'] = $lastName;
        } else {
            $insertData['name'] = $firstName;
            $insertData['surname'] = $lastName;
        }
        
        $insertData['date_of_birth'] = $dob;
        $insertData['age'] = $age;
        
        // Add age_group if column exists
        if (in_array('age_group', $schemaColumns)) {
            $insertData['age_group'] = $ageGroup;
        }
        
        // Add status if column exists
        if (in_array('status', $schemaColumns)) {
            $insertData['status'] = 'active';
        }
        
        // Add created_at if column exists
        if (in_array('created_at', $schemaColumns)) {
            $insertData['created_at'] = date('Y-m-d H:i:s');
        }
        
        // Build and execute insert query
        $columns = array_keys($insertData);
        $placeholders = ':' . implode(', :', $columns);
        $sql = "INSERT INTO players (" . implode(', ', $columns) . ") VALUES (" . $placeholders . ")";
        
        $stmt = $db->prepare($sql);
        
        foreach ($insertData as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $imported++;
        
        echo "✓ Imported: {$firstName} {$lastName} (Age: {$age}, Group: {$ageGroup})\n";
        
    } catch (Exception $e) {
        $failed++;
        echo "✗ Failed: {$firstName} {$lastName} - " . $e->getMessage() . "\n";
    }
}

echo "\n=== Import Complete ===\n";
echo "Successfully imported: {$imported} players\n";
echo "Failed: {$failed} players\n";

// Display age group summary
echo "\n=== Age Group Summary ===\n";
$ageGroupCounts = [];
foreach ($players as $playerData) {
    $ageGroup = calculateAgeGroup($playerData[2]);
    if (!isset($ageGroupCounts[$ageGroup])) {
        $ageGroupCounts[$ageGroup] = 0;
    }
    $ageGroupCounts[$ageGroup]++;
}

ksort($ageGroupCounts);
foreach ($ageGroupCounts as $group => $count) {
    echo "{$group}: {$count} players\n";
}

echo "\n✓ All done!\n";
