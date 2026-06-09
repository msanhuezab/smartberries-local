<?php
include_once "../../assest/config/validarUsuarioOpera.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES
include_once "../../assest/controlador/CONSULTA_ADO.php";
include_once "../../assest/controlador/EMPRESA_ADO.php";
include_once "../../assest/controlador/PLANTA_ADO.php";


//INICIALIZAR CONTROLADOR
$CONSULTA_ADO =  NEW CONSULTA_ADO;
$EMPRESA_ADO = new EMPRESA_ADO();
$PLANTA_ADO = new PLANTA_ADO();
//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD


//INICIALIZAR ARREGLOS
$ARRAYLISTAREMPRESA="";
$ARRAYLISTARPLANTA="";

// Cargar listas base
$ARRAYLISTAREMPRESA = $EMPRESA_ADO->listarEmpresaCBX();
$ARRAYLISTARPLANTA  = $PLANTA_ADO->listarPlantaPropiaCBX();

// Excluir empresa 5
$ARRAYLISTAREMPRESA = array_values(array_filter($ARRAYLISTAREMPRESA, function($e){ return $e['ID_EMPRESA'] != 5; }));

// Calcular totales sumando por empresa (sin filtro ESPECIE para evitar valores en 0)
$TOTALRECECPCIOANDO    = 0;
$TOTALRECECPCIOANDOBULK = 0;
$TOTALPROCESADO        = 0;
$TOTALEXISTENCIAMP     = 0;
foreach ($ARRAYLISTAREMPRESA as $r) {
    $tmp = $CONSULTA_ADO->acumuladoRecepcionMpPorEmpresa($r['ID_EMPRESA'], $TEMPORADAS);
    $TOTALRECECPCIOANDO += (float)($tmp[0]['NETO'] ?? 0);
    $tmp = $CONSULTA_ADO->acumuladoProcesadoMpPorEmpresa($r['ID_EMPRESA'], $TEMPORADAS);
    $TOTALPROCESADO += (float)($tmp[0]['NETO'] ?? 0);
    $tmp = $CONSULTA_ADO->existenciaDisponibleMpPorEmpresa($r['ID_EMPRESA'], $TEMPORADAS);
    $TOTALEXISTENCIAMP += (float)($tmp[0]['NETO'] ?? 0);
}
$_especie = $ESPECIE ?? '';
$ARRAYRECEPCIONBULKMP   = $CONSULTA_ADO->acumuladoRecepcionMpBulkEst($TEMPORADAS, $_especie);
$TOTALRECECPCIOANDOBULK = (float)($ARRAYRECEPCIONBULKMP[0]['NETO'] ?? 0);


?>


<!DOCTYPE html>
<html lang="es">
<head>
    <title>Estadísticas</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>
