<?php

include_once "../../assest/config/validarUsuarioFruta.php";


//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/TREEMBALAJE_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/ESPECIES_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/REEMBALAJE_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';

include_once '../../assest/controlador/DREXPORTACION_ADO.php';
include_once '../../assest/controlador/DRINDUSTRIAL_ADO.php';
include_once '../../assest/controlador/REEMBALAJE_ADO.php';
include_once '../../assest/modelo/REEMBALAJE.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR

$TREEMBALAJE_ADO =  new TREEMBALAJE_ADO();
$PRODUCTOR_ADO =  new PRODUCTOR_ADO();
$ESPECIES_ADO =  new ESPECIES_ADO();
$VESPECIES_ADO =  new VESPECIES_ADO();
$REEMBALAJE_ADO =  new REEMBALAJE_ADO();
$USUARIO_ADO =  new USUARIO_ADO();
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();
$DREXPORTACION_ADO = new DREXPORTACION_ADO();
$DRINDUSTRIAL_ADO = new DRINDUSTRIAL_ADO();

$REEMBALAJE = new REEMBALAJE();



//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD

$TOTALNETO = "";
$TOTALENVASE = "";
$TOTALEXPORTACION = "";
$TOTALINDUSTRIAL = "";

$MENSAJE = "";
$MENSAJEENVIO = "";
$CORREOUSUARIO = "";
$NOMBRECOMPLETOUSUARIO = $_SESSION['NOMBRE_USUARIO'] ?? '';



