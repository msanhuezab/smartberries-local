<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "../../assest/controlador/CALIDADCONTROL_ADO.php";
include_once "../../assest/controlador/ESPECIES_ADO.php";

$CALIDADCONTROL_ADO = new CALIDADCONTROL_ADO();
$ESPECIES_ADO = new ESPECIES_ADO();

function excelCalidadTexto($valor) {
    return htmlspecialchars((string) $valor, ENT_QUOTES, "UTF-8");
}

function excelCalidadNumero($valor, $dec = 2) {
    if ($valor === null || $valor === "") {
        return "";
    }
    return number_format((float) $valor, $dec, ",", ".");
}

function excelCalidadEstado($fila) {
    if ((int) ($fila["TOTAL_CONTROLES"] ?? 0) <= 0) {
        return "Sin control";
    }
    if ((int) ($fila["ABIERTOS"] ?? 0) > 0) {
        return "Abierto";
    }
    return "Cerrado";
}

function excelCalidadCodigoEstado($fila) {
    if ((int) ($fila["TOTAL_CONTROLES"] ?? 0) <= 0) {
        return "SIN_CONTROL";
    }
    if ((int) ($fila["ABIERTOS"] ?? 0) > 0) {
        return "ABIERTO";
    }
    return "CERRADO";
}

$ETAPAS = array("RECEPCION", "PROCESO", "EXPORTACION");
$MODOS_ETAPA = array(
    "RECEPCION" => array("AGRUPADO", "FOLIO"),
    "PROCESO" => array("PROCESO", "PALLET"),
    "EXPORTACION" => array("PALLET", "REFERENCIA", "CONTENEDOR")
);

$ETAPA = strtoupper($_GET["ETAPA"] ?? "PROCESO");
if (!in_array($ETAPA, $ETAPAS, true)) {
    $ETAPA = "PROCESO";
}

$MODO = strtoupper($_GET["MODO"] ?? "TODOS");
if ($MODO !== "TODOS" && !in_array($MODO, $MODOS_ETAPA[$ETAPA], true)) {
    $MODO = $MODOS_ETAPA[$ETAPA][0];
}

$ID_ESPECIES = $_GET["ID_ESPECIES"] ?? "1";
$FILTRO_ESTADO = strtoupper($_GET["ESTADO"] ?? "TODOS");
if (!in_array($FILTRO_ESTADO, array("TODOS", "SIN_CONTROL", "ABIERTO", "CERRADO"), true)) {
    $FILTRO_ESTADO = "TODOS";
}

$nombreEspecie = "";
foreach ($ESPECIES_ADO->listarEspeciesCalidadCBX() as $especie) {
    if ((string) $especie["ID_ESPECIES"] === (string) $ID_ESPECIES) {
        $nombreEspecie = $especie["NOMBRE_ESPECIES"];
        break;
    }
}

function excelCalidadFilasPorModo($CALIDADCONTROL_ADO, $EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $ETAPA, $MODO) {
    if ($ETAPA === "RECEPCION") {
        return $CALIDADCONTROL_ADO->listarRevisionRecepcionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
    }
    if ($ETAPA === "PROCESO") {
        return $CALIDADCONTROL_ADO->listarRevisionProcesoCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
    }
    return $CALIDADCONTROL_ADO->listarRevisionExportacionCalidad($EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $MODO);
}

$filas = array();
$modosExportar = $MODO === "TODOS" ? $MODOS_ETAPA[$ETAPA] : array($MODO);
foreach ($modosExportar as $modoExportar) {
    foreach (excelCalidadFilasPorModo($CALIDADCONTROL_ADO, $EMPRESAS, $PLANTAS, $TEMPORADAS, $ID_ESPECIES, $ETAPA, $modoExportar) as $fila) {
        $fila["_MODO_EXPORTADO"] = $modoExportar;
        $filas[] = $fila;
    }
}