<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
<div class="wrapper">
    <?php include_once "../../assest/config/menuOpera.php"; ?>

    <div class="content-wrapper">
        <div class="container-full">

            <!-- Encabezado -->
            <div class="content-header">
                <div class="d-flex align-items-center">
                    <div class="mr-auto">
                        <h3 class="page-title">Estadísticas</h3>
                        <div class="d-inline-block align-items-center">
                            <nav>
                                <ol class="breadcrumb">
                                    <li class="breadcrumb-item"><a href="#"><i class="mdi mdi-home-outline"></i></a></li>
                                    <li class="breadcrumb-item active">Estadísticas</li>
                                </ol>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <section class="content">

                <!-- ─── KPI Cards ─── -->
                <div class="row">
                    <?php if($PESTARVSP=="1"): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="box">
                            <div class="box-body">
                                <div class="d-flex align-items-center">
                                    <div class="mr-10">
                                        <span class="bg-success-light rounded p-15 d-flex"><i class="fa fa-arrow-down fa-2x text-success"></i></span>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size:12px;">TOTAL RECEPCIÓN MP</p>
                                        <h4 class="mb-0 font-weight-700"><?php echo number_format((float)$TOTALRECECPCIOANDO, 0, ',', '.'); ?> <small class="text-muted" style="font-size:12px;">kg</small></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="box">
                            <div class="box-body">
                                <div class="d-flex align-items-center">
                                    <div class="mr-10">
                                        <span class="bg-primary-light rounded p-15 d-flex"><i class="fa fa-arrow-down fa-2x text-primary"></i></span>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size:12px;">RECEPCIÓN BULK</p>
                                        <h4 class="mb-0 font-weight-700"><?php echo number_format((float)$TOTALRECECPCIOANDOBULK, 0, ',', '.'); ?> <small class="text-muted" style="font-size:12px;">kg</small></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="box">
                            <div class="box-body">
                                <div class="d-flex align-items-center">
                                    <div class="mr-10">
                                        <span class="bg-warning-light rounded p-15 d-flex"><i class="fa fa-cogs fa-2x text-warning"></i></span>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size:12px;">TOTAL PROCESADO</p>
                                        <h4 class="mb-0 font-weight-700"><?php echo number_format((float)$TOTALPROCESADO, 0, ',', '.'); ?> <small class="text-muted" style="font-size:12px;">kg</small></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if($PESTASTOPMP=="1"): ?>
                    <div class="col-xl-3 col-md-6">
                        <div class="box">
                            <div class="box-body">
                                <div class="d-flex align-items-center">
                                    <div class="mr-10">
                                        <span class="bg-danger-light rounded p-15 d-flex"><i class="fa fa-cubes fa-2x text-danger"></i></span>
                                    </div>
                                    <div>
                                        <p class="text-muted mb-0" style="font-size:12px;">EXISTENCIA DISPONIBLE MP</p>
                                        <h4 class="mb-0 font-weight-700"><?php echo number_format((float)$TOTALEXISTENCIAMP, 0, ',', '.'); ?> <small class="text-muted" style="font-size:12px;">kg</small></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- ─── Tabla Recepción VS Proceso ─── -->
                <?php if($PESTARVSP=="1"): ?>
                <div class="box">
                    <div class="box-header with-border">
                        <h4 class="box-title"><i class="fa fa-bar-chart text-primary mr-5"></i> Recepción vs Proceso</h4>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped" id="resumen" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Empresa / Planta</th>
                                        <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                            <th class="text-center"><?php echo $s["NOMBRE_PLANTA"]; ?><br><small>Recepción</small></th>
                                            <th class="text-center"><?php echo $s["NOMBRE_PLANTA"]; ?><br><small>Proceso</small></th>
                                        <?php endforeach; ?>
                                        <th class="text-center">Total<br><small>Recepción</small></th>
                                        <th class="text-center">Total<br><small>Procesado</small></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ARRAYLISTAREMPRESA as $r): ?>
                                        <?php
                                            $ARRAYRECEPCIONMPEMPRESA  = $CONSULTA_ADO->acumuladoRecepcionMpPorEmpresa($r["ID_EMPRESA"], $TEMPORADAS);
                                            $ARRAYPROCESADOMPEMPRESA  = $CONSULTA_ADO->acumuladoProcesadoMpPorEmpresa($r["ID_EMPRESA"], $TEMPORADAS);
                                            $_recEmp  = (float)($ARRAYRECEPCIONMPEMPRESA[0]["NETO"] ?? 0);
                                            $_procEmp = (float)($ARRAYPROCESADOMPEMPRESA[0]["NETO"] ?? 0);
                                        ?>
                                        <tr>
                                            <th><?php echo $r["NOMBRE_EMPRESA"]; ?></th>
                                            <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                                <?php
                                                    $ARRAYRECEPCIONMPEMPRESAPLANTA  = $CONSULTA_ADO->acumuladoRecepcionMpPorEmpresaPlanta($r["ID_EMPRESA"], $s["ID_PLANTA"], $TEMPORADAS);
                                                    $ARRAYPROCESADOMPEMPRESAPLANTA  = $CONSULTA_ADO->acumuladoProcesadoMpPorEmpresaPlanta($r["ID_EMPRESA"], $s["ID_PLANTA"], $TEMPORADAS);
                                                ?>
                                                <td class="text-right"><?php echo number_format((float)($ARRAYRECEPCIONMPEMPRESAPLANTA[0]["NETO"] ?? 0), 0, ',', '.'); ?></td>
                                                <td class="text-right"><?php echo number_format((float)($ARRAYPROCESADOMPEMPRESAPLANTA[0]["NETO"] ?? 0), 0, ',', '.'); ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-right"><strong><?php echo number_format($_recEmp, 0, ',', '.'); ?></strong></td>
                                            <td class="text-right"><strong><?php echo number_format($_procEmp, 0, ',', '.'); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <?php
                                    // Recolectar subtotales por planta para no consultar dos veces
                                    $_subtotales = [];
                                    foreach ($ARRAYLISTARPLANTA as $s) {
                                        $rec  = $CONSULTA_ADO->acumuladoRecepcionMpPorPlanta($s["ID_PLANTA"], $TEMPORADAS);
                                        $proc = $CONSULTA_ADO->acumuladoProcesadoMpPorPlanta($s["ID_PLANTA"], $TEMPORADAS);
                                        $_subtotales[$s["ID_PLANTA"]] = [
                                            'rec'  => (float)($rec[0]['NETO']  ?? 0),
                                            'proc' => (float)($proc[0]['NETO'] ?? 0),
                                        ];
                                    }
                                    ?>
                                    <tr style="background:#f5f5f5;">
                                        <th>Sub Total</th>
                                        <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                            <td class="text-right"><?php echo number_format($_subtotales[$s["ID_PLANTA"]]['rec'],  0, ',', '.'); ?></td>
                                            <td class="text-right"><?php echo number_format($_subtotales[$s["ID_PLANTA"]]['proc'], 0, ',', '.'); ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right"><strong><?php echo number_format($TOTALRECECPCIOANDO, 0, ',', '.'); ?></strong></td>
                                        <td class="text-right"><strong><?php echo number_format($TOTALPROCESADO, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                    <tr style="background:#eaf4ea;">
                                        <th>% Procesado</th>
                                        <?php foreach ($ARRAYLISTARPLANTA as $s):
                                            $_r = $_subtotales[$s["ID_PLANTA"]]['rec'];
                                            $_p = $_subtotales[$s["ID_PLANTA"]]['proc'];
                                            $_pct = $_r > 0 ? round($_p / $_r * 100, 1) : 0;
                                            $_color = $_pct >= 80 ? 'badge-success' : ($_pct >= 50 ? 'badge-warning' : 'badge-danger');
                                        ?>
                                            <td class="text-right text-muted">—</td>
                                            <td class="text-right"><span class="badge <?php echo $_color; ?>"><?php echo $_pct; ?>%</span></td>
                                        <?php endforeach; ?>
                                        <td class="text-right text-muted">—</td>
                                        <td class="text-right">
                                            <?php
                                                $_pctTotal = $TOTALRECECPCIOANDO > 0 ? round($TOTALPROCESADO / $TOTALRECECPCIOANDO * 100, 1) : 0;
                                                $_colorTotal = $_pctTotal >= 80 ? 'badge-success' : ($_pctTotal >= 50 ? 'badge-warning' : 'badge-danger');
                                            ?>
                                            <strong><span class="badge <?php echo $_colorTotal; ?>"><?php echo $_pctTotal; ?>%</span></strong>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- ─── Tabla Existencia MP ─── -->
                <?php if($PESTASTOPMP=="1"): ?>
                <div class="box">
                    <div class="box-header with-border">
                        <h4 class="box-title"><i class="fa fa-cubes text-danger mr-5"></i> Existencia Materia Prima</h4>
                    </div>
                    <div class="box-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-striped" id="stockmp" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>Empresa / Planta</th>
                                        <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                            <th class="text-center"><?php echo $s["NOMBRE_PLANTA"]; ?></th>
                                        <?php endforeach; ?>
                                        <th class="text-center">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ARRAYLISTAREMPRESA as $r): ?>
                                        <?php $ARRAYEXISTENCIAMPEMPRESA = $CONSULTA_ADO->existenciaDisponibleMpPorEmpresa($r["ID_EMPRESA"], $TEMPORADAS); ?>
                                        <tr>
                                            <th><?php echo $r["NOMBRE_EMPRESA"]; ?></th>
                                            <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                                <?php $ARRAYEXISTENCIAMPEMPRESAPLANTA = $CONSULTA_ADO->existenciaDisponibleMpPorEmpresaPlanta($r["ID_EMPRESA"], $s["ID_PLANTA"], $TEMPORADAS); ?>
                                                <td class="text-right"><?php echo number_format((float)($ARRAYEXISTENCIAMPEMPRESAPLANTA[0]["NETO"] ?? 0), 0, ',', '.'); ?></td>
                                            <?php endforeach; ?>
                                            <td class="text-right"><strong><?php echo number_format((float)($ARRAYEXISTENCIAMPEMPRESA[0]["NETO"] ?? 0), 0, ',', '.'); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr style="background:#f5f5f5;">
                                        <th>Total</th>
                                        <?php foreach ($ARRAYLISTARPLANTA as $s): ?>
                                            <?php $ARRAYEXISTENCIAMPEMPRESAPLANTA = $CONSULTA_ADO->existenciaDisponibleMpPorPlanta($s["ID_PLANTA"], $TEMPORADAS); ?>
                                            <td class="text-right"><?php echo number_format((float)($ARRAYEXISTENCIAMPEMPRESAPLANTA[0]["NETO"] ?? 0), 0, ',', '.'); ?></td>
                                        <?php endforeach; ?>
                                        <td class="text-right"><strong><?php echo number_format($TOTALEXISTENCIAMP, 0, ',', '.'); ?></strong></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            </section>
        </div>
    </div>

    <?php include_once "../../assest/config/footer.php"; ?>
    <?php include_once "../../assest/config/menuExtraOpera.php"; ?>
</div>
<?php include_once "../../assest/config/urlBase.php"; ?>
</body>
</html>