//INICIALIZAR ARREGLOS
$ARRAYUSUARIO = $USUARIO_ADO->verUsuario($_SESSION["ID_USUARIO"]);
if ($ARRAYUSUARIO) {
    $CORREOUSUARIO = trim($ARRAYUSUARIO[0]['EMAIL_USUARIO']);
    $NOMBRECOMPLETOUSUARIO = trim(
        ($ARRAYUSUARIO[0]['PNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SNOMBRE_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['PAPELLIDO_USUARIO'] ?? '') . ' ' .
        ($ARRAYUSUARIO[0]['SAPELLIDO_USUARIO'] ?? '')
    );
    $NOMBRECOMPLETOUSUARIO = trim($NOMBRECOMPLETOUSUARIO) ?: ($_SESSION['NOMBRE_USUARIO'] ?? '');
}

function generarCodigoAutorizacion()
{
    if (function_exists('random_int')) {
        return random_int(100000, 999999);
    }

    return mt_rand(100000, 999999);
}

function obtenerDestinatariosAutorizacion($correoSolicitante)
{
    $correosBase = ['maperez@fvolcan.cl', 'eisla@fvolcan.cl', 'msanhueza@fvolcan.cl'];
    $correoSolicitante = trim((string) $correoSolicitante);

    if ($correoSolicitante !== '') {
        $correosBase = array_filter(
            $correosBase,
            fn($correo) => strcasecmp($correo, $correoSolicitante) !== 0
        );
    }

    return array_values(array_filter(array_unique($correosBase)));
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
            "To: " . implode(', ', $destinatarios) . "\r\n" .
            "Subject: {$asunto}\r\n" .
            "MIME-Version: 1.0\r\n" .
            "Content-Type: text/plain; charset=UTF-8\r\n" .
            "Content-Transfer-Encoding: 8bit\r\n\r\n";

        $comando($cabeceras . $mensaje . "\r\n.\r\n", '250');
    } catch (Exception $e) {
        fclose($conexion);
        return [false, "Error durante el envío SMTP: " . $e->getMessage()];
    }

    try {
        $comando('QUIT', '221');
    } catch (Exception $e) {
        // Algunos servidores cierran la conexión antes de procesar QUIT; no es un error crítico.
    }

    fclose($conexion);
    return [true, null];
}

function obtenerDatosCorreoReembalaje($reembalaje, $TREEMBALAJE_ADO, $PRODUCTOR_ADO, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO)
{
    if (!$reembalaje) {
        return [
            'numero' => '',
            'fecha' => '',
            'tipo' => '',
            'productor' => '',
            'empresa' => '',
            'planta' => '',
            'temporada' => '',
        ];
    }

    $numero = $reembalaje['NUMERO_REEMBALAJE'] ?? '';
    $fecha = $reembalaje['FECHA'] ?? ($reembalaje['FECHA_REEMBALAJE'] ?? '');

    $tipo = 'Sin Datos';
    if (!empty($reembalaje['ID_TREEMBALAJE'])) {
        $tipoData = $TREEMBALAJE_ADO->verTreembalaje($reembalaje['ID_TREEMBALAJE']);
        if ($tipoData) {
            $tipo = $tipoData[0]['NOMBRE_TREEMBALAJE'] ?? 'Sin Datos';
        }
    }

    $productor = 'Sin Datos';
    if (!empty($reembalaje['ID_PRODUCTOR'])) {
        $productorData = $PRODUCTOR_ADO->verProductor($reembalaje['ID_PRODUCTOR']);
        if ($productorData) {
            $productor = trim(($productorData[0]['CSG_PRODUCTOR'] ?? '') . ' - ' . ($productorData[0]['NOMBRE_PRODUCTOR'] ?? ''));
            $productor = $productor !== '-' ? $productor : 'Sin Datos';
        }
    }

    $empresa = '';
    if (!empty($reembalaje['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($reembalaje['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'] ?? '';
        }
    }

    $planta = '';
    if (!empty($reembalaje['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($reembalaje['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'] ?? '';
        }
    }

    $temporada = '';
    if (!empty($reembalaje['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($reembalaje['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'] ?? '';
        }
    }

    return [
        'numero' => $numero,
        'fecha' => $fecha,
        'tipo' => $tipo,
        'productor' => $productor,
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
    ];
}

if ($_POST) {
    $IDREEMBALAJE = $_REQUEST['ID'] ?? null;
    $CODIGOELIMINAR = trim($_REQUEST['CODIGO_ELIMINAR'] ?? '');
    $CODIGOAPERTURA = trim($_REQUEST['CODIGO_ABRIR'] ?? '');

    $detalleReembalaje = $IDREEMBALAJE ? $REEMBALAJE_ADO->verReembalaje2($IDREEMBALAJE) : [];
    $datosReembalaje = $detalleReembalaje ? $detalleReembalaje[0] : [];
    $datosCorreo = obtenerDatosCorreoReembalaje($datosReembalaje, $TREEMBALAJE_ADO, $PRODUCTOR_ADO, $EMPRESA_ADO, $PLANTA_ADO, $TEMPORADA_ADO);

    if (isset($_REQUEST['SOLICITARELIMINAR'])) {
        if (!$IDREEMBALAJE) {
            $MENSAJE = "No se ha seleccionado un reembalaje válido.";
        } elseif (!$datosReembalaje || ($datosReembalaje['ESTADO'] ?? null) != 1) {
            $MENSAJE = "Solo se pueden solicitar eliminaciones para reembalajes abiertos.";
        } elseif (!empty($DREXPORTACION_ADO->buscarPorReembalaje($IDREEMBALAJE)) || !empty($DRINDUSTRIAL_ADO->buscarPorReembalaje($IDREEMBALAJE))) {
            $MENSAJE = "No se puede solicitar eliminación: el reembalaje tiene registros de salida o entrada.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['REEMBALAJE_ELIMINAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['REEMBALAJE_ELIMINAR_ID'] = $IDREEMBALAJE;
            $_SESSION['REEMBALAJE_ELIMINAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación reembalaje #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la eliminación de un reembalaje." . "\r\n\r\n" .
                "Número de reembalaje: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de reembalaje: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Solicitado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
                "Código de autorización: " . $codigoAutorizacion . "\r\n\r\n" .
                "El código tiene validez de 15 minutos.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if ($envioOk) {
                $MENSAJEENVIO = "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla.";
            } else {
                $MENSAJE = $errorEnvio ?: "No fue posible enviar el correo de autorización.";
            }
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ELIMINAR'])) {
        $codigoSesion = $_SESSION['REEMBALAJE_ELIMINAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['REEMBALAJE_ELIMINAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['REEMBALAJE_ELIMINAR_TIEMPO'] ?? 0;

        if (!$IDREEMBALAJE) {
            $MENSAJE = "No se ha seleccionado un reembalaje válido.";
        } elseif (!$datosReembalaje || ($datosReembalaje['ESTADO'] ?? null) != 1) {
            $MENSAJE = "Solo se pueden eliminar reembalajes abiertos.";
        } elseif (!empty($DREXPORTACION_ADO->buscarPorReembalaje($IDREEMBALAJE)) || !empty($DRINDUSTRIAL_ADO->buscarPorReembalaje($IDREEMBALAJE))) {
            $MENSAJE = "No se puede eliminar el reembalaje porque tiene registros de salida o entrada.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDREEMBALAJE) {
            $MENSAJE = "No hay una solicitud de eliminación vigente para este reembalaje.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOELIMINAR || $CODIGOELIMINAR != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $REEMBALAJE->__SET('ID_REEMBALAJE', $IDREEMBALAJE);
            $REEMBALAJE_ADO->deshabilitar($REEMBALAJE);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación reembalaje #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación del reembalaje." . "\r\n\r\n" .
                "Número de reembalaje: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de reembalaje: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado del registro fue desactivado.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Reembalaje eliminado (estado de registro desactivado)." : ($errorEnvio ?: "El reembalaje se eliminó pero hubo un problema al enviar la notificación.");
            unset($_SESSION['REEMBALAJE_ELIMINAR_CODIGO']);
            unset($_SESSION['REEMBALAJE_ELIMINAR_ID']);
            unset($_SESSION['REEMBALAJE_ELIMINAR_TIEMPO']);
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        if (!$IDREEMBALAJE) {
            $MENSAJE = "No se ha seleccionado un reembalaje válido.";
        } elseif (!$datosReembalaje || ($datosReembalaje['ESTADO'] ?? null) != 0) {
            $MENSAJE = "Solo se pueden solicitar aperturas para reembalajes cerrados.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['REEMBALAJE_ABRIR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['REEMBALAJE_ABRIR_ID'] = $IDREEMBALAJE;
            $_SESSION['REEMBALAJE_ABRIR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización apertura reembalaje #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la apertura de un reembalaje." . "\r\n\r\n" .
                "Número de reembalaje: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de reembalaje: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Solicitado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n" .
                "Código de autorización: " . $codigoAutorizacion . "\r\n\r\n" .
                "El código tiene validez de 15 minutos.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if ($envioOk) {
                $MENSAJEENVIO = "Código de autorización enviado correctamente a Maria de los Ángeles y Erwin Isla.";
            } else {
                $MENSAJE = $errorEnvio ?: "No fue posible enviar el correo de autorización.";
            }
        }
    }

    if (isset($_REQUEST['CONFIRMAR_ABRIR'])) {
        $codigoSesion = $_SESSION['REEMBALAJE_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['REEMBALAJE_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['REEMBALAJE_ABRIR_TIEMPO'] ?? 0;

        if (!$IDREEMBALAJE) {
            $MENSAJE = "No se ha seleccionado un reembalaje válido.";
        } elseif (!$datosReembalaje || ($datosReembalaje['ESTADO'] ?? null) != 0) {
            $MENSAJE = "Solo se pueden abrir reembalajes cerrados.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDREEMBALAJE) {
            $MENSAJE = "No hay una solicitud de apertura vigente para este reembalaje.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $REEMBALAJE->__SET('ID_REEMBALAJE', $IDREEMBALAJE);
            $REEMBALAJE_ADO->habilitar($REEMBALAJE);
            $REEMBALAJE_ADO->abierto($REEMBALAJE);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura reembalaje #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura del reembalaje." . "\r\n\r\n" .
                "Número de reembalaje: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de reembalaje: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo: " . $datosCorreo['tipo'] . "\r\n" .
                "Productor: " . $datosCorreo['productor'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado del reembalaje cambió a abierto.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Reembalaje abierto correctamente." : ($errorEnvio ?: "El reembalaje se abrió pero hubo un problema al enviar la notificación.");
            unset($_SESSION['REEMBALAJE_ABRIR_CODIGO']);
            unset($_SESSION['REEMBALAJE_ABRIR_ID']);
            unset($_SESSION['REEMBALAJE_ABRIR_TIEMPO']);
        }
    }
}
$ARRAYEMPRESA = "";
$ARRAYFOLIO2 = "";
$ARRAYPVESPECIES = "";
$ARRAYTREEMBALAJE = "";
$ARRAYPRODUCTOR = "";
$ARRAYVESPECIES = "";

$ARRAYREEMBALAJE = "";
$ARRAYTOTALREEMBALAJE = "";


//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES

if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYREEMBALAJE = $REEMBALAJE_ADO->listarReembalajeEmpresaPlantaTemporadaCBX($EMPRESAS, $PLANTAS, $TEMPORADAS);
}
include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";

?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Reembalaje</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <!- LLAMADA DE LOS ARCHIVOS NECESARIOS PARA DISEÑO Y FUNCIONES BASE DE LA VISTA -!>
        <?php include_once "../../assest/config/urlHead.php"; ?>
        <!- FUNCIONES BASES -!>
            <script type="text/javascript">
                //REDIRECCIONAR A LA PAGINA SELECIONADA
                function irPagina(url) {
                    location.href = "" + url;
                }

             


                function abrirPestana(url) {
                    var win = window.open(url, '_blank');
                    win.focus();
                }

                //FUNCION PARA ABRIR VENTANA QUE SE ENCUENTRA LA OPERACIONES DE DETALLE DE RECEPCION
                function abrirVentana(url) {
                    var opciones =
                        "'directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=1000, height=800'";
                    window.open(url, 'window', opciones);
                }
            </script>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary" >
    <div class="wrapper">
        <?php include_once "../../assest/config/menuFruta.php"; ?>
        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <div class="container-full">
                <!-- Content Header (Page header) -->
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Packing</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Modulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Packing</li>
                                        <li class="breadcrumb-item" aria-current="page">Reembalaje</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Agrupado Reembalaje </a>  </li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>
                <!-- Main content -->
                <section class="content">
                    <div class="box">
                        <div class="box-body">
                            <?php if ($MENSAJE) { ?>
                                <div class="alert alert-danger" role="alert">
                                    <?php echo $MENSAJE; ?>
                                </div>
                            <?php } ?>
                            <?php if ($MENSAJEENVIO) { ?>
                                <div class="alert alert-success" role="alert">
                                    <?php echo $MENSAJEENVIO; ?>
                                </div>
                            <?php } ?>
                            <div class="row">
                                <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                    <div class="table-responsive">
                                        <table id="reembalaje" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th>Numero</th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Operaciónes</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Fecha Reembalaje</th>
                                                    <th>Tipo Reembalaje</th>
                                                    <th>Turno </th>
                                                    <th>CSG Productor</th>
                                                    <th>Nombre Productor</th>
                                                    <th>Especie</th>
                                                    <th>Variedad</th>
                                                    <th>Kg. Con Desh. Entrada</th>
                                                    <th>Kg. Neto Expo.</th>
                                                    <th>Kg. Deshi. </th>
                                                    <th>Kg. Con Deshi. </th>
                                                    <th>Kg. IQF</th>
                                                    <th>Kg. Merma/Desecho</th>
                                                    <th>Kg. Industrial</th>
                                                    <th>Kg. Diferencia</th>
                                                    <th>% Exportación</th>
                                                    <th>% Deshitación</th>
                                                    <th>% Industrial</th>
                                                    <th>% Total</th>
                                                    <th>PT Embolsado</th>       
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificacion</th>
                                                    <th>Semana Reembalaje</th>
                                                    <th>Empresa</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYREEMBALAJE as $r) : ?>

                                                    <?php

                                                    $ARRAYTOTALENVASESEMBOLSADO=$REEMBALAJE_ADO->obtenerTotalEnvasesEmbolsado($r['ID_REEMBALAJE']);
                                                    $ENVASESEMBOLSADO=$ARRAYTOTALENVASESEMBOLSADO[0]["ENVASE"];

                                                    $ARRAYVERVESPECIESID = $VESPECIES_ADO->verVespecies($r['ID_VESPECIES']);
                                                    if ($ARRAYVERVESPECIESID) {
                                                        $NOMBREVESPECIES = $ARRAYVERVESPECIESID[0]['NOMBRE_VESPECIES'];
                                                        $ARRAYVERESPECIESID = $ESPECIES_ADO->verEspecies($ARRAYVERVESPECIESID[0]['ID_ESPECIES']);
                                                        if ($ARRAYVERVESPECIESID) {
                                                            $NOMBRESPECIES = $ARRAYVERESPECIESID[0]['NOMBRE_ESPECIES'];
                                                        } else {
                                                            $NOMBRESPECIES = "Sin Datos";
                                                        }
                                                    } else {
                                                        $NOMBREVESPECIES = "Sin Datos";
                                                        $NOMBRESPECIES = "Sin Datos";
                                                    }
                                                    $ARRAYVERPRODUCTORID = $PRODUCTOR_ADO->verProductor($r['ID_PRODUCTOR']);
                                                    if ($ARRAYVERPRODUCTORID) {

                                                        $CSGPRODUCTOR = $ARRAYVERPRODUCTORID[0]['CSG_PRODUCTOR'];
                                                        $NOMBREPRODUCTOR = $ARRAYVERPRODUCTORID[0]['NOMBRE_PRODUCTOR'];
                                                    } else {
                                                        $CSGPRODUCTOR = "Sin Datos";
                                                        $NOMBREPRODUCTOR = "Sin Datos";
                                                    }
                                                    $ARRAYTREEMBALAJE = $TREEMBALAJE_ADO->verTreembalaje($r['ID_TREEMBALAJE']);
                                                    if ($ARRAYTREEMBALAJE) {
                                                        $TREEMBALAJE = $ARRAYTREEMBALAJE[0]['NOMBRE_TREEMBALAJE'];
                                                    } else {
                                                        $TREEMBALAJE = "Sin Datos";
                                                    }
                                                    if ($r['TURNO']) {
                                                        if ($r['TURNO'] == "1") {
                                                            $TURNO = "Dia";
                                                        }
                                                        if ($r['TURNO'] == "2") {
                                                            $TURNO = "Noche";
                                                        }
                                                    } else {
                                                        $TURNO = "Sin Datos";
                                                    }
                                                    $ARRAYEMPRESA = $EMPRESA_ADO->verEmpresa($r['ID_EMPRESA']);
                                                    if ($ARRAYEMPRESA) {
                                                        $NOMBREEMPRESA = $ARRAYEMPRESA[0]['NOMBRE_EMPRESA'];
                                                    } else {
                                                        $NOMBREEMPRESA = "Sin Datos";
                                                    }
                                                    $ARRAYPLANTA = $PLANTA_ADO->verPlanta($r['ID_PLANTA']);
                                                    if ($ARRAYPLANTA) {
                                                        $NOMBREPLANTA = $ARRAYPLANTA[0]['NOMBRE_PLANTA'];
                                                    } else {
                                                        $NOMBREPLANTA = "Sin Datos";
                                                    }
                                                    $ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($r['ID_TEMPORADA']);
                                                    if ($ARRAYTEMPORADA) {
                                                        $NOMBRETEMPORADA = $ARRAYTEMPORADA[0]['NOMBRE_TEMPORADA'];
                                                    } else {
                                                        $NOMBRETEMPORADA = "Sin Datos";
                                                    }


                                                    ?>

                                                    <tr class="center">
                                                        <td> <?php echo $r['NUMERO_REEMBALAJE']; ?> </td>
                                                        <td>
                                                            <?php if ($r['ESTADO'] == "0") { ?>
                                                                <button type="button" class="btn btn-block btn-danger">Cerrado</button>
                                                            <?php  }  ?>
                                                            <?php if ($r['ESTADO'] == "1") { ?>
                                                                <button type="button" class="btn btn-block btn-success">Abierto</button>
                                                            <?php  }  ?>
                                                        </td>

                                                        <td class="text-center">
                                                            <form method="post" id="form1">
                                                                <div class="list-icons d-inline-flex">
                                                                    <div class="list-icons-item dropdown">
                                                                        <button class="btn btn-secondary" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                            <i class="glyphicon glyphicon-cog"></i>
                                                                        </button>
                                                                        <div class="dropdown-menu dropdown-menu-right">
                                                                            <button class="dropdown-menu" aria-labelledby="dropdownMenuButton"></button>
                                                                            <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_REEMBALAJE']; ?>" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroReembalajeEx" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarReembalajeEx" />
                                                                            <?php if ($r['ESTADO'] == "0") { ?>
                                                                                <span href="#" class="dropdown-item" data-toggle="tooltip" title="Ver">
                                                                                    <button type="submit" class="btn btn-info btn-block " id="VERURL" name="VERURL">
                                                                                        <i class="ti-eye"></i> Ver
                                                                                    </button>
                                                                                </span>
                                                                            <?php } ?>
                                                                            <?php if ($r['ESTADO'] == "1") { ?>
                                                                                <span href="#" class="dropdown-item" data-toggle="tooltip" title="Editar">
                                                                                    <button type="submit" class="btn  btn-warning btn-block" id="EDITARURL" name="EDITARURL">
                                                                                        <i class="ti-pencil-alt"></i> Editar
                                                                                    </button>
                                                                                </span>
                                                                            <?php } ?>
                                                                            <hr>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Informe" <?php if ($r['ESTADO'] == "1") { echo "disabled"; } ?> Onclick="abrirPestana('../../assest/documento/informeReembalajeEx.php?parametro=<?php echo $r['ID_REEMBALAJE']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Tarjas">
                                                                                <button type="button" class="btn  btn-danger btn-block" id="defecto" name="tarjas" title="Tarjas" Onclick="abrirPestana('../../assest/documento/informeTarjasReembalajeEx.php?parametro=<?php echo $r['ID_REEMBALAJE']; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Tarjas
                                                                                </button>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </form>
                                                        </td>
                                                        <td class="text-center">
                                                            <div class="d-grid gap-1">
                                                                <?php if ($r['ESTADO'] == "1") { ?>
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalEliminarReembalaje" data-id="<?php echo $r['ID_REEMBALAJE']; ?>" data-numero="<?php echo $r['NUMERO_REEMBALAJE']; ?>">
                                                                        Eliminar reembalaje
                                                                    </button>
                                                                <?php } ?>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-outline-success btn-sm btn-block" data-toggle="modal" data-target="#modalAbrirReembalaje" data-id="<?php echo $r['ID_REEMBALAJE']; ?>" data-numero="<?php echo $r['NUMERO_REEMBALAJE']; ?>">
                                                                        Abrir reembalaje
                                                                    </button>
                                                                <?php } ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $r['FECHA']; ?></td>
                                                        <td><?php echo $TREEMBALAJE; ?> </td>
                                                        <td><?php echo $TURNO; ?> </td>
                                                        <td><?php echo $CSGPRODUCTOR; ?></td>
                                                        <td><?php echo $NOMBREPRODUCTOR; ?></td>
                                                        <td><?php echo $NOMBRESPECIES; ?></td>
                                                        <td><?php echo $NOMBREVESPECIES; ?></td>
                                                        <td><?php echo $r['ENTRADA']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['EXPORTACION']-$r['NETO']; ?></td>
                                                        <td><?php echo $r['EXPORTACION']; ?></td>
                                                        <td><?php echo $r['INDUSTRIALSC']; ?></td>
                                                        <td><?php echo $r['INDUSTRIALNC']; ?></td>
                                                        <td><?php echo $r['INDUSTRIAL']; ?></td>
                                                        <td><?php echo number_format( $r['ENTRADA']-$r['EXPORTACION']-$r['INDUSTRIAL'],2,".",""); ?></td>
                                                        <td><?php echo $r['PDEXPORTACION_REEMBALAJE']; ?></td>
                                                        <td><?php echo $r['PDEXPORTACIONCD_REEMBALAJE']-$r['PDEXPORTACION_REEMBALAJE']; ?></td>
                                                        <td><?php echo $r['PDINDUSTRIAL_REEMBALAJE']; ?></td>
                                                        <td><?php echo number_format($r['PORCENTAJE_REEMBALAJE'], 2, ",", ".");  ?></td>
                                                        <td><?php echo $ENVASESEMBOLSADO; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td><?php echo $r['SEMANA']; ?></td>
                                                        <td><?php echo $NOMBREEMPRESA; ?></td>
                                                        <td><?php echo $NOMBREPLANTA; ?></td>
                                                        <td><?php echo $NOMBRETEMPORADA; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>                                              
                        <div class="box-footer">
                                <div class="btn-toolbar mb-3" role="toolbar" aria-label="Datos generales">
                                    <div class="form-row align-items-center" role="group" aria-label="Datos">
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Kg. Neto Entrada</div>
                                                    <button class="btn   btn-default" id="TOTALNETOEV" name="TOTALNETOEV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Kg. Neto Expo</div>
                                                    <button class="btn   btn-default" id="TOTALNETOEXPOV" name="TOTALNETOEXPOV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Kg. Con Deshi.</div>
                                                    <button class="btn   btn-default" id="TOTALNETOEXPODV" name="TOTALNETOEXPODV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Kg. Industrial</div>
                                                    <button class="btn   btn-default" id="TOTALNETOINDV" name="TOTALNETOINDV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                        </div>    
                    </div>
                    <!-- /.box -->
                </section>
                <!-- /.content -->
            </div>
        </div>
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
        <!-- Modal Eliminar Reembalaje -->
        <div class="modal fade" id="modalEliminarReembalaje" tabindex="-1" role="dialog" aria-labelledby="modalEliminarReembalajeLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEliminarReembalajeLabel">Autorización para eliminar reembalaje</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Se enviará un código de confirmación a Maria de los Ángeles y Erwin Isla. Ingrésalo para completar la eliminación.</p>
                            <p class="font-weight-bold">Reembalaje N° <span class="numero-reembalaje-eliminar"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroReembalajeEx">
                            <input type="hidden" name="URLO" value="listarReembalajeEx">
                            <div class="form-group">
                                <label for="codigoEliminarReembalaje">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoEliminarReembalaje" name="CODIGO_ELIMINAR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-danger" name="SOLICITARELIMINAR">Solicitar código</button>
                            <button type="submit" class="btn btn-danger" name="CONFIRMAR_ELIMINAR">Eliminar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <!-- Modal Abrir Reembalaje -->
        <div class="modal fade" id="modalAbrirReembalaje" tabindex="-1" role="dialog" aria-labelledby="modalAbrirReembalajeLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAbrirReembalajeLabel">Autorización para abrir reembalaje</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Solicita y confirma el código enviado a Maria de los Ángeles y Erwin Isla para abrir el reembalaje cerrado.</p>
                            <p class="font-weight-bold">Reembalaje N° <span class="numero-reembalaje-abrir"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroReembalajeEx">
                            <input type="hidden" name="URLO" value="listarReembalajeEx">
                            <div class="form-group">
                                <label for="codigoAbrirReembalaje">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoAbrirReembalaje" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-success" name="SOLICITAR_ABRIR">Solicitar código</button>
                            <button type="submit" class="btn btn-success" name="CONFIRMAR_ABRIR">Abrir reembalaje</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <script>
            const Toast = Swal.mixin({
                toast: true,
                position: 'top',
                showConfirmButton: false,
                showConfirmButton: true
            })

            Toast.fire({
                icon: "info",
                title: "Informacion importante",
                html: "<label>Los <b>reembalajes</b> Abiertos tienen que ser <b>Cerrados</b> para no afectar las operaciones posteriores.</label>"
            })
        </script>
        <script>
            $(document).ready(function () {
                $('#modalEliminarReembalaje').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idReembalaje = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idReembalaje);
                    modal.find('.numero-reembalaje-eliminar').text(numero || '');
                });

                $('#modalAbrirReembalaje').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idReembalaje = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idReembalaje);
                    modal.find('.numero-reembalaje-abrir').text(numero || '');
                });
            });
        </script>
</body>

</html>