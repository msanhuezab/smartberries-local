<?php

include_once "../../assest/config/validarUsuarioExpo.php";
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';

$RUTA_CONFIG_CRON_PT = dirname(__DIR__, 2) . '/data/config_cron_pt.json';
$RUTA_STATUS_CRON_PT = dirname(__DIR__, 2) . '/data/cron_pt_status.json';
$RUTA_EJECUCION_CRON_PT = dirname(__DIR__, 2) . '/fruta/cron/alertaFoliosExiexportacion.php';
$RUTA_LOCK_CRON_PT = dirname(__DIR__, 2) . '/fruta/cron/alerta_folios_exiexportacion.lock';

$CONFIG_ENVIO = [
    'habilitado' => true,
    'actualizado_en' => null,
    'fecha_inicio' => '',
    'hora' => '',
    'dias' => [],
    'correos' => '',
    'empresas' => [],
    'plantas' => [],
    'usuarios' => []
];

if (file_exists($RUTA_CONFIG_CRON_PT)) {
    $dataConfig = json_decode(file_get_contents($RUTA_CONFIG_CRON_PT), true);
    if (is_array($dataConfig)) {
        $CONFIG_ENVIO = array_merge($CONFIG_ENVIO, $dataConfig);
    }
}
$CONFIG_ENVIO['habilitado'] = isset($CONFIG_ENVIO['habilitado']) ? (bool) $CONFIG_ENVIO['habilitado'] : true;

$ESTADO_CRON = [
    'actualizado_en' => null,
    'estado' => null,
    'mensaje' => null,
    'envios' => 0,
    'errores' => [],
];
if (file_exists($RUTA_STATUS_CRON_PT)) {
    $dataStatus = json_decode(file_get_contents($RUTA_STATUS_CRON_PT), true);
    if (is_array($dataStatus)) {
        $ESTADO_CRON = array_merge($ESTADO_CRON, $dataStatus);
    }
}

$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
$USUARIO_ADO = new USUARIO_ADO();

$mapEmpresas = [];
foreach ($EMPRESA_ADO->listarEmpresaCBX() ?: [] as $empresa) {
    $mapEmpresas[$empresa['ID_EMPRESA']] = $empresa['NOMBRE_EMPRESA'];
}
$mapPlantas = [];
foreach ($PLANTA_ADO->listarPlantaCBX() ?: [] as $planta) {
    $mapPlantas[$planta['ID_PLANTA']] = $planta['NOMBRE_PLANTA'];
}
$mapUsuarios = [];
foreach ($USUARIO_ADO->listarUsuarioCBX() ?: [] as $usuario) {
    $mapUsuarios[$usuario['EMAIL_USUARIO']] = $usuario['NOMBRE_USUARIO'];
}

$MENSAJE_EJECUCION = null;
$MENSAJE_EJECUCION_TIPO = 'success';

