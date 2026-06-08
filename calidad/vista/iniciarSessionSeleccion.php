<?php
session_start();
$_SESSION["MODULO_POST_LOGIN"] = "calidad";
header('Location: ../../fruta/vista/iniciarSessionSeleccion.php');
exit;
?>
