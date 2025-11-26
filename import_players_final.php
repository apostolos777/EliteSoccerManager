<?php
/**
 * Import final player list with auto-calculated age groups
 */

$db = new SQLite3('database.db');
$db->exec('PRAGMA foreign_keys = OFF');

// Delete all existing players
$db->exec('DELETE FROM players');
echo "✓ Cleared existing players\n";

// Player data from user input
$players = [
    ['Gabriele', 'Gioia', '10/02/2019'],
    ['Will', 'Dignum', '06/20/2019'],
    ['Reece', 'Labuschagne', '02/25/2019'],
    ['Ronan Alexander', 'Glöss', '02/09/2019'],
    ['Matteo Joshua', 'Ferreira', '11/03/2019'],
    ['Liam Jeffrey', 'Brummer', '05/12/2018'],
    ['Raife Stephen Dahl', 'Prain', '03/11/2018'],
    ['Jamie Kerr', 'Smith', '09/07/2018'],
    ['Ruben', 'Vogt', '10/10/2018'],
    ['Lucca', 'Wood', '06/18/2018'],
    ['Daniel', 'Bothma', '11/30/2017'],
    ['Miles', 'Dignum', '04/07/2017'],
    ['D\'vaunte Sidney', 'Williams', '10/18/2017'],
    ['Alexander', 'Labuschagne', '03/23/2017'],
    ['Caleb Mason', 'Ackerberg', '01/11/2017'],
    ['Gabrijel', 'Marojević', '11/20/2017'],
    ['Jean-Luc', 'Vorster', '08/31/2017'],
    ['James', 'Wood', '01/24/2017'],
    ['Zane', 'Blignaut', '04/26/2017'],
    ['Tyler', 'Marara', '01/20/2017'],
    ['Blake', 'McFarland', '4/16/2016'],
    ['Nicholas', 'Van Wyk', '11/1/2016'],
    ['Hugo', 'Vogt', '2/8/2016'],
    ['Benjamin', 'Van Zyl', '2/3/2016'],
    ['Eric Adam', 'Haering', '02/24/2015'],
    ['Noah Daniel', 'Müller', '09/05/2015'],
    ['Daniel Engelbrecht', 'Vorster', '03/14/2015'],
    ['Daniel', 'Olivier', '12/8/2015'],
    ['Hlela', 'Mathem', '01/14/2015'],
    ['Micheal Malveen', 'Manyera', '11/01/2015'],
    ['Wianré', 'Gerber', '11/17/2015'],
    ['Ava Lee', 'Hartman', '11/23/2015'],
    ['Shemaiah Kyle', 'Moyo', '2/16/2015'],
    ['Hanru', 'Kilian', '8/9/2015'],
    ['Wesley', 'Gwasira', '01/05/2014'],
    ['Luke Daniel', 'Burt', '03/02/2014'],
    ['Dwayne', 'Rangwana', '5/28/2014'],
    ['Kaleb Werner', 'Joubert', '2/10/2014'],
    ['Mikhail', 'Ferreira', '10/31/2014'],
    ['Junior Fortewu', 'Nzabala', '2/1/2014'],
    ['Gideon (Reenen)', 'Van Der Merwe', '2/14/2014'],
    ['Tyler', 'Steenberg', '4/2/2014'],
    ['Tristan', 'Cooper', '11/13/2013'],
    ['Logan', 'Stravino', '12/03/2013'],
    ['Cameron Frank', 'Mather', '8/9/2013'],
    ['Yves', 'Mainguy', '5/19/2013'],
    ['Jean-Luc', 'Ferreira', '6/18/2013'],
    ['Jacobus', 'Olivier', '11/15/2013'],
    ['Jamaine Kiragu', 'Gaitho', '9/8/2013'],
    ['Blessing', 'Mzingelwa', '8/6/2013'],
    ['Regardt', 'Kemp', '2/27/2013'],
    ['Christiaan Cornelius', 'Groenewald', '7/2/2013'],
    ['Conner', 'Bothma', '6/22/2012'],
    ['Lee', 'Skots', '08/20/2012'],
    ['Keagan', 'Coetzee', '09/03/2012'],
    ['Blessmore', 'Munhanda', '09/23/2012'],
    ['Thomason', 'Hempe', '9/20/2011'],
    ['Olothando Olo', 'Norala', '10/15/2011'],
    ['Endinako', 'Sithonga', '5/19/2011'],
    ['Ikkena', 'Enenchukwu', '7/4/2011'],
    ['Kwanga', 'Vinindwa', '9/19/2011'],
    ['Cole Chris', 'Marescia', '09/16/2011'],
    ['Onethemba', 'Tukutezi', '4/2/2010'],
    ['Bongani', 'Ndlovu', '5/1/2010'],
    ['Sange', 'Matham', '2/19/2010'],
    ['Yonwaba', 'Gandashe', '5/22/2010'],
    ['Hlumelo', 'Kepkey', '09/30/2010'],
    ['Siyabonga', 'Mathakatha', '12/22/2009'],
    ['Mishean', 'Grobbelaar', '01/02/2009'],
    ['Amkhitha Ntando', 'Jika', '06/23/2009'],
    ['Junior', 'Ponoane', '10/21/2008'],
    ['Masimbonge', 'Kise', '02/22/2008'],
    ['Liam', 'Martin', '3/17/2008'],
    ['Lucas', 'Isaacs', '7/29/2008'],
    ['Linomtha Lino', 'Gxagxama', '8/12/2008'],
    ['Anda', 'Lolwana', '2/16/2008'],
    ['Thanda', 'Mbetane', '1/16/2007'],
    ['Enoch', 'Mills', '8/16/2007'],
    ['Lindokuhle', 'Xhakaliva', '6/30/2007'],
    ['Luzuko', 'Nyontso', '10/11/2007'],
    ['Mncedi', 'Ngqwemla', '7/11/2007'],
    ['Mihlali', 'Mbovane', '01/01/2007'],
    ['Melusi', 'Chakuchichi', '3/25/2006'],
    ['Ndomiso', 'Ngqwemla', '3/24/2006'],
    ['Brandon Elton', 'Gwature', '5/16/2006'],
    ['Ambrose', 'Kepkey', '5/5/2006'],
    ['BJ', 'MDE', '08/15/2006'],
    ['Sihle', 'Malundo', '9/6/2006'],
    ['Oyena', 'Macotha', '3/19/2006'],
    ['Tadiwanashe', 'Chireva', '2/22/2005'],
    ['Joseph', 'Jangeta', '6/9/2005'],
    ['Brandon', 'Manyera', '12/23/2005'],
    ['Beasley Tinotenda', 'Matava', '08/26/2005'],
    ['Qaqambile', 'Taboyi', '12/12/2004'],
    ['Jesse Jerelle', 'Steyn', '9/28/2003'],
    ['Siyamthanda', 'Mbizo', '09/10/2003'],
    ['Athandile', 'Jonas', '12/29/2003'],
    ['Xanti', 'Mgoqi', '09/08/2003'],
    ['Shaun', 'Mnganisa', '6/9/2002'],
    ['Mpho', 'Ntakakazi', '06/25/2002'],
    ['Milani', 'Gobondwana', '5/23/2000'],
    ['Sithsaba', 'Citwa', '5/29/2000'],
    ['Thandolienkosi', 'Nkomo', '08/05/1994'],
    ['Kevin', 'Nyandoro', '08/23/1993'],
    ['Sibonginkosi', 'Nkomo', '04/14/1989'],
];

