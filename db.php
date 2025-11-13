<?php
// db.php

$host = 'localhost';
$db = 'goout'; // <-- BDDEL HADI ILA KANT 3NDEK SMIYA KHRA L-DATABASE
$user = 'root';
$pass = ''; // Khlliha khawya ila makatkhdemch b password f XAMPP
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>