$filasFiltradas = array();
foreach ($filas as $fila) {
    $codigoEstado = excelCalidadCodigoEstado($fila);
    if ($FILTRO_ESTADO === "TODOS" || $FILTRO_ESTADO === $codigoEstado) {
        $filasFiltradas[] = $fila;
    }
}

$nombreArchivo = "calidad_" . strtolower($ETAPA) . "_" . strtolower($MODO) . "_" . date("Ymd_His") . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"" . $nombreArchivo . "\"");
header("Pragma: no-cache");
header("Expires: 0");
echo "\xEF\xBB\xBF";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        table { border-collapse: collapse; }
        th { background: #393764; color: #fff; font-weight: bold; }
        th, td { border: 1px solid #b8bdc7; padding: 5px; }
        .number { mso-number-format:"0.00"; text-align: right; }
    </style>
</head>
<body>
    <table>
        <tr>
            <th colspan="14">Revision Calidad</th>
        </tr>
        <tr>
            <td>Etapa</td>
            <td><?php echo excelCalidadTexto($ETAPA); ?></td>
            <td>Modo</td>
            <td><?php echo excelCalidadTexto($MODO); ?></td>
            <td>Especie</td>
            <td><?php echo excelCalidadTexto($nombreEspecie); ?></td>
            <td>Estado</td>
            <td><?php echo excelCalidadTexto($FILTRO_ESTADO); ?></td>
            <td>Fecha exportacion</td>
            <td><?php echo date("d-m-Y H:i:s"); ?></td>
        </tr>
    </table>
    <br>
    <table>
        <thead>
            <tr>
                <th>Estado calidad</th>
                <th>Etapa</th>
                <th>Modo</th>
                <th>ID operacion</th>
                <th>Operacion</th>
                <th>Fecha operacion</th>
                <th>Detalle</th>
                <th>Total pallets</th>
                <th>Kilos neto</th>
                <th>Total controles</th>
                <th>Abiertos</th>
                <th>Cerrados</th>
                <th>Score</th>
                <th>Resolucion</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filasFiltradas as $fila) { ?>
                <tr>
                    <td><?php echo excelCalidadTexto(excelCalidadEstado($fila)); ?></td>
                    <td><?php echo excelCalidadTexto($ETAPA); ?></td>
                    <td><?php echo excelCalidadTexto($fila["_MODO_EXPORTADO"] ?? $MODO); ?></td>
                    <td><?php echo excelCalidadTexto($fila["ID_OPERACION"] ?? ""); ?></td>
                    <td><?php echo excelCalidadTexto($fila["NUMERO_OPERACION"] ?? ""); ?></td>
                    <td><?php echo excelCalidadTexto($fila["FECHA_OPERACION"] ?? ""); ?></td>
                    <td><?php echo excelCalidadTexto($fila["DESCRIPCION"] ?? ""); ?></td>
                    <td class="number"><?php echo excelCalidadTexto($fila["TOTAL_PALLETS"] ?? ""); ?></td>
                    <td class="number"><?php echo excelCalidadTexto(excelCalidadNumero($fila["KILOS_NETO"] ?? "")); ?></td>
                    <td class="number"><?php echo excelCalidadTexto($fila["TOTAL_CONTROLES"] ?? ""); ?></td>
                    <td class="number"><?php echo excelCalidadTexto($fila["ABIERTOS"] ?? ""); ?></td>
                    <td class="number"><?php echo excelCalidadTexto($fila["CERRADOS"] ?? ""); ?></td>
                    <td class="number"><?php echo excelCalidadTexto($fila["SCORE_GENERAL"] ?? ""); ?></td>
                    <td><?php echo excelCalidadTexto($fila["RESULTADO_GENERAL"] ?? ""); ?></td>
                </tr>
            <?php } ?>
            <?php if (empty($filasFiltradas)) { ?>
                <tr>
                    <td colspan="14">Sin datos para los filtros seleccionados.</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</body>
</html>
