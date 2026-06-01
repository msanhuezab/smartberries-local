Despacho de Descarte(R)	<?php

include_once "../../assest/config/validarUsuarioFruta.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/ESPECIES_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/TMANEJO_ADO.php';
include_once '../../assest/controlador/TCALIBRE_ADO.php';
include_once '../../assest/controlador/TEMBALAJE_ADO.php';
include_once '../../assest/controlador/EEXPORTACION_ADO.php';
include_once '../../assest/controlador/DFINAL_ADO.php';


include_once '../../assest/controlador/CONDUCTOR_ADO.php';
include_once '../../assest/controlador/TRANSPORTE_ADO.php';
include_once '../../assest/controlador/COMPRADOR_ADO.php';


include_once '../../assest/controlador/TPROCESO_ADO.php';
include_once '../../assest/controlador/TREEMBALAJE_ADO.php';

include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EXIEXPORTACION_ADO.php';
include_once '../../assest/controlador/DESPACHOPT_ADO.php';
include_once '../../assest/controlador/DESPACHOEX_ADO.php';
include_once '../../assest/controlador/RECEPCIONPT_ADO.php';
include_once '../../assest/controlador/REPALETIZAJEEX_ADO.php';
include_once '../../assest/controlador/PROCESO_ADO.php'; 
include_once '../../assest/controlador/REEMBALAJE_ADO.php';
include_once '../../assest/controlador/MGUIAPT_ADO.php';
include_once '../../assest/modelo/DESPACHOPT.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR
$ESPECIES_ADO =  new ESPECIES_ADO();
$VESPECIES_ADO =  new VESPECIES_ADO();
$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$TMANEJO_ADO =  new TMANEJO_ADO();
$TCALIBRE_ADO =  new TCALIBRE_ADO();
$TEMBALAJE_ADO =  new TEMBALAJE_ADO();
$EEXPORTACION_ADO =  new EEXPORTACION_ADO();
$DFINAL_ADO =  new DFINAL_ADO();



$TRANSPORTE_ADO =  new TRANSPORTE_ADO();
$CONDUCTOR_ADO =  new CONDUCTOR_ADO();
$COMPRADOR_ADO =  new COMPRADOR_ADO();


$TPROCESO_ADO =  new TPROCESO_ADO();
$TREEMBALAJE_ADO =  new TREEMBALAJE_ADO();


$DESPACHOPT_ADO =  new DESPACHOPT_ADO();
$DESPACHOEX_ADO =  new DESPACHOEX_ADO();
$EXIEXPORTACION_ADO =  new EXIEXPORTACION_ADO();
$RECEPCIONPT_ADO =  new RECEPCIONPT_ADO();
$REPALETIZAJEEX_ADO =  new REPALETIZAJEEX_ADO();
$PROCESO_ADO =  new PROCESO_ADO();
$REEMBALAJE_ADO =  new REEMBALAJE_ADO();
$MGUIAPT_ADO =  new MGUIAPT_ADO();
$USUARIO_ADO = new USUARIO_ADO();
$DESPACHOPT = new DESPACHOPT();


//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD


$TOTALBRUTO = "";
$TOTALNETO = "";
$TOTALENVASE = "";
$FECHADESDE = "";
$FECHAHASTA = "";

$PRODUCTOR = "";
$NUMEROGUIA = "";
$MENSAJE = "";
$MENSAJEENVIO = "";
$CORREOUSUARIO = "";
$NOMBRECOMPLETOUSUARIO = $_SESSION['NOMBRE_USUARIO'] ?? '';

//INICIALIZAR ARREGLOS
$ARRAYDESPACHOPT = "";
$ARRAYDESPACHOPTTOTALES = "";
$ARRAYVEREMPRESA = "";
$ARRAYVERPRODUCTOR = "";
$ARRAYVERTRANSPORTE = "";
$ARRAYVERCONDUCTOR = "";
$ARRAYMGUIAPT = "";

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

