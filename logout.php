<?php
require_once 'includes/security.php';

cerrarSesion();
header("Location: login.php?error=sesion_cerrada");
exit();
?>
