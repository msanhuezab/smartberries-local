<?php

include_once "../../assest/config/validarUsuarioFruta.php";

?>

<!DOCTYPE html>
<html lang="es">

<head>
    <title>Calidad</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <?php include_once "../../assest/config/urlHead.php"; ?>
</head>

<body class="hold-transition light-skin fixed sidebar-mini theme-primary">
    <div class="wrapper">
        <?php include_once "../../assest/config/menuCalidad.php"; ?>
        <div class="content-wrapper">
            <div class="container-full">
                <div class="content-header">
                    <div class="d-flex align-items-center">
                        <div class="mr-auto">
                            <h3 class="page-title">Calidad</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Modulo</li>
                                        <li class="breadcrumb-item active" aria-current="page"><a href="#">Calidad</a></li>
                                    </ol>
                                </nav>
                            </div>
                        </div>
                        <?php include_once "../../assest/config/verIndicadorEconomico.php"; ?>
                    </div>
                </div>

                <section class="content">
                    <div class="box">
                        <div class="box-header with-border">
                            <h4 class="box-title">Modulo Calidad</h4>
                        </div>
                        <div class="box-body">
                            <div class="row">
                                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                                    <a href="#" class="box pull-up">
                                        <div class="box-body">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-15 bg-primary-light h-50 w-50 l-h-55 rounded text-center">
                                                    <i class="fa fa-clipboard text-primary font-size-24"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">Recepcion</h5>
                                                    <small>Folio o agrupado</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                                    <a href="#" class="box pull-up">
                                        <div class="box-body">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-15 bg-info-light h-50 w-50 l-h-55 rounded text-center">
                                                    <i class="fa fa-cogs text-info font-size-24"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">Proceso</h5>
                                                    <small>Folio o agrupado</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                                    <a href="#" class="box pull-up">
                                        <div class="box-body">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-15 bg-warning-light h-50 w-50 l-h-55 rounded text-center">
                                                    <i class="fa fa-truck text-warning font-size-24"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">Despacho</h5>
                                                    <small>Folio o agrupado</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-xl-3 col-lg-6 col-md-6 col-12">
                                    <a href="#" class="box pull-up">
                                        <div class="box-body">
                                            <div class="d-flex align-items-center">
                                                <div class="mr-15 bg-success-light h-50 w-50 l-h-55 rounded text-center">
                                                    <i class="fa fa-map-marker text-success font-size-24"></i>
                                                </div>
                                                <div>
                                                    <h5 class="mb-0">Destino</h5>
                                                    <small>Control final</small>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table id="tabla-calidad-modulo" class="table-hover" style="width: 100%;">
                                    <thead>
                                        <tr class="text-center">
                                            <th>Seccion</th>
                                            <th>Alcance</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr class="text-center">
                                            <td>Configuracion</td>
                                            <td>Parametros por empresa, temporada, especie y etapa</td>
                                            <td><span class="badge badge-info">Base creada</span></td>
                                        </tr>
                                        <tr class="text-center">
                                            <td>Registro</td>
                                            <td>Ingreso por folio o agrupado por operacion</td>
                                            <td><span class="badge badge-info">Base creada</span></td>
                                        </tr>
                                        <tr class="text-center">
                                            <td>Destino</td>
                                            <td>Control final de calidad en destino</td>
                                            <td><span class="badge badge-info">Base creada</span></td>
                                        </tr>
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
    <script>
        $(document).ready(function() {
            $('#tabla-calidad-modulo').DataTable({
                ordering: false,
                paging: false,
                searching: false,
                info: false
            });
        });
    </script>
</body>

</html>
