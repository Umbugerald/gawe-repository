<?php
session_start();
if (isset($_GET['id'])) {
    $_SESSION['id_alamat_pilihan'] = $_GET['id'];
    echo json_encode(['status' => 'success']);
}
?>