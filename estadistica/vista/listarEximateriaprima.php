<?php

include_once "../../assest/config/validarUsuarioOpera.php";
include_once '../../assest/controlador/EXIMATERIAPRIMA_ADO.php';

$EXIMATERIAPRIMA_ADO = new EXIMATERIAPRIMA_ADO();
$ARRAYEXIMATERIAPRIMA = [];

if ($TEMPORADAS) {
    $ARRAYEXIMATERIAPRIMA = $EXIMATERIAPRIMA_ADO->listarEximateriaprimaTemporadaDisponibleView($TEMPORADAS);
}

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Existencia Disponible MP</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <?php include_once "../../assest/config/urlHead.php"; ?>
    <script type="text/javascript">
        function irPagina(url) { location.href = url; }
    </script>
    <style>
        @keyframes siren-pulse {
            0%, 100% { color: #cc0000; transform: scale(1) rotate(-10deg); }
            50%       { color: #ff6600; transform: scale(1.25) rotate(10deg); }
        }
        .icono-sirena { display: inline-block; animation: siren-pulse 0.6s ease-in-out infinite; }
    </style>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuOpera.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Existencia</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item">Modulo</li>
                                        <li class="breadcrumb-item">Existencia</li>
                                        <li class="breadcrumb-item active"><a href="#">Existencia Materia Prima</a></li>
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
                            <div class="row">
                                <div class="col-12">
                                    <div class="table-responsive">
                                        <table id="existenciamp_estadisticas" class="table table-bordered table-hover table-striped" style="width:100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th><i class="fa fa-bell"></i></th>
                                                    <th>Folio Original</th>
                                                    <th>Folio Nuevo</th>
                                                    <th>Fecha Cosecha</th>
                                                    <th>Estado</th>
                                                    <th>Estado Calidad</th>
                                                    <th>Días</th>
                                                    <th>Código Estandar</th>
                                                    <th>Envase/Estandar</th>
                                                    <th>CSG</th>
                                                    <th>Productor</th>
                                                    <th>Especie</th>
                                                    <th>Variedad</th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Kilos Neto</th>
                                                    <th>Kilos Promedio</th>
                                                    <th>Kilos Bruto</th>
                                                    <th>N° Recepción</th>
                                                    <th>Fecha Recepción</th>
                                                    <th>Tipo Recepción</th>
                                                    <th>CSG/CSP Recepción</th>
                                                    <th>Origen Recepción</th>
                                                    <th>N° Guía Recepción</th>
                                                    <th>Fecha Guía Recepción</th>
                                                    <th>Tipo Manejo</th>
                                                    <th>Gasificación</th>
                                                    <th>Tipo Tratamiento 2</th>
                                                    <th>Ingreso</th>
                                                    <th>Modificación</th>
                                                    <th>Empresa</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYEXIMATERIAPRIMA as $r) : ?>
                                                    <?php
                                                        $fechaRec = !empty($r['FECHA_RECEPCION']) ? DateTime::createFromFormat('d-m-Y', trim($r['FECHA_RECEPCION'])) : false;
                                                        $alertaRec = $fechaRec ? ((new DateTime())->diff($fechaRec)->days > 3) : false;
                                                    ?>
                                                    <tr class="text-center">
                                                        <td><?php if ($alertaRec): ?><span class="icono-sirena">🚨</span><?php endif; ?></td>
                                                        <td><span class="<?php echo $r['COLOR_BADGE']; ?>"><?php echo $r['FOLIO_EXIMATERIAPRIMA']; ?></span></td>
                                                        <td><span class="<?php echo $r['COLOR_BADGE']; ?>"><?php echo $r['FOLIO_AUXILIAR_EXIMATERIAPRIMA']; ?></span></td>
                                                        <td><?php echo $r['COSECHA']; ?></td>
                                                        <td><?php echo $r['ESTADO_LABEL']; ?></td>
                                                        <td><?php echo $r['COLOR_LABEL']; ?></td>
                                                        <td><?php echo $r['DIAS']; ?></td>
                                                        <td><?php echo $r['CODIGO_ESTANDAR']; ?></td>
                                                        <td><?php echo $r['NOMBRE_ESTANDAR']; ?></td>
                                                        <td><?php echo $r['CSG_PRODUCTOR']; ?></td>
                                                        <td><?php echo $r['NOMBRE_PRODUCTOR']; ?></td>
                                                        <td><?php echo $r['NOMBRE_ESPECIES']; ?></td>
                                                        <td><?php echo $r['NOMBRE_VESPECIES']; ?></td>
                                                        <td><?php echo $r['ENVASE']; ?></td>
                                                        <td><?php echo $r['NETO']; ?></td>
                                                        <td><?php echo $r['PROMEDIO']; ?></td>
                                                        <td><?php echo $r['BRUTO']; ?></td>
                                                        <td><?php echo $r['NUMERO_RECEPCION']; ?></td>
                                                        <td><?php echo $r['FECHA_RECEPCION']; ?></td>
                                                        <td><?php echo $r['TIPO_RECEPCION']; ?></td>
                                                        <td><?php echo $r['CSG_ORIGEN']; ?></td>
                                                        <td><?php echo $r['ORIGEN']; ?></td>
                                                        <td><?php echo $r['NUMERO_GUIA_RECEPCION']; ?></td>
                                                        <td><?php echo $r['FECHA_GUIA_RECEPCION']; ?></td>
                                                        <td><?php echo $r['NOMBRE_TMANEJO']; ?></td>
                                                        <td><?php echo $r['GASIFICADO']; ?></td>
                                                        <td><?php echo $r['NOMBRE_TTRATAMIENTO2']; ?></td>
                                                        <td><?php echo $r['INGRESO']; ?></td>
                                                        <td><?php echo $r['MODIFICACION']; ?></td>
                                                        <td><?php echo $r['NOMBRE_EMPRESA']; ?></td>
                                                        <td><?php echo $r['NOMBRE_PLANTA']; ?></td>
                                                        <td><?php echo $r['NOMBRE_TEMPORADA']; ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="box-footer">
                            <div class="btn-toolbar mb-3" role="toolbar">
                                <div class="form-row align-items-center" role="group">
                                    <div class="col-auto">
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">Total Envase</div>
                                                <button class="btn btn-default" id="TOTALENVASEV"></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">Total Neto</div>
                                                <button class="btn btn-default" id="TOTALNETOV"></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="input-group mb-2">
                                            <div class="input-group-prepend">
                                                <div class="input-group-text">Total Bruto</div>
                                                <button class="btn btn-default" id="TOTALBRUTOV"></button>
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
    <script>
    </script>
</body>
</html>
