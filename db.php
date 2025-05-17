<?php
$host = 'sql102.infinityfree.com'; // Host do banco de dados
$dbname = 'if0_38274070_Astra';     // Nome do banco de dados
$username = 'if0_38274070';         // Usuário do banco de dados
$password = 'jBuP0dYfteTgGx';       // Senha do banco de dados

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
?>