<?php
session_start();

// Simpan data dari form ke session
foreach ($_POST as $key => $value) {
    $_SESSION['pendaftaran'][$key] = $value;
}
?>