<?php
/**
 * Envío automático de folios de exportación con más de 3 días sin inspección SAG.
 * Ejecutar vía CLI o cron, opcionalmente filtrando por empresa/planta/temporada:
 *   php alertaFoliosExiexportacion.php --empresa=1 --planta=2 --temporada=5 --force --reset
 *   php alertaFoliosExiexportacion.php --reset-only
 */

$BASE_PATH = dirname(__DIR__, 2);
require_once $BASE_PATH . '/assest/config/BDCONFIG.php';
require_once $BASE_PATH . '/assest/controlador/EXIEXPORTACION_ADO.php';
require_once $BASE_PATH . '/assest/controlador/EEXPORTACION_ADO.php';
require_once $BASE_PATH . '/assest/controlador/PRODUCTOR_ADO.php';
require_once $BASE_PATH . '/assest/controlador/VESPECIES_ADO.php';
require_once $BASE_PATH . '/assest/controlador/ESPECIES_ADO.php';
require_once $BASE_PATH . '/assest/controlador/TINPSAG_ADO.php';
require_once $BASE_PATH . '/assest/controlador/INPSAG_ADO.php';
require_once $BASE_PATH . '/assest/controlador/TMANEJO_ADO.php';
require_once $BASE_PATH . '/assest/controlador/EMPRESA_ADO.php';
require_once $BASE_PATH . '/assest/controlador/PLANTA_ADO.php';
require_once $BASE_PATH . '/assest/controlador/TEMPORADA_ADO.php';

date_default_timezone_set('America/Santiago');

$CONFIG_PATH = $BASE_PATH . '/data/config_cron_pt.json';
$STATUS_PATH = $BASE_PATH . '/data/cron_pt_status.json';
$LOCK_PATH = __DIR__ . '/alerta_folios_exiexportacion.lock';

