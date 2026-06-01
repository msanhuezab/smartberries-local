<?php
include_once "../../assest/config/validarUsuarioExpo.php";
include_once "../../assest/config/BDCONFIG.php";

$db = BDCONFIG::conectar();
$IDICARGA = isset($_REQUEST['ICARGA']) ? (int)$_REQUEST['ICARGA'] : 0;

function h($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
}

function numero($valor) {
    return number_format((float)$valor, 2, ',', '.');
}

function excelNumero($valor, $decimales = 2) {
    return number_format((float)$valor, $decimales, '.', '');
}

function xmlTexto($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function esComision($nombre) {
    return stripos($nombre, 'COMISI') !== false;
}

function obtenerItemsLiquidacion($db, $empresa) {
    $stmt = $db->prepare("
        SELECT ID_TITEM, NOMBRE_TITEM, IFNULL(TIPO_GASTO, 'GASTO') AS TIPO_GASTO
        FROM liquidacion_titem
        WHERE ESTADO_REGISTRO = 1
        AND TAITEM = 1
        AND ID_EMPRESA = ?
        ORDER BY NUMERO_TITEM ASC, ID_TITEM ASC
    ");
    $stmt->execute([$empresa]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerLiquidaciones($db, $empresa, $temporada) {
    $stmt = $db->prepare("
        SELECT
            ID_ICARGA,
            MAX(ID_VALOR) AS ID_VALOR,
            MAX(CONDICION_FLETE) AS CONDICION_FLETE,
            MAX(NUMERO_VALOR) AS NUMERO_VALOR,
            MAX(FECHA_VALOR) AS FECHA_VALOR,
            MAX(NUMERO_ICARGA) AS NUMERO_ICARGA,
            MAX(NREFERENCIA_ICARGA) AS NREFERENCIA_ICARGA,
            MAX(NCONTENEDOR_ICARGA) AS NCONTENEDOR_ICARGA,
            MAX(NOMBRE_MERCADO) AS NOMBRE_MERCADO,
            MAX(NOMBRE_CONSIGNATARIO) AS NOMBRE_CONSIGNATARIO,
            COUNT(ID_EXIEXPORTACION) AS LINEAS,
            SUM(CANTIDAD_ENVASE) AS ENVASES,
            SUM(KILOS_NETO) AS NETO,
            SUM(VENTA_USD) AS VENTA,
            MAX(ESTADO_LIQUIDACION) AS ESTADO_LIQUIDACION
        FROM view_liquidacion_exportacion
        WHERE ID_EMPRESA = ?
        AND ID_TEMPORADA = ?
        GROUP BY ID_ICARGA
        ORDER BY ID_ICARGA DESC
    ");
    $stmt->execute([$empresa, $temporada]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerGastosPorValor($db, $idsValor) {
    if (count($idsValor) === 0) {
        return [];
    }
    $marcas = implode(',', array_fill(0, count($idsValor), '?'));
    $stmt = $db->prepare("
        SELECT
            dv.ID_VALOR,
            dv.ID_TITEM,
            ti.NOMBRE_TITEM,
            SUM(IFNULL(dv.VALOR_DVALOR, 0)) AS VALOR_DVALOR
        FROM liquidacion_dvalor dv
        INNER JOIN liquidacion_titem ti ON ti.ID_TITEM = dv.ID_TITEM
            AND ti.ESTADO_REGISTRO = 1
            AND ti.TAITEM = 1
        WHERE dv.ESTADO_REGISTRO = 1
        AND dv.ID_VALOR IN ($marcas)
        GROUP BY dv.ID_VALOR, dv.ID_TITEM
    ");
    $stmt->execute($idsValor);
    $gastos = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $gasto) {
        $gastos[(int)$gasto['ID_VALOR']][(int)$gasto['ID_TITEM']] = [
            'nombre' => $gasto['NOMBRE_TITEM'],
            'valor' => (float)$gasto['VALOR_DVALOR']
        ];
    }
    return $gastos;
}

function obtenerLineasExportacion($db, $empresa, $temporada, $idIcarga) {
    $params = [$empresa, $temporada];
    $filtro = "";
    if ($idIcarga > 0) {
        $filtro = "AND ID_ICARGA = ?";
        $params[] = $idIcarga;
    }

    $stmt = $db->prepare("
        SELECT
            ID_ICARGA,
            MAX(ID_VALOR) AS ID_VALOR,
            MAX(CONDICION_FLETE) AS CONDICION_FLETE,
            MAX(NUMERO_VALOR) AS NUMERO_VALOR,
            MAX(FECHA_VALOR) AS FECHA_VALOR,
            MAX(NUMERO_ICARGA) AS NUMERO_ICARGA,
            MAX(NREFERENCIA_ICARGA) AS NREFERENCIA_ICARGA,
            MAX(NCONTENEDOR_ICARGA) AS NCONTENEDOR_ICARGA,
            MAX(NOMBRE_MERCADO) AS NOMBRE_MERCADO,
            MAX(NOMBRE_CONSIGNATARIO) AS NOMBRE_CONSIGNATARIO,
            GROUP_CONCAT(DISTINCT FOLIO_EXIEXPORTACION ORDER BY FOLIO_EXIEXPORTACION SEPARATOR ', ') AS FOLIOS,
            MAX(CSG_PRODUCTOR) AS CSG_PRODUCTOR,
            MAX(NOMBRE_PRODUCTOR) AS NOMBRE_PRODUCTOR,
            MAX(NOMBRE_VESPECIES) AS NOMBRE_VESPECIES,
            MAX(NOMBRE_ESTANDAR) AS NOMBRE_ESTANDAR,
            MAX(NOMBRE_TCALIBRE) AS NOMBRE_TCALIBRE,
            ID_PRODUCTOR,
            ID_VESPECIES,
            ID_ESTANDAR,
            ID_TCALIBRE,
            SUM(CANTIDAD_ENVASE) AS CANTIDAD_ENVASE,
            SUM(KILOS_NETO) AS KILOS_NETO,
            SUM(KILOS_BRUTO) AS KILOS_BRUTO,
            MAX(FOB_REFERENCIA_CAJA) AS FOB_REFERENCIA_CAJA,
            CASE WHEN SUM(KILOS_NETO) > 0 THEN SUM(VENTA_USD) / SUM(KILOS_NETO) ELSE 0 END AS FOB_FINAL_KG,
            CASE WHEN SUM(CANTIDAD_ENVASE) > 0 THEN SUM(VENTA_USD) / SUM(CANTIDAD_ENVASE) ELSE 0 END AS FOB_VENTA_CAJA,
            SUM(VENTA_USD) AS VENTA_USD,
            MAX(OBSERVACION) AS OBSERVACION,
            MAX(ESTADO_LIQUIDACION) AS ESTADO_LIQUIDACION
        FROM view_liquidacion_exportacion
        WHERE ID_EMPRESA = ?
        AND ID_TEMPORADA = ?
        $filtro
        GROUP BY ID_ICARGA, ID_PRODUCTOR, ID_VESPECIES, ID_ESTANDAR, ID_TCALIBRE
        ORDER BY ID_ICARGA DESC, NOMBRE_PRODUCTOR, NOMBRE_VESPECIES, NOMBRE_ESTANDAR, NOMBRE_TCALIBRE
    ");
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function calcularTotalesLineas($lineas) {
    $totales = [];
    foreach ($lineas as $linea) {
        $idIcarga = (int)$linea['ID_ICARGA'];
        if (!isset($totales[$idIcarga])) {
            $totales[$idIcarga] = [
                'neto' => 0,
                'venta' => 0
            ];
        }
        $kgCaja = (int)$linea['CANTIDAD_ENVASE'] > 0 ? (float)$linea['KILOS_NETO'] / (int)$linea['CANTIDAD_ENVASE'] : 0;
        $fobRefKg = $kgCaja > 0 ? (float)$linea['FOB_REFERENCIA_CAJA'] / $kgCaja : 0;
        $fobFinalKg = (float)$linea['FOB_FINAL_KG'] > 0 ? (float)$linea['FOB_FINAL_KG'] : $fobRefKg;
        $venta = isset($linea['VENTA_USD']) ? (float)$linea['VENTA_USD'] : (float)$linea['KILOS_NETO'] * $fobFinalKg;
        $totales[$idIcarga]['neto'] += (float)$linea['KILOS_NETO'];
        $totales[$idIcarga]['venta'] += $venta;
    }
    return $totales;
}

function excelCeldaTexto($valor) {
    return '<Cell><Data ss:Type="String">' . xmlTexto($valor) . '</Data></Cell>';
}

function excelCeldaNumero($valor, $decimales = 2) {
    return '<Cell><Data ss:Type="Number">' . excelNumero($valor, $decimales) . '</Data></Cell>';
}

function escribirExcel($lineas, $items, $gastos, $totales) {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="liquidaciones_exportacion.xls"');
    $salida = fopen('php://output', 'w');
    fprintf($salida, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fwrite($salida, '<?xml version="1.0" encoding="UTF-8"?>' . "\r\n");
    fwrite($salida, '<?mso-application progid="Excel.Sheet"?>' . "\r\n");
    fwrite($salida, '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\r\n");
    fwrite($salida, '<Styles><Style ss:ID="numero2"><NumberFormat ss:Format="0.00"/></Style><Style ss:ID="numero4"><NumberFormat ss:Format="0.0000"/></Style></Styles>' . "\r\n");
    fwrite($salida, '<Worksheet ss:Name="Liquidaciones"><Table>' . "\r\n");

    $cabecera = [
        'Estado',
        'N Liquidacion',
        'Fecha',
        'Condicion Flete',
        'Instructivo',
        'Referencia',
        'Contenedor',
        'Mercado',
        'Cliente',
        'Folios',
        'CSG',
        'Productor',
        'Variedad',
        'Estandar',
        'Calibre',
        'Cajas',
        'Kg Neto',
        'Kg Bruto',
        'FOB Ref Caja',
        'FOB Ref Kg',
        'FOB Venta Caja',
        'FOB Venta Kg',
        'Venta US$'
    ];
    foreach ($items as $item) {
        $cabecera[] = 'Prorrateo ' . $item['NOMBRE_TITEM'];
    }
    $cabecera[] = 'Comision Prorrateada';
    $cabecera[] = 'Gastos Prorrateados';
    $cabecera[] = 'Retorno Neto';
    $cabecera[] = 'FOB Final Caja';
    $cabecera[] = 'FOB Final Kg';
    $cabecera[] = 'Observacion';
    fwrite($salida, '<Row>');
    foreach ($cabecera as $titulo) {
        fwrite($salida, excelCeldaTexto($titulo));
    }
    fwrite($salida, "</Row>\r\n");

    foreach ($lineas as $linea) {
        $idValor = (int)$linea['ID_VALOR'];
        $idIcarga = (int)$linea['ID_ICARGA'];
        $kgCaja = (int)$linea['CANTIDAD_ENVASE'] > 0 ? (float)$linea['KILOS_NETO'] / (int)$linea['CANTIDAD_ENVASE'] : 0;
        $fobRefKg = $kgCaja > 0 ? (float)$linea['FOB_REFERENCIA_CAJA'] / $kgCaja : 0;
        $fobVentaKg = (float)$linea['FOB_FINAL_KG'] > 0 ? (float)$linea['FOB_FINAL_KG'] : $fobRefKg;
        $venta = isset($linea['VENTA_USD']) ? (float)$linea['VENTA_USD'] : (float)$linea['KILOS_NETO'] * $fobVentaKg;
        $fobVentaCaja = isset($linea['FOB_VENTA_CAJA']) ? (float)$linea['FOB_VENTA_CAJA'] : ($kgCaja > 0 ? $fobVentaKg * $kgCaja : 0);
        $totalNeto = $totales[$idIcarga]['neto'] ?? 0;
        $totalVenta = $totales[$idIcarga]['venta'] ?? 0;
        $comisionProrrateada = 0;
        $gastosProrrateados = 0;

        fwrite($salida, '<Row>');
        fwrite($salida, excelCeldaTexto($linea['ESTADO_LIQUIDACION']));
        fwrite($salida, excelCeldaTexto($linea['NUMERO_VALOR'] ?? ''));
        fwrite($salida, excelCeldaTexto($linea['FECHA_VALOR']));
        fwrite($salida, excelCeldaTexto($linea['CONDICION_FLETE'] ?? 'COLLECT'));
        fwrite($salida, excelCeldaTexto($linea['NUMERO_ICARGA']));
        fwrite($salida, excelCeldaTexto($linea['NREFERENCIA_ICARGA']));
        fwrite($salida, excelCeldaTexto($linea['NCONTENEDOR_ICARGA']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_MERCADO']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_CONSIGNATARIO']));
        fwrite($salida, excelCeldaTexto($linea['FOLIOS']));
        fwrite($salida, excelCeldaTexto($linea['CSG_PRODUCTOR']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_PRODUCTOR']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_VESPECIES']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_ESTANDAR']));
        fwrite($salida, excelCeldaTexto($linea['NOMBRE_TCALIBRE']));
        fwrite($salida, excelCeldaNumero($linea['CANTIDAD_ENVASE'], 0));
        fwrite($salida, excelCeldaNumero($linea['KILOS_NETO'], 2));
        fwrite($salida, excelCeldaNumero($linea['KILOS_BRUTO'], 2));
        fwrite($salida, excelCeldaNumero($linea['FOB_REFERENCIA_CAJA'], 4));
        fwrite($salida, excelCeldaNumero($fobRefKg, 4));
        fwrite($salida, excelCeldaNumero($fobVentaCaja, 4));
        fwrite($salida, excelCeldaNumero($fobVentaKg, 4));
        fwrite($salida, excelCeldaNumero($venta, 4));

        foreach ($items as $item) {
            $idTitem = (int)$item['ID_TITEM'];
            $valorGasto = $gastos[$idValor][$idTitem]['valor'] ?? 0;
            $tipoGasto = $item['TIPO_GASTO'] ?? 'GASTO';
            if ($tipoGasto === 'COMISION' || esComision($item['NOMBRE_TITEM'])) {
                $prorrata = $totalVenta > 0 ? $valorGasto * ($venta / $totalVenta) : 0;
                $comisionProrrateada += $prorrata;
            } elseif (($linea['CONDICION_FLETE'] ?? 'COLLECT') === 'PREPAID' && $tipoGasto === 'FLETE') {
                $prorrata = 0;
            } else {
                $prorrata = $totalNeto > 0 ? $valorGasto * ((float)$linea['KILOS_NETO'] / $totalNeto) : 0;
                $gastosProrrateados += $prorrata;
            }
            fwrite($salida, excelCeldaNumero($prorrata, 4));
        }

        fwrite($salida, excelCeldaNumero($comisionProrrateada, 4));
        fwrite($salida, excelCeldaNumero($gastosProrrateados, 4));
        $retornoNeto = $venta - $comisionProrrateada - $gastosProrrateados;
        $fobFinalKg = (float)$linea['KILOS_NETO'] > 0 ? $retornoNeto / (float)$linea['KILOS_NETO'] : 0;
        $fobFinalCaja = $fobFinalKg * $kgCaja;
        fwrite($salida, excelCeldaNumero($retornoNeto, 4));
        fwrite($salida, excelCeldaNumero($fobFinalCaja, 4));
        fwrite($salida, excelCeldaNumero($fobFinalKg, 4));
        fwrite($salida, excelCeldaTexto($linea['OBSERVACION']));
        fwrite($salida, "</Row>\r\n");
    }
    fwrite($salida, '</Table></Worksheet></Workbook>');
    fclose($salida);
    exit;
}

$ITEMS = obtenerItemsLiquidacion($db, $EMPRESAS);
$LIQUIDACIONES = obtenerLiquidaciones($db, $EMPRESAS, $TEMPORADAS);

if (isset($_GET['EXPORTAR'])) {
    $LINEAS = obtenerLineasExportacion($db, $EMPRESAS, $TEMPORADAS, $IDICARGA);
    $idsValor = array_values(array_unique(array_map(function ($linea) {
        return (int)$linea['ID_VALOR'];
    }, $LINEAS)));
    $idsValor = array_values(array_filter($idsValor, function ($idValor) {
        return $idValor > 0;
    }));
    $GASTOS = obtenerGastosPorValor($db, $idsValor);
    $TOTALES = calcularTotalesLineas($LINEAS);
    escribirExcel($LINEAS, $ITEMS, $GASTOS, $TOTALES);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Exportar Liquidaciones</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuExpo.php"; ?>
    <div class="content-wrapper">
        <div class="container-full">
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Exportar Liquidaciones</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item">Liquidacion</li>
                                    <li class="breadcrumb-item active">Exportar</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                    <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                </div>
            </div>

            <section class="content">
                <div class="box">
                    <div class="box-header with-border bg-primary">
                        <h4 class="box-title">Liquidaciones con gastos asociados</h4>
                    </div>
                    <form method="get">
                        <div class="box-body">
                            <div class="row">
                                <div class="col-md-8">
                                    <label>Referencia</label>
                                    <select class="form-control select2" name="ICARGA" style="width:100%">
                                        <option value="0">Todas las referencias de la temporada</option>
                                        <?php foreach ($LIQUIDACIONES as $liquidacion) { ?>
                                            <option value="<?php echo (int)$liquidacion['ID_ICARGA']; ?>" <?php echo $IDICARGA === (int)$liquidacion['ID_ICARGA'] ? 'selected' : ''; ?>>
                                                <?php echo h($liquidacion['NUMERO_ICARGA'] . ' - ' . $liquidacion['NREFERENCIA_ICARGA'] . ' - ' . $liquidacion['NOMBRE_MERCADO'] . ' - ' . $liquidacion['NOMBRE_CONSIGNATARIO'] . ' - ' . $liquidacion['NCONTENEDOR_ICARGA'] . ' - ' . $liquidacion['ESTADO_LIQUIDACION']); ?>
                                            </option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-4 pt-30">
                                    <button type="submit" class="btn btn-success" name="EXPORTAR" value="1">
                                        <i class="ti-download"></i> Exportar Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="box">
                    <div class="box-header with-border">
                        <h4 class="box-title">Referencias disponibles</h4>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table id="listar" class="table table-bordered table-hover" style="width:100%">
                                <thead>
                                    <tr class="text-center">
                                        <th>Estado</th>
                                        <th>N</th>
                                        <th>Fecha</th>
                                        <th>Referencia</th>
                                        <th>Mercado</th>
                                        <th>Cliente</th>
                                        <th>Contenedor</th>
                                        <th>Lineas</th>
                                        <th>Cajas</th>
                                        <th>Kg Neto</th>
                                        <th>Venta US$</th>
                                        <th>Exportar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($LIQUIDACIONES as $liquidacion) { ?>
                                        <tr>
                                            <td><?php echo h($liquidacion['ESTADO_LIQUIDACION']); ?></td>
                                            <td><?php echo h($liquidacion['NUMERO_VALOR']); ?></td>
                                            <td><?php echo h($liquidacion['FECHA_VALOR']); ?></td>
                                            <td><?php echo h($liquidacion['NREFERENCIA_ICARGA']); ?></td>
                                            <td><?php echo h($liquidacion['NOMBRE_MERCADO']); ?></td>
                                            <td><?php echo h($liquidacion['NOMBRE_CONSIGNATARIO']); ?></td>
                                            <td><?php echo h($liquidacion['NCONTENEDOR_ICARGA']); ?></td>
                                            <td class="text-right"><?php echo (int)$liquidacion['LINEAS']; ?></td>
                                            <td class="text-right"><?php echo (int)$liquidacion['ENVASES']; ?></td>
                                            <td class="text-right"><?php echo numero($liquidacion['NETO']); ?></td>
                                            <td class="text-right"><?php echo numero($liquidacion['VENTA']); ?></td>
                                            <td class="text-center">
                                                <a class="btn btn-sm btn-primary" href="exportarLiquidacionExp.php?ICARGA=<?php echo (int)$liquidacion['ID_ICARGA']; ?>&EXPORTAR=1">
                                                    <i class="ti-download"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>
    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraExpo.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
</body>
</html>
