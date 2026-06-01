<?php
include_once "../../assest/config/validarUsuarioFruta.php";

include_once '../../assest/controlador/EXIMATERIAPRIMA_ADO.php';
include_once '../../assest/controlador/DRECEPCIONMP_ADO.php';
include_once '../../assest/controlador/RECEPCIONMP_ADO.php';
include_once '../../assest/controlador/AUSUARIO_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';

include_once '../../assest/modelo/EXIMATERIAPRIMA.php';

$EXIMATERIAPRIMA_ADO =  new EXIMATERIAPRIMA_ADO();
$DRECEPCIONMP_ADO = new DRECEPCIONMP_ADO();
$RECEPCIONMP_ADO = new RECEPCIONMP_ADO();
$AUSUARIO_ADO = new AUSUARIO_ADO();
$USUARIO_ADO = new USUARIO_ADO();
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();

$EXIMATERIAPRIMA =  new EXIMATERIAPRIMA();

$IDEXIMATERIAPRIMA = "";
$FOLIO = "";
$FOLION = "";
$CODIGO = "";
$MOTIVO = "";
$MENSAJE = "";
$MENSAJEEXITO = "";
$MENSAJEENVIO = "";
$FILTROESTADO = 1;
$IDRECEPCION = "";
$NUMERORECEPCION = "";
$ESTADORECEPCION = null;
$ESTADOFOLIO = 0;
$CORREOUSUARIO = "";
$NOMBRECOMPLETOUSUARIO = "";
$NOMBREEMPRESA = "";
$NOMBREPLANTA = "";
$NOMBRETEMPORADA = "Sin datos";
$ARRAYRECEPCION = array();
$ARRAYHISTORIAL = $AUSUARIO_ADO->listarUltimosCambiosFolioMp($EMPRESAS, $PLANTAS, $TEMPORADAS);

function mapearDetalleHistorialFolio($mensaje)
{
    $detalle = [
        'accion' => (stripos($mensaje, 'deshabilita folio') !== false) ? 'Cambio y deshabilitar' : 'Cambio de folio',
        'folio_antiguo' => 'No identificado',
        'folio_nuevo' => 'No identificado',
    ];

    if (preg_match('/de\s+([0-9A-Za-z-]+)\s+a\s+([0-9A-Za-z-]+)/i', $mensaje, $coincidencias)) {
        $detalle['folio_antiguo'] = $coincidencias[1];
        $detalle['folio_nuevo'] = $coincidencias[2];
    }

    return $detalle;
}

$ARRAYUSUARIO = $USUARIO_ADO->verUsuario($_SESSION["ID_USUARIO"]);
if ($ARRAYUSUARIO) {
    $CORREOUSUARIO = trim($ARRAYUSUARIO[0]['EMAIL_USUARIO']);
    $NOMBRECOMPLETOUSUARIO = trim($ARRAYUSUARIO[0]['PNOMBRE_USUARIO'] . ' ' . $ARRAYUSUARIO[0]['SNOMBRE_USUARIO'] . ' ' . $ARRAYUSUARIO[0]['PAPELLIDO_USUARIO'] . ' ' . $ARRAYUSUARIO[0]['SAPELLIDO_USUARIO']);
}
$ARRAYNOMBRECOMPLETO = $USUARIO_ADO->ObtenerNombreCompleto($_SESSION["ID_USUARIO"]);
if ($ARRAYNOMBRECOMPLETO && !$NOMBRECOMPLETOUSUARIO) {
    $NOMBRECOMPLETOUSUARIO = trim($ARRAYNOMBRECOMPLETO[0]['NOMBRE_COMPLETO']);
}
$NOMBRECOMPLETOUSUARIO = trim($NOMBRECOMPLETOUSUARIO) !== '' ? trim($NOMBRECOMPLETOUSUARIO) : $_SESSION['NOMBRE_USUARIO'];

$ARRAYEMPRESA = $EMPRESA_ADO->verEmpresa($EMPRESAS);
if ($ARRAYEMPRESA) {
    $NOMBREEMPRESA = $ARRAYEMPRESA[0]['NOMBRE_EMPRESA'];
}
$ARRAYPLANTA = $PLANTA_ADO->verPlanta($PLANTAS);
if ($ARRAYPLANTA) {
    $NOMBREPLANTA = $ARRAYPLANTA[0]['NOMBRE_PLANTA'];
}
$NOMBREEMPRESA = $NOMBREEMPRESA ? $NOMBREEMPRESA : 'Sin datos';
$NOMBREPLANTA = $NOMBREPLANTA ? $NOMBREPLANTA : 'Sin datos';

$ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($TEMPORADAS);
if ($ARRAYTEMPORADA) {
    $NOMBRETEMPORADA = $ARRAYTEMPORADA[0]['NOMBRE_TEMPORADA'];
}

$MOSTRARDESHABILITAR = false;
$MOSTRARCAMBIAR = false;

if (!empty($_SESSION['GESTION_FOLIO_MP_EXITO'])) {
    $MENSAJEEXITO = $_SESSION['GESTION_FOLIO_MP_EXITO'];
    unset($_SESSION['GESTION_FOLIO_MP_EXITO']);
}

if (isset($_REQUEST['FILTRO_ESTADO'])) {
    $FILTROESTADO = $_REQUEST['FILTRO_ESTADO'] === "0" ? 0 : 1;
}

function enviarCorreoSMTP($destinatarios, $asunto, $mensaje, $remitente, $usuario, $contrasena, $host, $puerto, $timeout = 30)
{
    $destinatarios = (array) $destinatarios;
    $contextoSSL = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT,
        ]
    ]);

    $conexion = @stream_socket_client("ssl://{$host}:{$puerto}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $contextoSSL);

    if (!$conexion) {
        return [false, "No se pudo conectar al servidor SMTP ({$errstr})"];
    }

    if (function_exists('stream_set_timeout')) {
        stream_set_timeout($conexion, $timeout);
    }

    $leerRespuesta = function () use ($conexion) {
        $respuesta = '';
        while ($linea = fgets($conexion, 515)) {
            $respuesta .= $linea;
            if (isset($linea[3]) && $linea[3] === ' ') {
                break;
            }
        }
        return $respuesta;
    };

    $comando = function ($instruccion, $codigoEsperado) use ($conexion, $leerRespuesta) {
        fwrite($conexion, $instruccion . "\r\n");
        $respuesta = $leerRespuesta();
        if (substr($respuesta, 0, 3) !== $codigoEsperado) {
            throw new Exception("Error SMTP en '{$instruccion}': {$respuesta}");
        }
        return $respuesta;
    };

    $respuestaInicial = $leerRespuesta();
    if (substr($respuestaInicial, 0, 3) !== '220') {
        fclose($conexion);
        return [false, "El servidor SMTP no respondió correctamente: {$respuestaInicial}"];
    }

    $hostEhlo = $host ?: 'localhost';
    try {
        $comando('EHLO ' . $hostEhlo, '250');
    } catch (Exception $e) {
        $comando('HELO ' . $hostEhlo, '250');
    }

    try {
        $comando('AUTH LOGIN', '334');
        $comando(base64_encode($usuario), '334');
        $comando(base64_encode($contrasena), '235');
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error de autenticación SMTP: " . $e->getMessage()];
    }

    try {
        $comando("MAIL FROM:<{$remitente}>", '250');
        foreach ($destinatarios as $correo) {
            $comando("RCPT TO:<{$correo}>", '250');
        }
        $comando('DATA', '354');

        $cabeceras = "Date: " . date('r') . "\r\n" .
            "Message-ID: <" . uniqid() . "@" . ($hostEhlo ?: 'localhost') . ">\r\n" .
            "From: {$remitente}\r\n" .
            "Return-Path: {$remitente}\r\n" .
            "Reply-To: {$remitente}\r\n" .
            "To: " . implode(', ', $destinatarios) . "\r\n" .
            "Subject: {$asunto}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "X-Mailer: PHP/" . phpversion() . "\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n\r\n";

        $mensajeNormalizado = str_replace(["\r\n", "\n"], "\r\n", $mensaje);
        fwrite($conexion, $cabeceras . $mensajeNormalizado . "\r\n.\r\n");
        $respuestaData = $leerRespuesta();
        if (substr($respuestaData, 0, 3) !== '250') {
            throw new Exception("Error SMTP tras DATA: {$respuestaData}");
        }
        $comando('QUIT', '221');
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error al enviar correo: " . $e->getMessage()];
    }

    fclose($conexion);
    return [true, null];
}

$ARRAYEXISTENCIA = $EXIMATERIAPRIMA_ADO->listarEximateriaprimaEnExistencia($EMPRESAS, $PLANTAS, $TEMPORADAS, $FILTROESTADO);

