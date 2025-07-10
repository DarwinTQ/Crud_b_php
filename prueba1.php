<?php
require_once 'conexion.php';

try {
    $db = new DatabaseConnection();
    $conn = $db->getConnection();
    echo "Conexión exitosa a Oracle.";
    $db->closeConnection();
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
