<?php
$dbHost = "localhost";
$dbName = "my_ec_store_db";
$dbUser = "masatokuratani";
$dbPass = "masatomimi55";

//---- データベースへの接続 ---//
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,
];
try {
  $pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, $options);
} catch (PDOException $e) {
  die("データベース接続に失敗しました。しばらくしてから再度お試しください。: " . $e->getMessage() . "\n");
}