if ($_POST) {
    if (isset($_REQUEST['IDEXIMATERIAPRIMA'])) {
        $IDEXIMATERIAPRIMA = $_REQUEST['IDEXIMATERIAPRIMA'];
    }
    if (isset($_REQUEST['FOLION'])) {
        $FOLION = $_REQUEST['FOLION'];
    }
    if (isset($_REQUEST['CODIGO'])) {
        $CODIGO = $_REQUEST['CODIGO'];
    }
    if (isset($_REQUEST['MOTIVO'])) {
        $MOTIVO = $_REQUEST['MOTIVO'];
    }
}

$ARRAYFOLIOSELECCIONADO = array_filter($ARRAYEXISTENCIA, function ($registro) use ($IDEXIMATERIAPRIMA) {
    return $registro['ID_EXIMATERIAPRIMA'] == $IDEXIMATERIAPRIMA;
});

if ($ARRAYFOLIOSELECCIONADO) {
    $DATOSFOLIO = array_values($ARRAYFOLIOSELECCIONADO)[0];
    $FOLIO = $DATOSFOLIO['FOLIO_EXIMATERIAPRIMA'];
    $IDRECEPCION = $DATOSFOLIO['ID_RECEPCION'];
    $NUMERORECEPCION = $DATOSFOLIO['NUMERO_RECEPCION'];
    $ESTADORECEPCION = $DATOSFOLIO['ESTADO_RECEPCION'];
    $ESTADOFOLIO = $DATOSFOLIO['ESTADO_REGISTRO'];
    $MOSTRARDESHABILITAR = $FOLIO && $ESTADOFOLIO == 1;
    $MOSTRARCAMBIAR = $FOLIO && $ESTADOFOLIO != 1;
    if ($IDRECEPCION) {
        $ARRAYRECEPCION = $RECEPCIONMP_ADO->verRecepcion($IDRECEPCION);
        if ($ARRAYRECEPCION) {
            $NUMERORECEPCION = $ARRAYRECEPCION[0]['NUMERO_RECEPCION'];
            $ESTADORECEPCION = $ARRAYRECEPCION[0]['ESTADO'];
        }
    }
}

