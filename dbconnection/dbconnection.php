<?php 

    $host = "localhost";
    $username = "root";
    $pass = "";
    $db_name = "ypk_db";

    $conn = mysqli_connect($host, $username, $pass, $db_name);

    if(!$conn){
        die("Koneksi gagal : " . mysqli_connect_error());
    };
?>

