<?php

include_once "../../assest/config/validarUsuarioOpera.php";
include_once '../../assest/controlador/DESPACHOEX_ADO.php';

$DESPACHOEX_ADO = new DESPACHOEX_ADO();

$COLUMNAS_DETALLADO = [
    'Número Referencia',
    'Cliente',
    'Mercado',
    'Contenedor',
    'Tipo Despacho',
    'Número Despacho',
    'Fecha Despacho',
    'Número Guía Despacho',
    'Destino',
    'Fecha Corte Documental',
    'Fecha ETD',
    'Fecha Real ETD',
    'Fecha ETA',
    'Fecha Real ETA',
    'Recibidor Final',
    'Tipo Embarque',
    'Nave',
    'Número Viaje/Vuelo',
    'Puerto/Aeropuerto/Lugar Destino',
    'N° Folio Original',
    'N° Folio',
    'Fecha Embalado',
    'Condición',
    'Código Estandar',
    'Envase/Estandar',
    'CSG',
    'Productor',
    'Especies',
    'Variedad',
    'Cantidad Envase',
    'Kilos Neto',
    '% Deshidratación',
    'Kilos Deshidratación',
    'Kilos Bruto',
    'Número Repaletizaje',
    'Fecha Repaletizaje',
    'Número Proceso',
    'Fecha Proceso',
    'Tipo Proceso',
    'Número Reembalaje',
    'Fecha Reembalaje',
    'Tipo Reembalaje',
    'Tipo Manejo',
    'Tipo Calibre',
    'Tipo Embalaje',
    'Stock',
    'Embolsado',
    'Gasificación',
    'Prefrío',
    'Transporte',
    'Nombre Conductor',
    'Patente Camión',
    'Patente Carro',
    'Semana',
    'Semana Guía',
    'Empresa',
    'Planta',
    'Temporada',
    'Bl/AWB',
    'Número Recepción',
    'Fecha Recepción',
    'Tipo Recepción',
    'Número Guía Recepción',
    'Fecha Guía Recepción',
    'Número Recepción MP',
    'Fecha Recepción MP',
    'Tipo Recepción MP',
    'Número Guía Recepción MP',
    'Fecha Guía Recepción MP',
    'Planta Recepción MP',
    'Termógrafo Despacho',
    'Termógrafo Pallet',
];

if (!function_exists('repararUtf8DespachoexDetallado')) {
    function repararUtf8DespachoexDetallado($valor)
    {
        if (!is_string($valor) || (strpos($valor, "\xC3\x83") === false && strpos($valor, "\xC3\x82") === false)) {
            return $valor;
        }
        for ($i = 0; $i < 2; $i++) {
            if (strpos($valor, "\xC3\x83") === false && strpos($valor, "\xC3\x82") === false) {
                break;
            }
            if (function_exists('mb_convert_encoding')) {
                $convertido = mb_convert_encoding($valor, 'Windows-1252', 'UTF-8');
            } elseif (function_exists('iconv')) {
                $convertido = iconv('UTF-8', 'Windows-1252//IGNORE', $valor);
            } else {
                break;
            }
            if ($convertido === false || $convertido === $valor) {
                break;
            }
            $valor = $convertido;
        }
        return $valor;
    }
}

if (!function_exists('normalizarFilaUtf8DespachoexDetallado')) {
    function normalizarFilaUtf8DespachoexDetallado($fila)
    {
        if (!is_array($fila)) {
            return $fila;
        }
        $normalizada = [];
        foreach ($fila as $clave => $valor) {
            $normalizada[repararUtf8DespachoexDetallado($clave)] = repararUtf8DespachoexDetallado($valor);
        }
        return $normalizada;
    }
}

$COLUMNAS_DETALLADO = array_map('repararUtf8DespachoexDetallado', $COLUMNAS_DETALLADO);

$FILTROS = [
    'id_especie' => $ESPECIE ?? ($_SESSION['ID_ESPECIE'] ?? ''),
    'fecha_desde' => $_GET['fecha_desde'] ?? '',
    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
    'productor' => trim($_GET['productor'] ?? ''),
    'csg' => trim($_GET['csg'] ?? ''),
    'especie' => trim($_GET['especie'] ?? ''),
    'variedad' => trim($_GET['variedad'] ?? ''),
];
foreach (['fecha_desde', 'fecha_hasta'] as $campoFecha) {
    if ($FILTROS[$campoFecha] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $FILTROS[$campoFecha])) {
        $FILTROS[$campoFecha] = '';
    }
}

$h = function ($valor) {
    return htmlspecialchars((string) ($valor ?? ''), ENT_QUOTES, 'UTF-8');
};
$parseNumero = function ($valor) {
    $valor = trim((string) $valor);
    if ($valor === '') {
        return 0.0;
    }
    if (strpos($valor, ',') !== false && strpos($valor, '.') !== false) {
        $valor = str_replace(['.', ','], ['', '.'], $valor);
    } elseif (strpos($valor, ',') !== false) {
        $valor = str_replace(',', '.', $valor);
    }
    return is_numeric($valor) ? (float) $valor : 0.0;
};

