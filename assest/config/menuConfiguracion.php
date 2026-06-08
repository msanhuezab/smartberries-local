<?php
$MENU_CONFIGURACION = true;
$MENU_CONFIGURACION_DESDE_MODULO = strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/configuracion/vista/') !== false;
ob_start();
include "../../assest/config/menuExpo.php";
$MENU_CONFIGURACION_HTML = ob_get_clean();
if ($MENU_CONFIGURACION_DESDE_MODULO) {
    $MENU_CONFIGURACION_HTML = preg_replace('/href="((?:registro|listar|cron|ver|editar)[^"#?]*\.php)"/', 'href="../../exportadora/vista/$1"', $MENU_CONFIGURACION_HTML);
}
echo $MENU_CONFIGURACION_HTML;
?>