function obtenerDatosCorreoDespacho($despacho, $PRODUCTOR_ADO, $PLANTA_ADO, $COMPRADOR_ADO, $EMPRESA_ADO, $TEMPORADA_ADO)
{
    if (!$despacho) {
        return [
            'numero' => '',
            'fecha' => '',
            'tipo' => '',
            'destino' => '',
            'empresa' => '',
            'planta' => '',
            'temporada' => '',
        ];
    }

    $numero = $despacho['NUMERO_DESPACHO'] ?? '';
    $fecha = $despacho['FECHA_DESPACHO'] ?? '';
    $tipoDespacho = 'Sin Datos';
    $destino = 'Sin Datos';

    switch ($despacho['TDESPACHO'] ?? null) {
        case '1':
            $tipoDespacho = 'Interplanta';
            $plantaDestino = $PLANTA_ADO->verPlanta($despacho['ID_PLANTA2'] ?? null);
            if ($plantaDestino) {
                $destino = $plantaDestino[0]['NOMBRE_PLANTA'];
            }
            break;
        case '2':
            $tipoDespacho = 'Devolución Productor';
            $productor = $PRODUCTOR_ADO->verProductor($despacho['ID_PRODUCTOR'] ?? null);
            if ($productor) {
                $destino = $productor[0]['NOMBRE_PRODUCTOR'];
            }
            break;
        case '3':
            $tipoDespacho = 'Venta';
            $comprador = $COMPRADOR_ADO->verComprador($despacho['ID_COMPRADOR'] ?? null);
            if ($comprador) {
                $destino = $comprador[0]['NOMBRE_COMPRADOR'];
            }
            break;
        case '4':
            $tipoDespacho = 'Despacho Regalo';
            $destino = $despacho['REGALO_DESPACHO'] ?? 'Sin Datos';
            break;
        case '5':
            $tipoDespacho = 'Planta Externa';
            $plantaExt = $PLANTA_ADO->verPlanta($despacho['ID_PLANTA3'] ?? null);
            if ($plantaExt) {
                $destino = $plantaExt[0]['NOMBRE_PLANTA'];
            }
            break;
        case '6':
            $tipoDespacho = 'Despacho Industrial';
            $plantaInd = $PLANTA_ADO->verPlanta($despacho['ID_PLANTA3'] ?? null);
            if ($plantaInd) {
                $destino = $plantaInd[0]['NOMBRE_PLANTA'];
            }
            break;
    }

    $empresa = '';
    if (!empty($despacho['ID_EMPRESA'])) {
        $empresaData = $EMPRESA_ADO->verEmpresa($despacho['ID_EMPRESA']);
        if ($empresaData) {
            $empresa = $empresaData[0]['NOMBRE_EMPRESA'];
        }
    }

    $planta = '';
    if (!empty($despacho['ID_PLANTA'])) {
        $plantaData = $PLANTA_ADO->verPlanta($despacho['ID_PLANTA']);
        if ($plantaData) {
            $planta = $plantaData[0]['NOMBRE_PLANTA'];
        }
    }

    $temporada = '';
    if (!empty($despacho['ID_TEMPORADA'])) {
        $temporadaData = $TEMPORADA_ADO->verTemporada($despacho['ID_TEMPORADA']);
        if ($temporadaData) {
            $temporada = $temporadaData[0]['NOMBRE_TEMPORADA'];
        }
    }

    return [
        'numero' => $numero,
        'fecha' => $fecha,
        'tipo' => $tipoDespacho,
        'destino' => $destino,
        'empresa' => $empresa,
        'planta' => $planta,
        'temporada' => $temporada,
    ];
}