// Age group mapping function
function calculateAgeGroup($dateOfBirth) {
    try {
        $dob = DateTime::createFromFormat('m/d/Y', $dateOfBirth);
        if (!$dob) {
            return 'Unknown';
        }
        $today = new DateTime();
        $age = $today->diff($dob)->y;
        
        // Exact age mapping
        switch ($age) {
            case 5: return 'U6';
            case 6: return 'U7';
            case 7: return 'U8';
            case 8: return 'U9';
            case 9: return 'U10';
            case 10: return 'U11';
            case 11: return 'U12';
            case 12: return 'U13';
            case 13: return 'U14';
            case 14: return 'U15';
            case 15: return 'U16';
            case 16: return 'U17';
            case 17: return 'U18';
            case 18: return 'U19';
            case 19: return 'U20';
            case 20: return 'U21';
            default:
                return ($age >= 21) ? 'Senior' : 'Too Young';
        }
    } catch (Exception $e) {
        return 'Unknown';
    }
}

// Insert players
$stmt = $db->prepare('
    INSERT INTO players (name, surname, date_of_birth, age, age_group, status, created_at, updated_at)
    VALUES (?, ?, ?, ?, ?, ?, datetime("now"), datetime("now"))
');

$ageGroupStats = [];
$insertedCount = 0;

foreach ($players as $player) {
    [$name, $surname, $dob] = $player;
    
    // Skip empty rows
    if (empty($name) || empty($surname)) {
        continue;
    }
    
    // Calculate age and age group
    $dobDate = DateTime::createFromFormat('m/d/Y', $dob);
    $today = new DateTime();
    $age = $today->diff($dobDate)->y;
    $ageGroup = calculateAgeGroup($dob);
    
    // Insert player
    $stmt->bindValue(1, trim($name), SQLITE3_TEXT);
    $stmt->bindValue(2, trim($surname), SQLITE3_TEXT);
    $stmt->bindValue(3, $dob, SQLITE3_TEXT);
    $stmt->bindValue(4, $age, SQLITE3_INTEGER);
    $stmt->bindValue(5, $ageGroup, SQLITE3_TEXT);
    $stmt->bindValue(6, 'active', SQLITE3_TEXT);
    
    $stmt->execute();
    $insertedCount++;
    
    // Track age group distribution
    if (!isset($ageGroupStats[$ageGroup])) {
        $ageGroupStats[$ageGroup] = 0;
    }
    $ageGroupStats[$ageGroup]++;
}

echo "\n✓ Imported $insertedCount players\n\n";
echo "Age Group Distribution:\n";
ksort($ageGroupStats);
foreach ($ageGroupStats as $group => $count) {
    echo "  $group: $count\n";
}

// Verify count
$finalCount = $db->querySingle('SELECT COUNT(*) FROM players');
echo "\n✓ Final player count: $finalCount\n";

$db->exec('PRAGMA foreign_keys = ON');
$db->close();
