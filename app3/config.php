<?php
    $server = "tcp:<your-server>.database.windows.net,1433";
    $database = "<your-database>";
    $username = "<your-username>";
    $password = '<your-password>';
    
    $conn = sqlsrv_connect($server, array(
        "Database" => $database,
        "UID" => $username,
        "PWD" => $password,
        "Encrypt" => true,
        "TrustServerCertificate" => false
    ));
?>