function ejecutarCronPtDesdeVista(string $rutaScript, bool $force, array &$salida, int &$codigo): bool
{
    if (!function_exists('exec')) {
        return false;
    }

    $phpBin = PHP_BINARY ?? '';
    if (!is_string($phpBin) || $phpBin === '' || (!file_exists($phpBin) && !is_executable($phpBin))) {
        return false;
    }

    $comando = escapeshellarg($phpBin) . ' ' . escapeshellarg($rutaScript);
    if ($force) {
        $comando .= ' --force';
    }
    exec($comando . ' 2>&1', $salida, $codigo);
    return true;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['EJECUTAR_CRON_PT'])) {
    if (empty($CONFIG_ENVIO['habilitado'])) {
        $MENSAJE_EJECUCION = 'El cron está deshabilitado. Habilítalo antes de ejecutar manualmente.';
        $MENSAJE_EJECUCION_TIPO = 'warning';
    } elseif (!file_exists($RUTA_EJECUCION_CRON_PT)) {
        $MENSAJE_EJECUCION = 'No se encontró el archivo del cron para ejecutar.';
        $MENSAJE_EJECUCION_TIPO = 'danger';
    } else {
        $salida = [];
        $codigo = 0;
        $ejecutado = ejecutarCronPtDesdeVista($RUTA_EJECUCION_CRON_PT, true, $salida, $codigo);
        if (!$ejecutado) {
            $MENSAJE_EJECUCION = 'No se pudo ejecutar el cron desde la vista. Verifica que PHP CLI esté disponible.';
            $MENSAJE_EJECUCION_TIPO = 'danger';
        } else {
            $resultadoTexto = trim(implode("\n", $salida));
            if ($codigo === 0) {
                $MENSAJE_EJECUCION = $resultadoTexto !== '' ? $resultadoTexto : 'Cron ejecutado. Si no se enviaron correos, revisa destinatarios y filtros.';
                $MENSAJE_EJECUCION_TIPO = 'success';
            } else {
                $MENSAJE_EJECUCION = $resultadoTexto !== '' ? $resultadoTexto : 'No fue posible ejecutar el cron.';
                $MENSAJE_EJECUCION_TIPO = 'danger';
            }
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['REINICIAR_CRON_PT'])) {
    if (file_exists($RUTA_LOCK_CRON_PT)) {
        $resultado = @unlink($RUTA_LOCK_CRON_PT);
        if ($resultado) {
            $MENSAJE_EJECUCION = 'Cron reiniciado correctamente.';
        } else {
            $MENSAJE_EJECUCION = 'No fue posible reiniciar el cron.';
            $MENSAJE_EJECUCION_TIPO = 'danger';
        }
    } else {
        $MENSAJE_EJECUCION = 'No hay bloqueo activo para reiniciar.';
    }
}

$DIAS_SEMANA = [
    '1' => 'Lunes',
    '2' => 'Martes',
    '3' => 'Miércoles',
    '4' => 'Jueves',
    '5' => 'Viernes',
    '6' => 'Sábado',
    '7' => 'Domingo'
];

function obtenerProximaEjecucion(array $config): ?DateTime
{
    if (empty($config['habilitado'])) {
        return null;
    }

    $hora = trim($config['hora'] ?? '');
    $dias = $config['dias'] ?? [];
    if ($hora === '' || empty($dias)) {
        return null;
    }

    $partesHora = explode(':', $hora);
    if (count($partesHora) < 2) {
        return null;
    }

    $horaInt = max(0, min(23, (int) $partesHora[0]));
    $minutoInt = max(0, min(59, (int) $partesHora[1]));

    $now = new DateTime('now');
    if (!empty($config['fecha_inicio'])) {
        $inicio = DateTime::createFromFormat('Y-m-d', $config['fecha_inicio']);
        if ($inicio) {
            $inicio->setTime(0, 0, 0);
            if ($inicio > $now) {
                $now = $inicio;
            }
        }
    }
    for ($i = 0; $i <= 7; $i++) {
        $candidato = clone $now;
        if ($i > 0) {
            $candidato->modify('+' . $i . ' day');
        }
        $diaCandidato = $candidato->format('N');
        if (!in_array((string) $diaCandidato, $dias, true)) {
            continue;
        }
        $candidato->setTime($horaInt, $minutoInt, 0);
        if ($candidato <= $now) {
            continue;
        }
        return $candidato;
    }

    return null;
}

$proximaEjecucion = obtenerProximaEjecucion($CONFIG_ENVIO);
$timestampProxima = $proximaEjecucion ? $proximaEjecucion->getTimestamp() : null;

$diasSeleccionados = array_map(function ($dia) use ($DIAS_SEMANA) {
    return $DIAS_SEMANA[$dia] ?? $dia;
}, $CONFIG_ENVIO['dias'] ?? []);

$correosSeleccionados = array_filter(array_map('trim', explode(',', $CONFIG_ENVIO['correos'] ?? '')));
$usuariosSeleccionados = array_map(function ($correo) use ($mapUsuarios) {
    $nombre = $mapUsuarios[$correo] ?? null;
    return $nombre ? "{$nombre} ({$correo})" : $correo;
}, $CONFIG_ENVIO['usuarios'] ?? []);

$empresasSeleccionadas = array_map(function ($empresaId) use ($mapEmpresas) {
    return $mapEmpresas[$empresaId] ?? $empresaId;
}, $CONFIG_ENVIO['empresas'] ?? []);

$plantasSeleccionadas = array_map(function ($plantaId) use ($mapPlantas) {
    return $mapPlantas[$plantaId] ?? $plantaId;
}, $CONFIG_ENVIO['plantas'] ?? []);

if (isset($_GET['estado_cron_pt'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'habilitado' => $CONFIG_ENVIO['habilitado'],
        'server_time' => date('d/m/Y H:i'),
        'server_timestamp' => time(),
        'fecha_inicio' => $CONFIG_ENVIO['fecha_inicio'] ?? '',
        'hora' => $CONFIG_ENVIO['hora'] ?? '',
        'dias' => $diasSeleccionados,
        'permitir_multiples' => !empty($CONFIG_ENVIO['permitir_multiples']),
        'correos' => $correosSeleccionados,
        'usuarios' => $usuariosSeleccionados,
        'empresas' => $empresasSeleccionadas,
        'plantas' => $plantasSeleccionadas,
        'proxima_ejecucion' => $proximaEjecucion ? $proximaEjecucion->format('d/m/Y H:i') : null,
        'timestamp' => $timestampProxima,
        'actualizado_en' => $CONFIG_ENVIO['actualizado_en'] ?? null,
        'ultimo_estado' => $ESTADO_CRON['estado'] ?? null,
        'ultimo_envio' => $ESTADO_CRON['actualizado_en'] ? date('d/m/Y H:i', strtotime($ESTADO_CRON['actualizado_en'])) : null,
        'ultimo_mensaje' => $ESTADO_CRON['mensaje'] ?? null,
        'ultimo_envios' => $ESTADO_CRON['envios'] ?? 0,
    ]);
    exit;
}

if (isset($_GET['auto_run_cron_pt'])) {
    header('Content-Type: application/json; charset=utf-8');
    $salida = [];
    $codigo = 0;
    $respuesta = [
        'ok' => false,
        'mensaje' => 'Cron no ejecutado.',
        'salida' => [],
        'codigo' => null,
    ];
    if (!empty($CONFIG_ENVIO['habilitado'])) {
        $ejecutado = ejecutarCronPtDesdeVista($RUTA_EJECUCION_CRON_PT, false, $salida, $codigo);
        if ($ejecutado) {
            $respuesta['ok'] = $codigo === 0;
            $respuesta['salida'] = $salida;
            $respuesta['codigo'] = $codigo;
            $respuesta['mensaje'] = trim(implode("\n", $salida));
        } else {
            $respuesta['mensaje'] = 'No se pudo ejecutar el cron automáticamente. Verifica PHP CLI.';
        }
    } else {
        $respuesta['mensaje'] = 'Cron deshabilitado.';
    }
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Cron Existencia PT</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <style>
        .cron-ejecucion-card {
            background: #fff;
            border-radius: 8px;
            border: 1px solid #e7ebf3;
        }
        .cron-ejecucion-section {
            border: 1px solid #eef1f6;
            border-radius: 6px;
            padding: 12px;
            margin-bottom: 12px;
            background: #fbfcff;
        }
        .cron-ejecucion-title {
            font-weight: 600;
            color: #1f2d3d;
        }
        .cron-ejecucion-subtitle {
            font-size: 0.82rem;
            color: #6c7a89;
        }
        .cron-ejecucion-meta {
            font-size: 0.82rem;
            color: #4c5b6b;
        }
        .cron-ejecucion-chip {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 999px;
            background: #f4f6fb;
            font-size: 0.78rem;
            margin: 0 6px 6px 0;
        }
        .cron-ejecucion-label {
            font-size: 0.78rem;
            color: #728096;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .cron-ejecucion-time {
            font-weight: 600;
            color: #2b3a4b;
            font-size: 0.9rem;
        }
    </style>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuExpo.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Cron Existencia PT</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item active" aria-current="page">Cron en ejecución</li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="row">
                        <div class="col-12">
                            <div class="box cron-ejecucion-card">
                                <div class="box-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-10">
                                        <div>
                                            <h4 class="cron-ejecucion-title mb-0">Cron existencia producto terminado</h4>
                                            <span class="cron-ejecucion-subtitle">Estado y configuración activa del envío automático.</span>
                                        </div>
                                        <div class="d-flex align-items-center mt-10 mt-md-0">
                                            <span id="estado-cron" class="badge badge-pill <?php echo $CONFIG_ENVIO['habilitado'] ? 'badge-success' : 'badge-secondary'; ?> mr-10">
                                                <?php echo $CONFIG_ENVIO['habilitado'] ? 'Habilitado' : 'Deshabilitado'; ?>
                                            </span>
                                            <form method="post" class="m-0 mr-10">
                                                <button type="submit" name="EJECUTAR_CRON_PT" class="btn btn-sm btn-primary">
                                                    Ejecutar cron ahora
                                                </button>
                                            </form>
                                            <form method="post" class="m-0">
                                                <button type="submit" name="REINICIAR_CRON_PT" class="btn btn-sm btn-outline-secondary">
                                                    Restaurar cron
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="cron-ejecucion-subtitle mb-10">El envío automático depende del cron del servidor. Usa el botón solo para pruebas manuales.</div>
                                    <?php if (!empty($MENSAJE_EJECUCION)) { ?>
                                        <div class="alert alert-<?php echo $MENSAJE_EJECUCION_TIPO; ?> py-5 px-10">
                                            <?php echo nl2br(htmlspecialchars($MENSAJE_EJECUCION)); ?>
                                        </div>
                                    <?php } ?>
                                    <div class="cron-ejecucion-section">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="cron-ejecucion-label">Hora servidor</div>
                                                <div class="cron-ejecucion-meta mb-5">
                                                    <span id="server-time" class="cron-ejecucion-time"><?php echo date('d/m/Y H:i'); ?></span>
                                                </div>
                                                <div class="cron-ejecucion-label">Última ejecución</div>
                                                <div class="cron-ejecucion-meta mb-5">
                                                    <span id="cron-ultimo-envio"><?php echo $ESTADO_CRON['actualizado_en'] ? date('d/m/Y H:i', strtotime($ESTADO_CRON['actualizado_en'])) : 'Sin registros'; ?></span>
                                                </div>
                                                <div class="cron-ejecucion-label">Resultado</div>
                                                <div class="cron-ejecucion-meta mb-5">
                                                    <span id="cron-ultimo-estado"><?php echo $ESTADO_CRON['mensaje'] ?? 'Sin resultados'; ?></span>
                                                </div>
                                                <div class="cron-ejecucion-label">Próxima ejecución</div>
                                                <div class="cron-ejecucion-meta mb-5">
                                                    <?php if ($proximaEjecucion) { ?>
                                                        <span id="proxima-ejecucion"><?php echo $proximaEjecucion->format('d/m/Y H:i'); ?></span>
                                                    <?php } else { ?>
                                                        <span id="proxima-ejecucion" class="text-muted">Sin programación</span>
                                                    <?php } ?>
                                                </div>
                                                <div class="cron-ejecucion-label">Cuenta regresiva</div>
                                                <div class="cron-ejecucion-meta">
                                                    <span class="cron-countdown" data-countdown="<?php echo $timestampProxima ?? ''; ?>">
                                                        <?php echo $timestampProxima ? 'Calculando...' : 'No disponible'; ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="cron-ejecucion-label">Horario</div>
                                                <div class="cron-ejecucion-meta mb-5"><strong>Hora:</strong> <span id="cron-hora"><?php echo $CONFIG_ENVIO['hora'] ?: 'No definida'; ?></span></div>
                                                <div class="cron-ejecucion-meta"><strong>Días:</strong> <span id="cron-dias"><?php echo !empty($diasSeleccionados) ? implode(', ', $diasSeleccionados) : 'No definidos'; ?></span></div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="cron-ejecucion-label">Control</div>
                                                <div class="cron-ejecucion-meta mb-5"><strong>Inicio:</strong> <span id="cron-inicio"><?php echo $CONFIG_ENVIO['fecha_inicio'] ?: 'No definida'; ?></span></div>
                                                <div class="cron-ejecucion-meta"><strong>Permite múltiples:</strong> <span id="cron-multiples"><?php echo !empty($CONFIG_ENVIO['permitir_multiples']) ? 'Sí' : 'No'; ?></span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="cron-ejecucion-section">
                                        <div class="cron-ejecucion-label mb-5">Destinatarios</div>
                                        <div id="cron-correos">
                                            <?php if (!empty($correosSeleccionados)) { ?>
                                                <?php foreach ($correosSeleccionados as $correo) { ?>
                                                    <span class="cron-ejecucion-chip"><?php echo htmlspecialchars($correo); ?></span>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <span class="text-muted">Sin correos manuales.</span>
                                            <?php } ?>
                                        </div>
                                        <div class="cron-ejecucion-label mt-10">Usuarios</div>
                                        <div id="cron-usuarios">
                                            <?php if (!empty($usuariosSeleccionados)) { ?>
                                                <?php foreach ($usuariosSeleccionados as $usuario) { ?>
                                                    <span class="cron-ejecucion-chip"><?php echo htmlspecialchars($usuario); ?></span>
                                                <?php } ?>
                                            <?php } else { ?>
                                                <span class="text-muted">Sin usuarios asignados.</span>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <div class="cron-ejecucion-section">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="cron-ejecucion-label">Empresas</div>
                                                <div id="cron-empresas">
                                                    <?php if (!empty($empresasSeleccionadas)) { ?>
                                                        <?php foreach ($empresasSeleccionadas as $empresa) { ?>
                                                            <span class="cron-ejecucion-chip"><?php echo htmlspecialchars($empresa); ?></span>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Sin empresas asignadas.</span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="cron-ejecucion-label">Plantas</div>
                                                <div id="cron-plantas">
                                                    <?php if (!empty($plantasSeleccionadas)) { ?>
                                                        <?php foreach ($plantasSeleccionadas as $planta) { ?>
                                                            <span class="cron-ejecucion-chip"><?php echo htmlspecialchars($planta); ?></span>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <span class="text-muted">Sin plantas asignadas.</span>
                                                    <?php } ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
        <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
    </div>

    <script>
        let serverTimestamp = null;

        function formatCountdown(ms) {
            if (ms <= 0) {
                return 'En ejecución';
            }
            const totalSeconds = Math.floor(ms / 1000);
            const days = Math.floor(totalSeconds / 86400);
            const hours = Math.floor((totalSeconds % 86400) / 3600);
            const minutes = Math.floor((totalSeconds % 3600) / 60);
            const seconds = totalSeconds % 60;
            const parts = [];
            if (days > 0) {
                parts.push(days + 'd');
            }
            parts.push(String(hours).padStart(2, '0') + ':' + String(minutes).padStart(2, '0') + ':' + String(seconds).padStart(2, '0'));
            return parts.join(' ');
        }

        function actualizarCuentaRegresiva() {
            document.querySelectorAll('.cron-countdown').forEach((element) => {
                const timestamp = element.getAttribute('data-countdown');
                if (!timestamp) {
                    element.textContent = 'No disponible';
                    return;
                }
                const diff = (parseInt(timestamp, 10) * 1000) - Date.now();
                element.textContent = formatCountdown(diff);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            actualizarCuentaRegresiva();
            setInterval(actualizarCuentaRegresiva, 1000);
        });
    </script>
    <script>
        function aplicarEstadoCron(data) {
            const proxima = document.getElementById('proxima-ejecucion');
            const hora = document.getElementById('cron-hora');
            const dias = document.getElementById('cron-dias');
            const contador = document.querySelector('.cron-countdown');
            const estado = document.getElementById('estado-cron');
            const inicio = document.getElementById('cron-inicio');
            const multiples = document.getElementById('cron-multiples');
            const correos = document.getElementById('cron-correos');
            const usuarios = document.getElementById('cron-usuarios');
            const empresas = document.getElementById('cron-empresas');
            const plantas = document.getElementById('cron-plantas');
            const serverTime = document.getElementById('server-time');
            const ultimoEnvio = document.getElementById('cron-ultimo-envio');
            const ultimoEstado = document.getElementById('cron-ultimo-estado');

            if (proxima) {
                if (data.proxima_ejecucion) {
                    proxima.textContent = data.proxima_ejecucion;
                    proxima.classList.remove('text-muted');
                } else {
                    proxima.textContent = 'Sin programación';
                    proxima.classList.add('text-muted');
                }
            }
            if (contador) {
                contador.setAttribute('data-countdown', data.timestamp || '');
            }
            if (hora) {
                hora.textContent = data.hora || 'No definida';
            }
            if (dias) {
                dias.textContent = (data.dias && data.dias.length) ? data.dias.join(', ') : 'No definidos';
            }
            if (inicio) {
                inicio.textContent = data.fecha_inicio || 'No definida';
            }
            if (multiples) {
                multiples.textContent = data.permitir_multiples ? 'Sí' : 'No';
            }
            if (estado) {
                const habilitado = !!data.habilitado;
                estado.textContent = habilitado ? 'Habilitado' : 'Deshabilitado';
                estado.classList.toggle('badge-success', habilitado);
                estado.classList.toggle('badge-secondary', !habilitado);
            }
            if (serverTime) {
                serverTime.textContent = data.server_time || serverTime.textContent;
            }
            if (data.server_timestamp) {
                serverTimestamp = parseInt(data.server_timestamp, 10);
            }
            if (ultimoEnvio) {
                ultimoEnvio.textContent = data.ultimo_envio ? data.ultimo_envio : 'Sin registros';
            }
            if (ultimoEstado) {
                ultimoEstado.textContent = data.ultimo_mensaje ? data.ultimo_mensaje : 'Sin resultados';
            }
            const renderChips = (element, items, emptyText) => {
                if (!element) {
                    return;
                }
                element.innerHTML = '';
                if (items && items.length) {
                    items.forEach((item) => {
                        const span = document.createElement('span');
                        span.className = 'cron-ejecucion-chip';
                        span.textContent = item;
                        element.appendChild(span);
                    });
                } else {
                    const span = document.createElement('span');
                    span.className = 'text-muted';
                    span.textContent = emptyText;
                    element.appendChild(span);
                }
            };

            renderChips(correos, data.correos || [], 'Sin correos manuales.');
            renderChips(usuarios, data.usuarios || [], 'Sin usuarios asignados.');
            renderChips(empresas, data.empresas || [], 'Sin empresas asignadas.');
            renderChips(plantas, data.plantas || [], 'Sin plantas asignadas.');

        }

        function verificarActualizacionCron() {
            fetch('cronEjecutados.php?estado_cron_pt=1', { cache: 'no-store' })
                .then((response) => response.json())
                .then((data) => {
                    aplicarEstadoCron(data);
                    actualizarCuentaRegresiva();
                })
                .catch(() => {
                    // No interrumpir la vista si no hay respuesta.
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            verificarActualizacionCron();
            setInterval(verificarActualizacionCron, 15000);
            setInterval(() => {
                if (serverTimestamp && serverTime) {
                    serverTimestamp += 1;
                    const fecha = new Date(serverTimestamp * 1000);
                    const formato = fecha.toLocaleString('es-CL', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    serverTime.textContent = formato;
                }
            }, 1000);
        });
    </script>
</body>

</html>
