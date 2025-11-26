<?php
foreach([__DIR__ . "/../database.db", __DIR__ . "/../vivo_football.db"] as $f){
    echo "\n== $f ==\n";
    try{
        $db=new PDO("sqlite:$f");
        $db->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION);
        $rows=$db->query("SELECT setting_key, setting_value FROM club_settings WHERE setting_key LIKE 'color_%'")->fetchAll(PDO::FETCH_ASSOC);
        if(!$rows) { echo "(no rows)\n"; }
        foreach($rows as $r) echo $r['setting_key'] . " = " . $r['setting_value'] . "\n";
    }catch(Exception $e){ echo "Error reading $f: " . $e->getMessage() . "\n"; }
}
