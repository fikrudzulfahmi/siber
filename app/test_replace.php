<?php
$folder_id = '13yUQjoLBTAMMZ4YP2XN3zagFHysja2G5';
$configFile = __DIR__ . '/config.php';
$configContent = file_get_contents($configFile);
if (strpos($configContent, "define('GOOGLE_DRIVE_FOLDER_ID'") !== false) {
    $configContent = preg_replace(
        "/define\('GOOGLE_DRIVE_FOLDER_ID',\s*'.*?'\);/",
        "define('GOOGLE_DRIVE_FOLDER_ID', '" . addslashes($folder_id) . "');",
        $configContent
    );
    file_put_contents($configFile, $configContent);
    echo "SUCCESS\n";
} else {
    echo "NOT FOUND\n";
}