$tiempoInicio = microtime(true);
$ARRAYDESPACHOEX = $TEMPORADAS
    ? $DESPACHOEX_ADO->listarDetalleDespachoexVistaTemporadaFiltrado($TEMPORADAS, $FILTROS)
    : [];
$ARRAYDESPACHOEX = array_map('normalizarFilaUtf8DespachoexDetallado', $ARRAYDESPACHOEX);
$tiempoConsulta = microtime(true) - $tiempoInicio;

$TOTALENVASE = 0;
$TOTALNETO = 0;
$TOTALBRUTO = 0;
foreach ($ARRAYDESPACHOEX as $filaTotal) {
    $TOTALENVASE += $parseNumero($filaTotal['Cantidad Envase'] ?? 0);
    $TOTALNETO += $parseNumero($filaTotal['Kilos Neto'] ?? 0);
    $TOTALBRUTO += $parseNumero($filaTotal['Kilos Bruto'] ?? 0);
}

if (isset($_GET['exportar']) && $_GET['exportar'] === 'excel') {
    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="detallado-despacho-expo.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    echo "\xEF\xBB\xBF";
    echo '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>';
    echo '<table border="1"><thead><tr>';
    foreach ($COLUMNAS_DETALLADO as $columna) {
        echo '<th>' . $h($columna) . '</th>';
    }
    echo '</tr></thead><tbody>';
    foreach ($ARRAYDESPACHOEX as $fila) {
        echo '<tr>';
        foreach ($COLUMNAS_DETALLADO as $columna) {
            echo '<td>' . $h($fila[$columna] ?? '') . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></body></html>';
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Detallado Despacho Expo</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <script type="text/javascript">
        function irPagina(url) {
            location.href = "" + url;
        }
        function abrirPestana(url) {
            var win = window.open(url, '_blank');
            win.focus();
        }
    </script>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuOpera.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Detallado</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Detallado</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Detallado Despacho Expo</a></li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-body">
                            <form method="get" class="mb-3">
                                <div class="row align-items-end">
                                    <div class="col-md-2 col-sm-6">
                                        <label>Fecha desde</label>
                                        <input type="date" class="form-control" name="fecha_desde" value="<?php echo $h($FILTROS['fecha_desde']); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label>Fecha hasta</label>
                                        <input type="date" class="form-control" name="fecha_hasta" value="<?php echo $h($FILTROS['fecha_hasta']); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label>CSG</label>
                                        <input type="text" class="form-control" name="csg" value="<?php echo $h($FILTROS['csg']); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label>Productor</label>
                                        <input type="text" class="form-control" name="productor" value="<?php echo $h($FILTROS['productor']); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label>Especie</label>
                                        <input type="text" class="form-control" name="especie" value="<?php echo $h($FILTROS['especie']); ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label>Variedad</label>
                                        <input type="text" class="form-control" name="variedad" value="<?php echo $h($FILTROS['variedad']); ?>">
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">Filtrar</button>
                                    <a class="btn btn-default" href="listarDespachoexDetallado.php">Limpiar</a>
                                    <button type="submit" class="btn btn-success" name="exportar" value="excel">Exportar Excel</button>
                                    <span class="ml-3">
                                        <?php echo count($ARRAYDESPACHOEX); ?> filas en <?php echo number_format($tiempoConsulta, 3, ',', '.'); ?> segundos
                                    </span>
                                </div>
                            </form>

                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="detalladodex" class="table-hover" style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <?php foreach ($COLUMNAS_DETALLADO as $columna) : ?>
                                                        <th><?php echo $h($columna); ?></th>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYDESPACHOEX as $fila) : ?>
                                                    <tr class="text-center">
                                                        <?php foreach ($COLUMNAS_DETALLADO as $columna) : ?>
                                                            <td><?php echo $h($fila[$columna] ?? ''); ?></td>
                                                        <?php endforeach; ?>
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
                                                    <button class="btn btn-default" id="TOTALENVASEV" name="TOTALENVASEV"><?php echo number_format($TOTALENVASE, 0, ',', '.'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Neto</div>
                                                    <button class="btn btn-default" id="TOTALNETOV" name="TOTALNETOV"><?php echo number_format($TOTALNETO, 2, ',', '.'); ?></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total Bruto</div>
                                                    <button class="btn btn-default" id="TOTALBRUTOV" name="TOTALBRUTOV"><?php echo number_format($TOTALBRUTO, 2, ',', '.'); ?></button>
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
        <?php include_once "../../assest/config/footer.php"; ?>
        <?php include_once "../../assest/config/menuExtraOpera.php"; ?>
    </div>
    <?php include_once "../../assest/config/urlBase.php"; ?>
</body>

</html>
