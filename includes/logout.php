<?php
session_start();
session_destroy();
if (!headers_sent()) {
    header("Location: ../login.php");
    exit();
} else {
    echo 'Logged out. <a href="../login.php">Go to login</a>';
}
?>