if (isset($_REQUEST['DESHABILITAR'])) {
    $errores = array();
    if (!$IDEXIMATERIAPRIMA) {
        $errores[] = "Debe seleccionar un folio activo para deshabilitar.";
    }
    if ($ESTADOFOLIO != 1) {
        $errores[] = "El folio seleccionado ya no se encuentra activo.";
    }
    if (!$FOLION) {
        $errores[] = "Ingrese el nuevo folio.";
    }
    if (!$CODIGO) {
        $errores[] = "Debe ingresar el código recibido.";
    }

    if (!empty($_SESSION['GESTION_FOLIO_MP_CODIGO'])) {
        if ($_SESSION['GESTION_FOLIO_MP_CODIGO'] != $CODIGO) {
            $errores[] = "El código de autorización no coincide.";
        }
        if (!empty($_SESSION['GESTION_FOLIO_MP_TIEMPO']) && (time() - $_SESSION['GESTION_FOLIO_MP_TIEMPO']) > 900) {
            $errores[] = "El código de autorización ha expirado.";
        }
    } else {
        $errores[] = "Debe solicitar un código antes de cambiar el folio.";
    }

    if (!$errores) {
        $FOLIOACTUAL = $FOLIO;
        $EXIMATERIAPRIMA->__SET('FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('FOLIO_AUXILIAR_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ALIAS_DINAMICO_FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ALIAS_ESTATICO_FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ID_EXIMATERIAPRIMA', $IDEXIMATERIAPRIMA);
        $EXIMATERIAPRIMA_ADO->cambioFolioYDeshabilitar($EXIMATERIAPRIMA);

        if ($IDRECEPCION && $FOLIOACTUAL) {
            $DRECEPCIONMP_ADO->actualizarYDeshabilitarFolioPorRecepcion($IDRECEPCION, $FOLIOACTUAL, $FOLION);
        }

        $descripcionAccion = $NOMBRECOMPLETOUSUARIO . ", Cambia y deshabilita folio de materia prima de " . $FOLIOACTUAL . " a " . $FOLION;
        if ($NUMERORECEPCION) {
            $textoEstadoRecepcion = $ESTADORECEPCION === null ? '' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? ' Abierta' : ' Cerrada');
            $descripcionAccion .= " (Recepción " . $NUMERORECEPCION . ($textoEstadoRecepcion ? " -" . $textoEstadoRecepcion : '') . ")";
        }
        if (!empty($MOTIVO)) {
            $descripcionAccion .= " (Motivo: " . $MOTIVO . ")";
        }

        $AUSUARIO_ADO->agregarAusuario2("NULL", 1, 2, $descripcionAccion, "fruta_eximateriaprima", $IDEXIMATERIAPRIMA, $_SESSION["ID_USUARIO"], $_SESSION['ID_EMPRESA'], $_SESSION['ID_PLANTA'], $_SESSION['ID_TEMPORADA']);

        $destinatariosCambio = array_values(array_unique(array_filter([$CORREOUSUARIO, 'maperez@fvolcan.cl', 'eisla@fvolcan.cl'])));
        $asuntoCambio = 'Folio materia prima cambiado y deshabilitado';
        $textoEstadoRecepcionCambio = $ESTADORECEPCION === null ? 'Sin datos' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? 'Abierta' : 'Cerrada');
        $mensajeCambio = "Se ha cambiado y deshabilitado un folio de materia prima." . "\r\n\r\n" .
            "Usuario: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
            "Planta: " . $NOMBREPLANTA . "\r\n" .
            "Empresa: " . $NOMBREEMPRESA . "\r\n" .
            "Temporada: " . $NOMBRETEMPORADA . "\r\n" .
            "Recepción origen: " . ($NUMERORECEPCION ? $NUMERORECEPCION : 'Sin datos') . " (" . $textoEstadoRecepcionCambio . ")\r\n" .
            "Folio actual: " . $FOLIOACTUAL . "\r\n" .
            "Folio nuevo: " . $FOLION . "\r\n" .
            "Estado posterior: Deshabilitado\r\n" .
            (empty($MOTIVO) ? "" : "Motivo: " . $MOTIVO . "\r\n");

        $remitente = 'informes@volcanfoods.cl';
        $usuarioSMTP = 'informes@volcanfoods.cl';
        $contrasenaSMTP = '1z=EWfu0026k';
        $hostSMTP = 'mail.volcanfoods.cl';
        $puertoSMTP = 465;

        [$envioCambioOk, $errorEnvioCambio] = enviarCorreoSMTP($destinatariosCambio, $asuntoCambio, $mensajeCambio, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);

        $textoNotificacion = "El folio de materia prima fue cambiado y deshabilitado correctamente.";
        if (!$envioCambioOk) {
            $textoNotificacion .= " No se pudo enviar la notificación por correo: " . ($errorEnvioCambio ?: 'revise la configuración SMTP.');
        }

        $_SESSION['GESTION_FOLIO_MP_EXITO'] = $textoNotificacion;

        unset($_SESSION['GESTION_FOLIO_MP_CODIGO']);
        unset($_SESSION['GESTION_FOLIO_MP_TIEMPO']);

        header('Location: registroGestionFolioMp.php');
        exit();
    } else {
        $MENSAJE = implode(" ", $errores);
    }
}

if (isset($_REQUEST['SOLICITAR'])) {
    if (!$IDEXIMATERIAPRIMA) {
        $MENSAJE = "Debe seleccionar un folio para solicitar el código.";
    } else {
        $CODIGOVERIFICACION = random_int(100000, 999999);
        $_SESSION['GESTION_FOLIO_MP_CODIGO'] = $CODIGOVERIFICACION;
        $_SESSION['GESTION_FOLIO_MP_TIEMPO'] = time();

        $textoEstadoRecepcion = $ESTADORECEPCION === null ? 'Sin datos' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? 'Abierta' : 'Cerrada');
        $folioDestinoTexto = $FOLION ? $FOLION : 'No indicado';
        $correoDestino = array_values(array_unique(array_filter(['maperez@fvolcan.cl', 'eisla@fvolcan.cl'])));
        $asunto = 'Código de autorización - Cambio de folio materia prima';
        $mensajeCorreo = "Se ha solicitado un código para cambiar el folio de materia prima." . "\r\n\r\n" .
            "Usuario: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
            "Planta: " . $NOMBREPLANTA . "\r\n" .
            "Empresa: " . $NOMBREEMPRESA . "\r\n" .
            "Temporada: " . $NOMBRETEMPORADA . "\r\n" .
            "Recepción origen: " . ($NUMERORECEPCION ? $NUMERORECEPCION : 'Sin datos') . " (" . $textoEstadoRecepcion . ")\r\n" .
            "Folio actual: " . $FOLIO . "\r\n" .
            "Folio nuevo: " . $folioDestinoTexto . "\r\n" .
            (empty($MOTIVO) ? "" : "Motivo: " . $MOTIVO . "\r\n") .
            "Código de autorización: " . $CODIGOVERIFICACION . "\r\n\r\n" .
            "Este código es válido por 15 minutos y se envía a María de los Ángeles y Erwin Isla.";

        $remitente = 'informes@volcanfoods.cl';
        $usuarioSMTP = 'informes@volcanfoods.cl';
        $contrasenaSMTP = '1z=EWfu0026k';
        $hostSMTP = 'mail.volcanfoods.cl';
        $puertoSMTP = 465;

        [$envioOk, $errorEnvio] = enviarCorreoSMTP($correoDestino, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);

        if ($envioOk) {
            $MENSAJEENVIO = "Código enviado a los correos autorizados. Duración: 15 minutos (María de los Ángeles y Erwin Isla).";
        } else {
            $MENSAJE = $errorEnvio ?: "No fue posible enviar el correo. Verifique la configuración de correo en el servidor.";
        }
    }
}