if ($_POST) {
    $IDDESPACHO = $_REQUEST['ID'] ?? null;
    $CODIGOVERIFICACION = $_REQUEST['CODIGO_ELIMINAR'] ?? '';
    $CODIGOAPERTURA = $_REQUEST['CODIGO_ABRIR'] ?? '';

    $detalleDespacho = $IDDESPACHO ? $DESPACHOPT_ADO->verDespachopt($IDDESPACHO) : [];
    $datosDespacho = $detalleDespacho ? $detalleDespacho[0] : [];
    $datosCorreo = obtenerDatosCorreoDespacho($datosDespacho, $PRODUCTOR_ADO, $PLANTA_ADO, $COMPRADOR_ADO, $EMPRESA_ADO, $TEMPORADA_ADO);
    $plantaDespachoNombre = '';
    if (!empty($datosDespacho['ID_PLANTA'])) {
        $plantaDespacho = $PLANTA_ADO->verPlanta($datosDespacho['ID_PLANTA']);
        if ($plantaDespacho) {
            $plantaDespachoNombre = $plantaDespacho[0]['NOMBRE_PLANTA'];
        }
    }
    $numeroDespachoMensaje = $datosDespacho['NUMERO_DESPACHO'] ?? $IDDESPACHO;

    $foliosDespacho = ($IDDESPACHO && $datosDespacho)
        ? $EXIEXPORTACION_ADO->buscarPordespachoDetalle(
            $IDDESPACHO,
            $datosDespacho['ID_EMPRESA'],
            $datosDespacho['ID_PLANTA'],
            $datosDespacho['ID_TEMPORADA'],
            $datosDespacho['TDESPACHO']
        )
        : [];
    $motivosRechazo = $IDDESPACHO ? $MGUIAPT_ADO->listarMguiaDespachoCBX($IDDESPACHO) : [];
    $foliosDespachoActivos = array_filter($foliosDespacho, function ($registro) use ($IDDESPACHO) {
        if (isset($registro['ID_DESPACHO']) && (string) $registro['ID_DESPACHO'] !== (string) $IDDESPACHO) {
            return false;
        }
        return !isset($registro['ESTADO_REGISTRO']) || (int) $registro['ESTADO_REGISTRO'] === 1;
    });
    $motivosRechazoActivos = array_filter($motivosRechazo, function ($registro) use ($IDDESPACHO) {
        if (isset($registro['ID_DESPACHO']) && (string) $registro['ID_DESPACHO'] !== (string) $IDDESPACHO) {
            return false;
        }
        return !isset($registro['ESTADO_REGISTRO']) || (int) $registro['ESTADO_REGISTRO'] === 1;
    });

    if (isset($_REQUEST['SOLICITARELIMINAR'])) {
        if (!$IDDESPACHO) {
            $MENSAJE = "No se ha seleccionado un despacho válido.";
        } elseif ($foliosDespachoActivos) {
            $detalleAsociado = '';
            if ($foliosDespachoActivos) {
                $folio = reset($foliosDespachoActivos);
                $folioNumero = $folio['FOLIO_AUXILIAR_EXIEXPORTACION'] ?? $folio['ID_EXIEXPORTACION'] ?? 'Sin Datos';
                $detalleAsociado = "Folios PT asociados: " . $folioNumero;
            }
            $MENSAJE = "El despacho N° " . $numeroDespachoMensaje . " no puede eliminarse porque tiene registros asociados. " . $detalleAsociado . ".";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['DESPACHOPT_ELIMINAR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['DESPACHOPT_ELIMINAR_ID'] = $IDDESPACHO;
            $_SESSION['DESPACHOPT_ELIMINAR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización eliminación despacho PT #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó eliminar un despacho PT." . "\r\n\r\n" .
                "Número de despacho: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de despacho: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de despacho: " . $datosCorreo['tipo'] . "\r\n" .
                "Destino: " . $datosCorreo['destino'] . "\r\n" .
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
        $codigoSesion = $_SESSION['DESPACHOPT_ELIMINAR_CODIGO'] ?? null;
        $idSesion = $_SESSION['DESPACHOPT_ELIMINAR_ID'] ?? null;
        $tiempoSesion = $_SESSION['DESPACHOPT_ELIMINAR_TIEMPO'] ?? 0;

        if ($foliosDespachoActivos) {
            $detalleAsociado = '';
            if ($foliosDespachoActivos) {
                $folio = reset($foliosDespachoActivos);
                $folioNumero = $folio['FOLIO_AUXILIAR_EXIEXPORTACION'] ?? $folio['ID_EXIEXPORTACION'] ?? 'Sin Datos';
                $detalleAsociado = "Folios PT asociados: " . $folioNumero;
            }
            $MENSAJE = "El despacho N° " . $numeroDespachoMensaje . " tiene registros asociados y no puede ser eliminado. " . $detalleAsociado . ".";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDDESPACHO) {
            $MENSAJE = "No hay una solicitud de eliminación vigente para este despacho.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOVERIFICACION || $CODIGOVERIFICACION != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $DESPACHOPT->__SET('ID_DESPACHO', $IDDESPACHO);
            $DESPACHOPT_ADO->deshabilitar($DESPACHOPT);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación eliminación despacho PT #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la eliminación del despacho PT." . "\r\n\r\n" .
                "Número de despacho: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de despacho: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de despacho: " . $datosCorreo['tipo'] . "\r\n" .
                "Destino: " . $datosCorreo['destino'] . "\r\n" .
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
            $MENSAJEENVIO = $envioOk ? "Despacho eliminado (estado de registro desactivado)." : ($errorEnvio ?: "El despacho se eliminó pero hubo un problema al enviar la notificación.");
            unset($_SESSION['DESPACHOPT_ELIMINAR_CODIGO']);
            unset($_SESSION['DESPACHOPT_ELIMINAR_ID']);
            unset($_SESSION['DESPACHOPT_ELIMINAR_TIEMPO']);
        }
    }

    if (isset($_REQUEST['SOLICITAR_ABRIR'])) {
        if (!$IDDESPACHO) {
            $MENSAJE = "No se ha seleccionado un despacho válido.";
        } elseif (!$datosDespacho || $datosDespacho['ESTADO'] != 0) {
            $MENSAJE = "Solo es posible solicitar apertura para despachos cerrados.";
        } else {
            $codigoAutorizacion = generarCodigoAutorizacion();
            $_SESSION['DESPACHOPT_ABRIR_CODIGO'] = $codigoAutorizacion;
            $_SESSION['DESPACHOPT_ABRIR_ID'] = $IDDESPACHO;
            $_SESSION['DESPACHOPT_ABRIR_TIEMPO'] = time();

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Autorización apertura despacho PT #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se solicitó la apertura de un despacho PT cerrado." . "\r\n\r\n" .
                "Número de despacho: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de despacho: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de despacho: " . $datosCorreo['tipo'] . "\r\n" .
                "Destino: " . $datosCorreo['destino'] . "\r\n" .
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
        $codigoSesion = $_SESSION['DESPACHOPT_ABRIR_CODIGO'] ?? null;
        $idSesion = $_SESSION['DESPACHOPT_ABRIR_ID'] ?? null;
        $tiempoSesion = $_SESSION['DESPACHOPT_ABRIR_TIEMPO'] ?? 0;

        if (!$IDDESPACHO) {
            $MENSAJE = "No se ha seleccionado un despacho válido.";
        } elseif (!$datosDespacho || $datosDespacho['ESTADO'] != 0) {
            $MENSAJE = "Solo es posible abrir despachos que estén cerrados.";
        } elseif (!$codigoSesion || !$idSesion || $idSesion != $IDDESPACHO) {
            $MENSAJE = "No hay una solicitud de apertura vigente para este despacho.";
        } elseif ((time() - $tiempoSesion) > 900) {
            $MENSAJE = "El código de autorización ha expirado.";
        } elseif (!$CODIGOAPERTURA || $CODIGOAPERTURA != $codigoSesion) {
            $MENSAJE = "El código ingresado no es válido.";
        } else {
            $DESPACHOPT->__SET('ID_DESPACHO', $IDDESPACHO);
            $DESPACHOPT_ADO->habilitar($DESPACHOPT);
            $DESPACHOPT_ADO->abierto($DESPACHOPT);

            $destinatarios = obtenerDestinatariosAutorizacion($CORREOUSUARIO);
            $asunto = 'Confirmación apertura despacho PT #' . $datosCorreo['numero'];
            $mensajeCorreo = "Se confirmó la apertura del despacho PT." . "\r\n\r\n" .
                "Número de despacho: " . $datosCorreo['numero'] . "\r\n" .
                "Fecha de despacho: " . $datosCorreo['fecha'] . "\r\n" .
                "Tipo de despacho: " . $datosCorreo['tipo'] . "\r\n" .
                "Destino: " . $datosCorreo['destino'] . "\r\n" .
                "Empresa: " . $datosCorreo['empresa'] . "\r\n" .
                "Planta: " . $datosCorreo['planta'] . "\r\n" .
                "Temporada: " . $datosCorreo['temporada'] . "\r\n" .
                "Confirmado por: " . $NOMBRECOMPLETOUSUARIO . "\r\n\r\n" .
                "El estado del despacho cambió a abierto.";

            $remitente = 'informes@volcanfoods.cl';
            $usuarioSMTP = 'informes@volcanfoods.cl';
            $contrasenaSMTP = '1z=EWfu0026k';
            $hostSMTP = 'mail.volcanfoods.cl';
            $puertoSMTP = 465;

            [$envioOk, $errorEnvio] = enviarCorreoSMTP($destinatarios, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            if (!empty($CORREOUSUARIO)) {
                enviarCorreoSMTP($CORREOUSUARIO, $asunto, $mensajeCorreo, $remitente, $usuarioSMTP, $contrasenaSMTP, $hostSMTP, $puertoSMTP);
            }
            $MENSAJEENVIO = $envioOk ? "Despacho abierto correctamente." : ($errorEnvio ?: "El despacho se abrió pero hubo un problema al enviar la notificación.");
            unset($_SESSION['DESPACHOPT_ABRIR_CODIGO']);
            unset($_SESSION['DESPACHOPT_ABRIR_ID']);
            unset($_SESSION['DESPACHOPT_ABRIR_TIEMPO']);
        }
    }
}

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES



if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYDESPACHOPT = $DESPACHOPT_ADO->listarDespachoptEmpresaPlantaTemporadaCBX($EMPRESAS, $PLANTAS, $TEMPORADAS);
}


include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";





?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Agrupado Despacho</title>
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

           
                function refrescar() {
                    document.getElementById("form_reg_dato").submit();
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
                            <h3 class="page-title">Frigorifico</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Frigorifico</li>
                                        <li class="breadcrumb-item" aria-current="page">Despacho</li>
                                        <li class="breadcrumb-item" aria-current="page">Despacho P. Terminado</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Agrupado Despacho </a> </li>
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
                                        <table id="despachopt" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Número </th>
                                                    <th>Estado</th>
                                                    <th class="text-center">Operaciónes</th>
                                                    <th class="text-center">Autorizaciones</th>
                                                    <th>Estado Despacho</th>
                                                    <th>Fecha Despacho </th>
                                                    <th>Número Guía </th>
                                                    <th>Tipo Despacho</th>
                                                    <th>CSG/CSP Despacho</th>
                                                    <th>Destino Despacho</th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Kilos Neto</th>
                                                    <th>Kilos Bruto</th>
                                                    <th>Fecha Ingreso</th>
                                                    <th>Fecha Modificación</th>
                                                    <th>Transporte </th>
                                                    <th>Nombre Conductor </th>
                                                    <th>Patente Camión </th>
                                                    <th>Patente Carro </th>
                                                    <th>Semana Despacho </th>
                                                    <th>Empresa</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYDESPACHOPT as $r) : ?>
                                                    <?php
                                                    if ($r['ESTADO_DESPACHO'] == "1") {
                                                        $ESTADODESPACHO = "Por Confirmar";
                                                    }
                                                    if ($r['ESTADO_DESPACHO'] == "2") {
                                                        $ESTADODESPACHO = "Confirmado";
                                                    }
                                                    if ($r['ESTADO_DESPACHO'] == "3") {
                                                        $ESTADODESPACHO = "Rechazado";
                                                    }
                                                    if ($r['ESTADO_DESPACHO'] == "4") {
                                                        $ESTADODESPACHO = "Aprobado";
                                                    }
                                                    if ($r['TDESPACHO'] == "1") {
                                                        $TDESPACHO = "Interplanta";
                                                        $NUMEROGUIADEPACHO=$r["NUMERO_GUIA_DESPACHO"];
                                                        $ARRAYPLANTA2 = $PLANTA_ADO->verPlanta($r['ID_PLANTA2']);
                                                        if ($ARRAYPLANTA2) {
                                                            $CSGCSPDESTINO=$ARRAYPLANTA2[0]['CODIGO_SAG_PLANTA'];
                                                            $DESTINO = $ARRAYPLANTA2[0]['NOMBRE_PLANTA'];
                                                        } else {
                                                            $CSGCSPDESTINO="Sin Datos";
                                                            $DESTINO = "Sin Datos";
                                                        }
                                                    }
                                                    if ($r['TDESPACHO'] == "2") {
                                                        $TDESPACHO = "Devolución Productor";
                                                        $NUMEROGUIADEPACHO=$r["NUMERO_GUIA_DESPACHO"];
                                                        $ARRAYPRODUCTOR = $PRODUCTOR_ADO->verProductor($r['ID_PRODUCTOR']);
                                                        if ($ARRAYPRODUCTOR) {
                                                            $CSGCSPDESTINO=$ARRAYPRODUCTOR[0]['CSG_PRODUCTOR'];
                                                            $DESTINO =  $ARRAYPRODUCTOR[0]['NOMBRE_PRODUCTOR'];
                                                        } else {
                                                            $CSGCSPDESTINO="Sin Datos";
                                                            $DESTINO = "Sin Datos";
                                                        }
                                                    }
                                                    if ($r['TDESPACHO'] == "3") {
                                                        $TDESPACHO = "Venta";
                                                        $NUMEROGUIADEPACHO=$r["NUMERO_GUIA_DESPACHO"];
                                                        $ARRAYCOMPRADOR = $COMPRADOR_ADO->verComprador($r['ID_COMPRADOR']);
                                                        if ($ARRAYCOMPRADOR) {
                                                            $CSGCSPDESTINO="No Aplica";
                                                            $DESTINO = $ARRAYCOMPRADOR[0]['NOMBRE_COMPRADOR'];
                                                        } else {
                                                            $CSGCSPDESTINO="Sin Datos";
                                                            $DESTINO = "Sin Datos";
                                                        }
                                                    }
                                                    if ($r['TDESPACHO'] == "4") {
                                                        $TDESPACHO = "Despacho Regalo";
                                                        $NUMEROGUIADEPACHO="No Aplica";
                                                        $CSGCSPDESTINO="No Aplica";
                                                        $DESTINO = $r['REGALO_DESPACHO'];
                                                    }
                                                    if ($r['TDESPACHO'] == "5") {
                                                        $TDESPACHO = "Planta Externa";
                                                        $NUMEROGUIADEPACHO=$r["NUMERO_GUIA_DESPACHO"];
                                                        $ARRAYPLANTA2 = $PLANTA_ADO->verPlanta($r['ID_PLANTA3']);
                                                        if ($ARRAYPLANTA2) {
                                                            $CSGCSPDESTINO=$ARRAYPLANTA2[0]['CODIGO_SAG_PLANTA'];
                                                            $DESTINO = $ARRAYPLANTA2[0]['NOMBRE_PLANTA'];
                                                        } else {
                                                            $CSGCSPDESTINO="Sin Datos";
                                                            $DESTINO = "Sin Datos";
                                                        }
                                                    }
                                                    $ARRAYVERTRANSPORTE = $TRANSPORTE_ADO->verTransporte($r['ID_TRANSPORTE']);
                                                    if ($ARRAYVERTRANSPORTE) {
                                                        $NOMBRETRANSPORTE = $ARRAYVERTRANSPORTE[0]['NOMBRE_TRANSPORTE'];
                                                    } else {
                                                        $NOMBRETRANSPORTE = "Sin Datos";
                                                    }
                                                    $ARRAYVERCONDUCTOR = $CONDUCTOR_ADO->verConductor($r['ID_CONDUCTOR']);
                                                    if ($ARRAYVERCONDUCTOR) {

                                                        $NOMBRECONDUCTOR = $ARRAYVERCONDUCTOR[0]['NOMBRE_CONDUCTOR'];
                                                    } else {
                                                        $NOMBRECONDUCTOR = "Sin Datos";
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

                                                    $ARRAYMGUIAPT = $MGUIAPT_ADO->listarMguiaDespachoCBX($r['ID_DESPACHO']);
                                                    ?>
                                                    <tr class="text-center">
                                                        <td> <?php echo $r['NUMERO_DESPACHO']; ?> </td>
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
                                                                            <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_DESPACHO']; ?>" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroDespachopt" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarDespachopt" />
                                                                            <input type="hidden" class="form-control" placeholder="URL" id="URLMR" name="URLMR" value="listarDespachoMguiaPT" />
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
                                                                            <?php if ($ARRAYMGUIAPT) { ?>
                                                                                <hr>
                                                                                <span href="#" class="dropdown-item" data-toggle="tooltip" title="Ver Motivos">
                                                                                    <button type="submit" class="btn btn-primary btn-block " id="VERMOTIVOSRURL" name="VERMOTIVOSRURL">
                                                                                        <i class="ti-eye"></i> Ver motivos
                                                                                    </button>
                                                                                </span>
                                                                            <?php } ?>
                                                                            <hr>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Informe" Onclick="abrirPestana('../../assest/documento/informeDespachoPT.php?parametro=<?php echo $r['ID_DESPACHO']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Informe
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-danger  btn-block" id="defecto" name="informe" title="Comercial" Onclick="abrirPestana('../../assest/documento/informeDespachoPtInterComercial.php?parametro=<?php echo $r['ID_DESPACHO']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Comercial
                                                                                </button>
                                                                            </span>
                                                                            <span href="#" class="dropdown-item" data-toggle="tooltip" title="Informe">
                                                                                <button type="button" class="btn  btn-success  btn-block" id="defecto" name="informe" title="Comercial" Onclick="abrirPestana('../../assest/documento/informeDespachoPtInterComercial.php?parametro=<?php echo $r['ID_DESPACHO']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                    <i class="fa fa-file-pdf-o"></i> Comercial
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
                                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-block" data-toggle="modal" data-target="#modalEliminarDespacho" data-id="<?php echo $r['ID_DESPACHO']; ?>" data-numero="<?php echo $r['NUMERO_DESPACHO']; ?>">
                                                                        Eliminar despacho
                                                                    </button>
                                                                <?php } ?>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-outline-success btn-sm btn-block" data-toggle="modal" data-target="#modalAbrirDespacho" data-id="<?php echo $r['ID_DESPACHO']; ?>" data-numero="<?php echo $r['NUMERO_DESPACHO']; ?>">
                                                                        Abrir despacho
                                                                    </button>
                                                                <?php } ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo $ESTADODESPACHO; ?></td>
                                                        <td><?php echo $r['FECHA']; ?></td>
                                                        <td><?php echo $NUMEROGUIADEPACHO; ?></td>
                                                        <td><?php echo $TDESPACHO; ?></td>
                                                        <td><?php echo $CSGCSPDESTINO; ?></td>
                                                        <td><?php echo $DESTINO; ?></td>
                                                        <td><?php echo $r['ENVASE']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['BRUTO']; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td><?php echo $NOMBRETRANSPORTE; ?></td>
                                                        <td><?php echo $NOMBRECONDUCTOR; ?></td>
                                                        <td><?php echo $r['PATENTE_CAMION']; ?></td>
                                                        <td><?php echo $r['PATENTE_CARRO']; ?></td>                             
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
                            <div class="box-footer">
                                <div class="btn-toolbar mb-3" role="toolbar" aria-label="Datos generales">
                                    <div class="form-row align-items-center" role="group" aria-label="Datos">
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Envase</div>
                                                    <button class="btn   btn-default" id="TOTALENVASEV" name="TOTALENVASEV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Neto</div>
                                                    <button class="btn   btn-default" id="TOTALNETOV" name="TOTALNETOV" >                                                           
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Bruto</div>
                                                    <button class="btn   btn-default" id="TOTALBRUTOV" name="TOTALBRUTOV" >                                                           
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
        <!-- Modal Eliminar Despacho -->
        <div class="modal fade" id="modalEliminarDespacho" tabindex="-1" role="dialog" aria-labelledby="modalEliminarDespachoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalEliminarDespachoLabel">Autorización para eliminar despacho</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Se enviará un código de confirmación a Maria de los Ángeles y Erwin Isla. Ingrésalo para completar la eliminación.</p>
                            <p class="font-weight-bold">Despacho N° <span class="numero-despacho-eliminar"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroDespachopt">
                            <input type="hidden" name="URLO" value="listarDespachopt">
                            <div class="form-group">
                                <label for="codigoEliminar">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoEliminar" name="CODIGO_ELIMINAR" placeholder="Ingresa el código recibido">
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

        <!-- Modal Abrir Despacho -->
        <div class="modal fade" id="modalAbrirDespacho" tabindex="-1" role="dialog" aria-labelledby="modalAbrirDespachoLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAbrirDespachoLabel">Autorización para abrir despacho</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form method="post">
                        <div class="modal-body">
                            <p class="mb-3">Solicita y confirma el código enviado a Maria de los Ángeles y Erwin Isla para abrir el despacho cerrado.</p>
                            <p class="font-weight-bold">Despacho N° <span class="numero-despacho-abrir"></span></p>
                            <input type="hidden" name="ID" value="">
                            <input type="hidden" name="URL" value="registroDespachopt">
                            <input type="hidden" name="URLO" value="listarDespachopt">
                            <div class="form-group">
                                <label for="codigoAbrir">Código de autorización</label>
                                <input type="text" class="form-control" id="codigoAbrir" name="CODIGO_ABRIR" placeholder="Ingresa el código recibido">
                                <small class="form-text text-muted">El código tiene validez de 15 minutos.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-outline-success" name="SOLICITAR_ABRIR">Solicitar código</button>
                            <button type="submit" class="btn btn-success" name="CONFIRMAR_ABRIR">Abrir despacho</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            $(document).ready(function () {
                $('#modalEliminarDespacho').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idDespacho = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idDespacho);
                    modal.find('.numero-despacho-eliminar').text(numero || '');
                });

                $('#modalAbrirDespacho').on('show.bs.modal', function (event) {
                    var button = $(event.relatedTarget);
                    var idDespacho = button.data('id');
                    var numero = button.data('numero');
                    var modal = $(this);
                    modal.find('input[name="ID"]').val(idDespacho);
                    modal.find('.numero-despacho-abrir').text(numero || '');
                });
            });
        </script>
</body>

</html>