function obtenerDestinatariosAutorizacion($correoSolicitante)
{
    $correosBase = ['maperez@fvolcan.cl', 'eisla@fvolcan.cl'];
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

function cargarConfiguracionCronPt(string $ruta): array
{
    $config = [
        'habilitado' => true,
        'actualizado_en' => null,
        'fecha_inicio' => '',
        'permitir_multiples' => false,
        'hora' => '',
        'dias' => [],
        'correos' => '',
        'empresas' => [],
        'plantas' => [],
        'usuarios' => []
    ];

    if (file_exists($ruta)) {
        $data = json_decode(file_get_contents($ruta), true);
        if (is_array($data)) {
            $config = array_merge($config, $data);
        }
    }

    $config['habilitado'] = isset($config['habilitado']) ? (bool) $config['habilitado'] : true;
    $config['permitir_multiples'] = !empty($config['permitir_multiples']);
    $config['dias'] = isset($config['dias']) && is_array($config['dias']) ? array_values(array_unique($config['dias'])) : [];
    $config['empresas'] = isset($config['empresas']) && is_array($config['empresas']) ? array_values(array_unique($config['empresas'])) : [];
    $config['plantas'] = isset($config['plantas']) && is_array($config['plantas']) ? array_values(array_unique($config['plantas'])) : [];
    $config['usuarios'] = isset($config['usuarios']) && is_array($config['usuarios']) ? array_values(array_unique($config['usuarios'])) : [];

    return $config;
}

function guardarEstadoCronPt(string $ruta, array $data): void
{
    $payload = array_merge([
        'actualizado_en' => date('c'),
    ], $data);

    $dir = dirname($ruta);
    if (!is_dir($dir)) {
        @mkdir($dir, 0777, true);
    }
    file_put_contents($ruta, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

function obtenerTemporadaVigente($TEMPORADA_ADO, $temporadaIdManual = null)
{
    if ($temporadaIdManual) {
        return $temporadaIdManual;
    }
    $temporadas = $TEMPORADA_ADO->listarTemporadaCBX() ?: [];
    if (empty($temporadas)) {
        return null;
    }
    usort($temporadas, fn($a, $b) => ($b['ID_TEMPORADA'] ?? 0) <=> ($a['ID_TEMPORADA'] ?? 0));
    return $temporadas[0]['ID_TEMPORADA'] ?? null;
}

function obtenerFoliosAtrasados($empresaId, $plantaId, $temporadaId, $EXIEXPORTACION_ADO, $EEXPORTACION_ADO, $PRODUCTOR_ADO, $VESPECIES_ADO, $INPSAG_ADO, $cache)
{
    $resultado = [];
    $agrupados = $EXIEXPORTACION_ADO->listarExiexportacionAgrupadoPorFolioEmpresaPlantaTemporadaDisponible($empresaId, $plantaId, $temporadaId);
    foreach ($agrupados as $s) {
        $detalles = $EXIEXPORTACION_ADO->listarExiexportacionEmpresaPlantaTemporadaPorFolioDisponible($empresaId, $plantaId, $temporadaId, $s['FOLIO_AUXILIAR_EXIEXPORTACION']);
        foreach ($detalles as $r) {
            $fechaEmbalado = null;
            if (!empty($r['EMBALADO'])) {
                $fechaEmbalado = DateTime::createFromFormat('Y-m-d', $r['EMBALADO']) ?: DateTime::createFromFormat('d/m/Y', $r['EMBALADO']);
            }
            $dias = $fechaEmbalado ? $fechaEmbalado->setTime(0, 0)->diff(new DateTime('today'))->format('%a') : null;
            if (($r['ID_INPSAG'] ?? null) || !is_numeric($dias) || $dias <= 3) {
                continue;
            }

            $productor = "Sin Datos";
            if (!isset($cache['PRODUCTOR'][$r['ID_PRODUCTOR']])) {
                $cache['PRODUCTOR'][$r['ID_PRODUCTOR']] = $PRODUCTOR_ADO->verProductor($r['ID_PRODUCTOR']);
            }
            if (!empty($cache['PRODUCTOR'][$r['ID_PRODUCTOR']])) {
                $productor = $cache['PRODUCTOR'][$r['ID_PRODUCTOR']][0]['NOMBRE_PRODUCTOR'];
            }

            $variedad = "Sin Datos";
            if (!isset($cache['VESPECIES'][$r['ID_VESPECIES']])) {
                $cache['VESPECIES'][$r['ID_VESPECIES']] = $VESPECIES_ADO->verVespecies($r['ID_VESPECIES']);
            }
            if (!empty($cache['VESPECIES'][$r['ID_VESPECIES']])) {
                $variedad = $cache['VESPECIES'][$r['ID_VESPECIES']][0]['NOMBRE_VESPECIES'];
            }

            $sif = "Sin Datos";
            $inpsag = $INPSAG_ADO->verInpsag3($r['ID_INPSAG']) ?: [];
            if (!empty($inpsag[0]['CORRELATIVO_INPSAG'])) {
                $sif = $inpsag[0]['CORRELATIVO_INPSAG'];
            }

            $estandar = "Sin Datos";
            if (!isset($cache['ESTANDAR'][$r['ID_ESTANDAR']])) {
                $cache['ESTANDAR'][$r['ID_ESTANDAR']] = $EEXPORTACION_ADO->verEstandar($r['ID_ESTANDAR']);
            }
            if (!empty($cache['ESTANDAR'][$r['ID_ESTANDAR']])) {
                $estandar = $cache['ESTANDAR'][$r['ID_ESTANDAR']][0]['CODIGO_ESTANDAR'];
            }

            $resultado[] = [
                'folio' => $r['FOLIO_AUXILIAR_EXIEXPORTACION'],
                'productor' => $productor,
                'variedad' => $variedad,
                'dias' => $dias,
                'embalado' => $r['EMBALADO'] ?? 'Sin Datos',
                'sif' => $sif,
                'estandar' => $estandar,
            ];
        }
    }

    return $resultado;
}

function obtenerDestinatariosConfigurados(array $config): array
{
    $manuales = array_filter(array_map('trim', explode(',', $config['correos'] ?? '')));
    $usuarios = array_filter(array_map('trim', $config['usuarios'] ?? []));
    return array_values(array_unique(array_merge($manuales, $usuarios)));
}

function validarEjecucionCron(array $config, bool $force, bool $desdeInclude): array
{
    if (empty($config['habilitado'])) {
        return [false, 'Cron PT deshabilitado.'];
    }

    if ($force || $desdeInclude) {
        return [true, null];
    }

    $hora = trim((string) ($config['hora'] ?? ''));
    $dias = $config['dias'] ?? [];
    if ($hora === '' || empty($dias)) {
        return [false, 'Configuración de hora/días no definida.'];
    }

    $fechaInicioConfig = trim((string) ($config['fecha_inicio'] ?? ''));
    if ($fechaInicioConfig) {
        $fechaInicio = DateTime::createFromFormat('Y-m-d', $fechaInicioConfig);
        if ($fechaInicio) {
            $fechaActual = new DateTime('today');
            if ($fechaActual < $fechaInicio) {
                return [false, "Cron PT aún no inicia. Fecha inicio: {$fechaInicioConfig}."];
            }
        }
    }

    $diaSemana = (int) date('N');
    if (!in_array((string) $diaSemana, $dias, true)) {
        return [false, 'Hoy no está configurado como día de envío.'];
    }

    $ahora = new DateTime('now');
    $objetivo = DateTime::createFromFormat('H:i', $hora) ?: new DateTime('today');
    $objetivo->setDate((int) $ahora->format('Y'), (int) $ahora->format('m'), (int) $ahora->format('d'));
    if ($ahora < $objetivo) {
        return [false, "Aún no se alcanza la hora configurada ({$hora})."];
    }

    return [true, null];
}

$options = getopt('', ['empresa::', 'planta::', 'temporada::', 'force::', 'reset::', 'reset-only::']);
if ($options === false) {
    $options = [];
}

$force = array_key_exists('force', $options);
$reset = array_key_exists('reset', $options);
$resetOnly = array_key_exists('reset-only', $options);

if ($reset || $resetOnly) {
    @unlink($LOCK_PATH);
    if ($resetOnly) {
        echo "Lock del cron reiniciado.\n";
        exit(0);
    }
}

$config = cargarConfiguracionCronPt($CONFIG_PATH);
$desdeInclude = defined('CRON_FOLIOS_INCLUDE_ONLY');

[$puedeEjecutar, $motivo] = validarEjecucionCron($config, $force, $desdeInclude);
if (!$puedeEjecutar) {
    echo $motivo . "\n";
    guardarEstadoCronPt($STATUS_PATH, [
        'estado' => 'omitido',
        'mensaje' => $motivo,
        'envios' => 0,
    ]);
    exit(0);
}

$horaConfig = trim((string) ($config['hora'] ?? ''));
$tokenLock = date('Y-m-d') . ' ' . $horaConfig . ' ' . ($config['actualizado_en'] ?? '');
if (!$force && !$desdeInclude && !$config['permitir_multiples'] && file_exists($LOCK_PATH)) {
    $lockActual = trim((string) @file_get_contents($LOCK_PATH));
    if ($lockActual === $tokenLock) {
        echo "Alerta ya enviada hoy a la hora configurada.\n";
        guardarEstadoCronPt($STATUS_PATH, [
            'estado' => 'omitido',
            'mensaje' => 'Alerta ya enviada hoy.',
            'envios' => 0,
        ]);
        exit(0);
    }
}

$destinatarios = obtenerDestinatariosConfigurados($config);
if (empty($destinatarios)) {
    echo "Sin destinatarios configurados.\n";
    guardarEstadoCronPt($STATUS_PATH, [
        'estado' => 'error',
        'mensaje' => 'Sin destinatarios configurados.',
        'envios' => 0,
    ]);
    exit(0);
}

$EXIEXPORTACION_ADO = new EXIEXPORTACION_ADO();
$EEXPORTACION_ADO = new EEXPORTACION_ADO();
$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$VESPECIES_ADO = new VESPECIES_ADO();
$INPSAG_ADO = new INPSAG_ADO();
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$TEMPORADA_ADO = new TEMPORADA_ADO();

$temporadaManual = isset($options['temporada']) ? (int) $options['temporada'] : null;
$temporadaId = obtenerTemporadaVigente($TEMPORADA_ADO, $temporadaManual);
if (!$temporadaId) {
    echo "No se encontró una temporada vigente.\n";
    guardarEstadoCronPt($STATUS_PATH, [
        'estado' => 'error',
        'mensaje' => 'No se encontró temporada vigente.',
        'envios' => 0,
    ]);
    exit(1);
}

$empresaFiltroLista = array_map('intval', $config['empresas'] ?? []);
$plantaFiltroLista = array_map('intval', $config['plantas'] ?? []);

if (isset($options['empresa'])) {
    $empresaFiltroLista = array_filter([(int) $options['empresa']]);
}
if (isset($options['planta'])) {
    $plantaFiltroLista = array_filter([(int) $options['planta']]);
}

$empresas = $EMPRESA_ADO->listarEmpresaCBX() ?: [];
$plantas = $PLANTA_ADO->listarPlantaCBX() ?: [];

$enviosRealizados = 0;
$errores = [];

foreach ($empresas as $empresa) {
    if (!empty($empresaFiltroLista) && !in_array((int) $empresa['ID_EMPRESA'], $empresaFiltroLista, true)) {
        continue;
    }
    foreach ($plantas as $planta) {
        if (!empty($plantaFiltroLista) && !in_array((int) $planta['ID_PLANTA'], $plantaFiltroLista, true)) {
            continue;
        }

        $cache = ['PRODUCTOR' => [], 'VESPECIES' => [], 'ESTANDAR' => []];
        $folios = obtenerFoliosAtrasados(
            $empresa['ID_EMPRESA'],
            $planta['ID_PLANTA'],
            $temporadaId,
            $EXIEXPORTACION_ADO,
            $EEXPORTACION_ADO,
            $PRODUCTOR_ADO,
            $VESPECIES_ADO,
            $INPSAG_ADO,
            $cache
        );

        if (empty($folios)) {
            continue;
        }

        $lineas = array_map(function ($item) {
            return "Folio: {$item['folio']} | Productor: {$item['productor']} | Variedad: {$item['variedad']} | Días: {$item['dias']} | Embalado: {$item['embalado']} | SIF: {$item['sif']} | Estándar: {$item['estandar']}";
        }, $folios);

        $mensaje = "Empresa: {$empresa['NOMBRE_EMPRESA']}\r\nPlanta: {$planta['NOMBRE_PLANTA']}\r\nTemporada ID: {$temporadaId}\r\n\r\n";
        $mensaje .= "Folios con más de 3 días sin inspección SAG:\r\n\r\n" . implode("\r\n", $lineas);

        $asunto = "Alerta folios sin inspección - {$empresa['NOMBRE_EMPRESA']} / {$planta['NOMBRE_PLANTA']}";
        [$ok, $error] = enviarCorreoSMTP($destinatarios, $asunto, $mensaje, 'informes@volcanfoods.cl', 'informes@volcanfoods.cl', '1z=EWfu0026k', 'mail.volcanfoods.cl', 465);
        if ($ok) {
            $enviosRealizados++;
            echo "Enviado: {$empresa['NOMBRE_EMPRESA']} / {$planta['NOMBRE_PLANTA']} (" . count($folios) . " folios)\n";
        } else {
            $errores[] = "Error enviando {$empresa['NOMBRE_EMPRESA']} / {$planta['NOMBRE_PLANTA']}: {$error}";
            echo end($errores) . "\n";
        }
    }
}

if ($enviosRealizados > 0 && !$desdeInclude && !$config['permitir_multiples']) {
    @file_put_contents($LOCK_PATH, $tokenLock);
}

$mensajeEstado = $enviosRealizados > 0 ? 'Envíos realizados correctamente.' : 'Sin envíos (sin folios o filtros sin coincidencia).';
if (!empty($errores)) {
    $mensajeEstado = 'Se ejecutó con errores.';
}

guardarEstadoCronPt($STATUS_PATH, [
    'estado' => $enviosRealizados > 0 ? 'ok' : (empty($errores) ? 'sin_envios' : 'error'),
    'mensaje' => $mensajeEstado,
    'envios' => $enviosRealizados,
    'errores' => $errores,
]);

echo "Proceso finalizado. Envios: {$enviosRealizados}\n";