if (isset($_REQUEST['CAMBIAR'])) {

    $errores = array();
    if (!$IDEXIMATERIAPRIMA) {
        $errores[] = "Debe seleccionar un folio en existencia.";
    }
    if (!$FOLION) {
        $errores[] = "Ingrese el nuevo folio.";
    }
    if (!$CODIGO) {
        $errores[] = "Debe ingresar el código recibido.";
    }

    if (!empty($_SESSION['GESTION_FOLIO_MP_CODIGO'])) {
        if ($_SESSION['GESTION_FOLIO_MP_CODIGO'] != $CODIGO) {
            $errores[] = "El código de autorización no coincide.";
        }
        if (!empty($_SESSION['GESTION_FOLIO_MP_TIEMPO']) && (time() - $_SESSION['GESTION_FOLIO_MP_TIEMPO']) > 900) {
            $errores[] = "El código de autorización ha expirado.";
        }
    } else {
        $errores[] = "Debe solicitar un código antes de cambiar el folio.";
    }

    if (!$errores) {
        $FOLIOACTUAL = $FOLIO;
        $EXIMATERIAPRIMA->__SET('FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('FOLIO_AUXILIAR_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ALIAS_DINAMICO_FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ALIAS_ESTATICO_FOLIO_EXIMATERIAPRIMA', $FOLION);
        $EXIMATERIAPRIMA->__SET('ID_EXIMATERIAPRIMA', $IDEXIMATERIAPRIMA);
        $EXIMATERIAPRIMA_ADO->cambioFolio($EXIMATERIAPRIMA);

        if ($IDRECEPCION && $FOLIOACTUAL) {
            $DRECEPCIONMP_ADO->actualizarFolioPorRecepcion($IDRECEPCION, $FOLIOACTUAL, $FOLION);
        }

        $descripcionAccion = "" . $NOMBRECOMPLETOUSUARIO . ", Cambio de folio de materia prima de " . $FOLIOACTUAL . " a " . $FOLION;
        if ($NUMERORECEPCION) {
            $textoEstadoRecepcion = $ESTADORECEPCION === null ? '' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? ' Abierta' : ' Cerrada');
            $descripcionAccion .= " (Recepción " . $NUMERORECEPCION . ($textoEstadoRecepcion ? " -" . $textoEstadoRecepcion : '') . ")";
        }
        if (!empty($MOTIVO)) {
            $descripcionAccion .= " (Motivo: " . $MOTIVO . ")";
        }

        $AUSUARIO_ADO->agregarAusuario2("NULL", 1, 2, $descripcionAccion, "fruta_eximateriaprima", $IDEXIMATERIAPRIMA, $_SESSION["ID_USUARIO"], $_SESSION['ID_EMPRESA'], $_SESSION['ID_PLANTA'], $_SESSION['ID_TEMPORADA']);

        $destinatariosCambio = array_values(array_unique(array_filter([$CORREOUSUARIO, 'maperez@fvolcan.cl', 'eisla@fvolcan.cl'])));
        $asuntoCambio = 'Folio materia prima actualizado';
        $textoEstadoRecepcionCambio = $ESTADORECEPCION === null ? 'Sin datos' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? 'Abierta' : 'Cerrada');
        $mensajeCambio = "Se ha realizado un cambio de folio de materia prima." . "\r\n\r\n" .
            "Usuario: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
            "Planta: " . $NOMBREPLANTA . "\r\n" .
            "Empresa: " . $NOMBREEMPRESA . "\r\n" .
            "Temporada: " . $NOMBRETEMPORADA . "\r\n" .
            "Recepción origen: " . ($NUMERORECEPCION ? $NUMERORECEPCION : 'Sin datos') . " (" . $textoEstadoRecepcionCambio . ")\r\n" .
            "Folio actual: " . $FOLIOACTUAL . "\r\n" .
            "Folio nuevo: " . $FOLION . "\r\n" .
            (empty($MOTIVO) ? "" : "Motivo: " . $MOTIVO . "\r\n");

        $remitente = 'informes@volcanfoods.cl';
        $usuarioSMTP = 'informes@volcanfoods.cl';
        $contrasenaSMTP = '1z=EWfu0026k';
        $hostSMTP = 'mail.volcanfoods.cl';
        $puertoSMTP = 465;

        [$envioCambioOk, $errorEnvioCambio] = enviarCorreoSMTP($destinatariosCambio, $asuntoCambio, $mensajeCambio, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);

        $textoNotificacion = "El folio de materia prima fue modificado correctamente.";
        if (!$envioCambioOk) {
            $textoNotificacion .= " No se pudo enviar la notificación por correo: " . ($errorEnvioCambio ?: 'revise la configuración SMTP.');
        }

        $_SESSION['GESTION_FOLIO_MP_EXITO'] = $textoNotificacion;

        unset($_SESSION['GESTION_FOLIO_MP_CODIGO']);
        unset($_SESSION['GESTION_FOLIO_MP_TIEMPO']);

        header('Location: registroGestionFolioMp.php');
        exit();
    } else {
        $MENSAJE = implode(" ", $errores);
    }
}

