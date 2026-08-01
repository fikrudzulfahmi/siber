<?php
require 'config/database.php';
$db = (new Database())->getConnection();
$stmt = $db->query("DESCRIBE jadwal_pelajaran");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