?>
<!DOCTYPE html>
<html lang="es">

<head>
    <title>Gestión de folios materia prima</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <script type="text/javascript">
        function validacion() {
            var FOLIOSELECCION = document.getElementById("IDEXIMATERIAPRIMA").value;
            var FOLION = document.getElementById("FOLION").value;
            var CODIGO = document.getElementById("CODIGO").value;

            document.getElementById('val_folio').innerHTML = "";
            document.getElementById('val_fn').innerHTML = "";
            document.getElementById('val_codigo').innerHTML = "";

            if (FOLIOSELECCION == null || FOLIOSELECCION.length == 0 || /^\s+$/.test(FOLIOSELECCION)) {
                document.form_reg_dato.IDEXIMATERIAPRIMA.focus();
                document.getElementById('val_folio').innerHTML = "Seleccione un folio en existencia";
                return false;
            }

            if (FOLION == null || FOLION.length == 0 || /^\s+$/.test(FOLION)) {
                document.form_reg_dato.FOLION.focus();
                document.form_reg_dato.FOLION.style.borderColor = "#FF0000";
                document.getElementById('val_fn').innerHTML = "No ha ingresado folio nuevo";
                return false;
            }
            document.form_reg_dato.FOLION.style.borderColor = "#4AF575";

            if (FOLION.length > 10) {
                document.form_reg_dato.FOLION.focus();
                document.form_reg_dato.FOLION.style.borderColor = "#FF0000";
                document.getElementById('val_fn').innerHTML = "No se permiten más de 10 dígitos";
                return false;
            }
            document.form_reg_dato.FOLION.style.borderColor = "#4AF575";

            if (CODIGO == null || CODIGO.length == 0 || /^\s+$/.test(CODIGO)) {
                document.form_reg_dato.CODIGO.focus();
                document.form_reg_dato.CODIGO.style.borderColor = "#FF0000";
                document.getElementById('val_codigo').innerHTML = "Debe ingresar el código de autorización";
                return false;
            }
            document.form_reg_dato.CODIGO.style.borderColor = "#4AF575";

        }

        function textoEstadoRecepcion(estado) {
            if (estado === null || estado === '' || typeof estado === 'undefined') {
                return 'Sin datos';
            }
            return (estado === '1' || estado === 1) ? 'Abierta' : 'Cerrada';
        }

        function alternarAccionesPorEstado(estadoRegistro, tieneFolio) {
            var botonDeshabilitar = document.getElementById("btnDeshabilitar");
            var botonCambiar = document.getElementById("btnCambiar");

            botonDeshabilitar.style.display = 'none';
            botonCambiar.style.display = 'none';

            if (!tieneFolio) {
                return;
            }

            if (estadoRegistro === 1) {
                botonCambiar.style.display = 'flex';
                botonDeshabilitar.style.display = 'flex';
            } else {
                botonCambiar.style.display = 'flex';
            }
        }

        function actualizarFolio() {
            var seleccion = document.getElementById("IDEXIMATERIAPRIMA");
            var folioActual = seleccion.options[seleccion.selectedIndex].getAttribute('data-folio');
            var numeroRecepcion = seleccion.options[seleccion.selectedIndex].getAttribute('data-nrecepcion');
            var estadoRecepcion = seleccion.options[seleccion.selectedIndex].getAttribute('data-estado');
            var estadoRegistro = parseInt(seleccion.options[seleccion.selectedIndex].getAttribute('data-estadoregistro'), 10);
            document.getElementById("FOLIO").value = folioActual ? folioActual : "";
            var textoRecepcion = numeroRecepcion ? ("Recepción " + numeroRecepcion + " (" + textoEstadoRecepcion(estadoRecepcion) + ")") : "Recepción no disponible";
            document.getElementById("INFO_RECEPCION").value = textoRecepcion;

            if (isNaN(estadoRegistro)) {
                estadoRegistro = parseInt(document.getElementById("FILTRO_ESTADO").value, 10) || 0;
            }

            alternarAccionesPorEstado(estadoRegistro, !!folioActual);
        }

        document.addEventListener('DOMContentLoaded', function () {
            actualizarFolio();

            <?php if ($MENSAJEEXITO) { ?>
            Swal.fire({
                icon: "success",
                title: "Operación realizada",
                text: "<?php echo htmlspecialchars($MENSAJEEXITO, ENT_QUOTES, 'UTF-8'); ?>",
                showConfirmButton: true,
                confirmButtonText: "Cerrar"
            });
            <?php } ?>
        });
    </script>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuFruta.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Gestión de folios</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Gestión de folios</li>
                                        <li class="breadcrumb-item active" aria-current="page">Materia prima</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>
                <section class="content">
                    <div class="box box-solid box-bordered">
                        <div class="box-header with-border bg-warning">
                            <h4 class="box-title text-white">Cambiar folio de materia prima</h4>
                        </div>
                        <form class="form" role="form" method="post" name="form_reg_dato" id="form_reg_dato">
                            <div class="box-body form-element">
                                <?php if ($MENSAJE) { ?>
                                    <div class="alert alert-danger" role="alert">
                                        <?php echo htmlspecialchars($MENSAJE, ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                <?php } ?>
                                <div class="row">
                                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Filtrar folios</label>
                                            <select class="form-control select2" name="FILTRO_ESTADO" id="FILTRO_ESTADO" onchange="this.form.submit();" style="width: 100%">
                                                <option value="1" <?php echo $FILTROESTADO == 1 ? 'selected' : ''; ?>>Mostrar folios activos</option>
                                                <option value="0" <?php echo $FILTROESTADO == 0 ? 'selected' : ''; ?>>Mostrar folios eliminados</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Folio en existencia</label>
                                            <select class="form-control select2" id="IDEXIMATERIAPRIMA" name="IDEXIMATERIAPRIMA" style="width: 100%" onchange="actualizarFolio();">
                                                <option value="">Seleccione un folio</option>
                                                <?php foreach ($ARRAYEXISTENCIA as $r) : ?>
                                                    <option value="<?php echo $r['ID_EXIMATERIAPRIMA']; ?>" data-folio="<?php echo htmlspecialchars($r['FOLIO_EXIMATERIAPRIMA'], ENT_QUOTES, 'UTF-8'); ?>" data-nrecepcion="<?php echo htmlspecialchars($r['NUMERO_RECEPCION'], ENT_QUOTES, 'UTF-8'); ?>" data-estado="<?php echo htmlspecialchars($r['ESTADO_RECEPCION'], ENT_QUOTES, 'UTF-8'); ?>" data-estadoregistro="<?php echo htmlspecialchars($r['ESTADO_REGISTRO'], ENT_QUOTES, 'UTF-8'); ?>" <?php echo $IDEXIMATERIAPRIMA == $r['ID_EXIMATERIAPRIMA'] ? 'selected' : ''; ?>>
                                                        <?php echo $r['FOLIO_EXIMATERIAPRIMA']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <label id="val_folio" class="validacion"> </label>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Folio actual</label>
                                            <input type="number" class="form-control" placeholder="Folio actual" id="FOLIO" name="FOLIO" value="<?php echo htmlspecialchars($FOLIO, ENT_QUOTES, 'UTF-8'); ?>" disabled style='background-color: #eeeeee;' />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Recepción origen</label>
                                            <input type="text" class="form-control" id="INFO_RECEPCION" value="<?php echo htmlspecialchars($NUMERORECEPCION ? ('Recepción ' . $NUMERORECEPCION . ' (' . ($ESTADORECEPCION === null ? 'Sin datos' : (($ESTADORECEPCION == 1 || $ESTADORECEPCION === "1") ? 'Abierta' : 'Cerrada')) . ')') : 'Recepción no disponible', ENT_QUOTES, 'UTF-8'); ?>" disabled style='background-color: #eeeeee;' />
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Folio nuevo</label>
                                            <input type="number" class="form-control" placeholder="Folio nuevo" id="FOLION" name="FOLION" value="<?php echo htmlspecialchars($FOLION, ENT_QUOTES, 'UTF-8'); ?>" />
                                            <label id="val_fn" class="validacion"> <?php echo htmlspecialchars($MENSAJE, ENT_QUOTES, 'UTF-8'); ?></label>
                                        </div>
                                    </div>
                                    <div class="col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12">
                                        <div class="form-group">
                                            <label>Código de autorización</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" placeholder="Ingrese código enviado" id="CODIGO" name="CODIGO" value="<?php echo htmlspecialchars($CODIGO, ENT_QUOTES, 'UTF-8'); ?>" />
                                                <div class="input-group-append">
                                                    <button type="submit" class="btn btn-primary btn-rounded" name="SOLICITAR" value="SOLICITAR">Solicitar código</button>
                                                </div>
                                            </div>
                                            <label id="val_codigo" class="validacion"> </label>
                                            <?php if ($MENSAJEENVIO) { ?>
                                                <small class="text-success"><?php echo htmlspecialchars($MENSAJEENVIO, ENT_QUOTES, 'UTF-8'); ?></small>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                        <label>Motivo</label>
                                        <textarea class="form-control" rows="1" placeholder="Motivo del cambio" id="MOTIVO" name="MOTIVO"><?php echo htmlspecialchars($MOTIVO, ENT_QUOTES, 'UTF-8'); ?></textarea>
                                    </div>
                                </div>
                                <div class="box-footer">
                                    <div class="btn-group btn-rounded btn-block col-xxl-6 col-xl-6 col-lg-6 col-md-6 col-sm-12 col-12 col-xs-12" role="group" aria-label="Acciones generales">
                                        <button type="button" class="btn btn-success btn-rounded d-flex align-items-center justify-content-center" data-toggle="tooltip" title="Volver" name="CANCELAR" value="CANCELAR" Onclick="irPagina('index.php');">
                                            <i class="ti-back-left mr-2"></i>
                                            <span>Volver</span>
                                        </button>
                                        <button type="submit" class="btn btn-danger btn-rounded d-flex align-items-center justify-content-center" id="btnDeshabilitar" data-toggle="tooltip" title="Cambiar y deshabilitar" name="DESHABILITAR" value="DESHABILITAR" onclick="return validacion();" style="<?php echo $MOSTRARDESHABILITAR ? '' : 'display:none;'; ?>">
                                            <i class="ti-close mr-2"></i>
                                            <span>Cambiar y deshabilitar folio</span>
                                        </button>
                                        <button type="submit" class="btn btn-warning btn-rounded d-flex align-items-center justify-content-center" id="btnCambiar" data-toggle="tooltip" title="Cambiar número" name="CAMBIAR" value="CAMBIAR" onclick="return validacion()" style="<?php echo $MOSTRARCAMBIAR ? '' : 'display:none;'; ?>">
                                            <i class="ti-save-alt mr-2"></i>
                                            <span>Cambiar número de folio</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="box box-solid box-bordered">
                        <div class="box-header with-border">
                            <h4 class="box-title">Historial últimos 10 cambios de folio</h4>
                        </div>
                        <div class="box-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Acción</th>
                                            <th>Folio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($ARRAYHISTORIAL) : ?>
                                            <?php foreach ($ARRAYHISTORIAL as $historial) : ?>
                                                <?php $detalleHistorial = mapearDetalleHistorialFolio($historial['MENSAJE']); ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars(date('d-m-Y H:i', strtotime($historial['INGRESO'])), ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td>
                                                        <?php
                                                        $nombreUsuarioHist = trim($historial['NOMBRE_COMPLETO']);
                                                        echo htmlspecialchars($nombreUsuarioHist ? $nombreUsuarioHist : $historial['USUARIO'], ENT_QUOTES, 'UTF-8');
                                                        ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($detalleHistorial['accion'], ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td><span class="badge bg-primary"><?php echo htmlspecialchars($detalleHistorial['folio_antiguo'], ENT_QUOTES, 'UTF-8'); ?></span> <i class="ti-arrow-right"></i> <span class="badge bg-info"><?php echo htmlspecialchars($detalleHistorial['folio_nuevo'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else : ?>
                                            <tr>
                                                <td colspan="4">No hay cambios registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
</body>

</html>
