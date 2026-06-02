<?php

include_once "../../assest/config/validarUsuarioFruta.php";
include_once "includes/reporteRecepcionGranel.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/ESPECIES_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/TMANEJO_ADO.php';
include_once '../../assest/controlador/TCALIBRE_ADO.php';
include_once '../../assest/controlador/TEMBALAJE_ADO.php';
include_once '../../assest/controlador/TTRATAMIENTO2_ADO.php';


include_once '../../assest/controlador/TRANSPORTE_ADO.php';
include_once '../../assest/controlador/CONDUCTOR_ADO.php';


include_once '../../assest/controlador/TPROCESO_ADO.php';
include_once '../../assest/controlador/TREEMBALAJE_ADO.php';

include_once '../../assest/controlador/PROCESO_ADO.php';
include_once '../../assest/controlador/REEMBALAJE_ADO.php';

include_once '../../assest/controlador/EINDUSTRIAL_ADO.php';
include_once '../../assest/controlador/RECEPCIONIND_ADO.php';
include_once '../../assest/controlador/DRECEPCIONIND_ADO.php';
include_once '../../assest/controlador/EXIINDUSTRIAL_ADO.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR
$ESPECIES_ADO =  new ESPECIES_ADO();
$VESPECIES_ADO =  new VESPECIES_ADO();
$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$TMANEJO_ADO =  new TMANEJO_ADO();
$TCALIBRE_ADO =  new TCALIBRE_ADO();
$TEMBALAJE_ADO =  new TEMBALAJE_ADO();
$TTRATAMIENTO2_ADO =  new TTRATAMIENTO2_ADO();



$TRANSPORTE_ADO =  new TRANSPORTE_ADO();
$CONDUCTOR_ADO =  new CONDUCTOR_ADO();


$TPROCESO_ADO =  new TPROCESO_ADO();
$TREEMBALAJE_ADO =  new TREEMBALAJE_ADO();

$PROCESO_ADO =  new PROCESO_ADO();
$REEMBALAJE_ADO =  new REEMBALAJE_ADO();

$EINDUSTRIAL_ADO =  new EINDUSTRIAL_ADO();
$RECEPCIONIND_ADO =  new RECEPCIONIND_ADO();
$DRECEPCIONIND_ADO =  new DRECEPCIONIND_ADO();
$EXIINDUSTRIAL_ADO =  new EXIINDUSTRIAL_ADO();

//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD

$EMPRESA = "";
$PLANTA = "";
$TEMPORADA = "";

$TOTALGUIA = "";
$TOTALBRUTO = "";
$TOTALNETO = "";
$TOTALENVASE = "";

$FECHADESDE = "";
$FECHAHASTA = "";
$PRODUCTOR = "";

$NUMEROGUIA = "";

//INICIALIZAR ARREGLOS
$ARRAYRECEPCION = array();
$ARRAYRECEPCIONIND = array();
$ARRAYRECEPCIONTOTALES = "";
$ARRAYVEREMPRESA = "";
$ARRAYVERPRODUCTOR = "";
$ARRAYVERTRANSPORTE = "";
$ARRAYVERCONDUCTOR = "";
$ARRAYFECHA = "";
$ARRAYPRODUCTOR = "";

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


if ($EMPRESAS  && $PLANTAS && $TEMPORADAS) {
    $ARRAYRECEPCIONIND = listarRecepcionGranelVista('vw_recepcion_ind_detallado', $EMPRESAS, $PLANTAS, $TEMPORADAS);
}

include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";







?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title>Detallado Recepcion</title>
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

                //FUNCION PARA ABRIR VENTANA QUE SE ENCUENTRA LA OPERACIONES DE DETALLE DE RECEPCION
                function abrirVentana(url) {
                    var opciones =
                        "'directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=1000, height=800'";
                    window.open(url, 'window', opciones);
                }


                function abrirPestana(url) {
                    var win = window.open(url, '_blank');
                    win.focus();
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
                            <h3 class="page-title">Granel</h3>
                            <div class="d-inline-block align-items-center">
                                <nav>
                                    <ol class="breadcrumb">
                                        <li class="breadcrumb-item"><a href="index.php"><i class="mdi mdi-home-outline"></i></a></li>
                                        <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                        <li class="breadcrumb-item" aria-current="page">Granel</li>
                                        <li class="breadcrumb-item" aria-current="page">Recepción</li>
                                        <li class="breadcrumb-item" aria-current="page">Industrial</li>
                                        <li class="breadcrumb-item active" aria-current="page"> <a href="#"> Detallado Recepción </a>  </li>
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
                            <div class="row">
                                <div class="col-xxl-12 col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 col-xs-12">
                                    <div class="table-responsive">
                                        <table id="detalladorind" class="table-hover " style="width: 100%;">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>N° Folio </th>
                                                    <th>Fecha Embalado </th>
                                                    <th>Código Estandar</th>
                                                    <th>Envase/Estandar</th>
                                                    <th>CSG</th>
                                                    <th>Productor</th>
                                                    <th>Especies</th>
                                                    <th>Variedad</th>
                                                    <th>Cantidad Envase</th>
                                                    <th>Kilo Neto </th>
                                                    <th>Kilo Bruto </th>
                                                    <th>Número Recepción</th>
                                                    <th>Fecha Recepción </th>
                                                    <th>Tipo Recepción</th>
                                                    <th>Origen Recepción</th>
                                                    <th>Número Guía Recepción</th>
                                                    <th>Fecha Guía Recepción </th>
                                                    <th>Tipo Manejo </th>
                                                    <th>Transporte </th>
                                                    <th>Nombre Conductor </th>
                                                    <th>Patente Camión </th>
                                                    <th>Patente Carro </th>
                                                    <th>Semana Recepción </th>
                                                    <th>Semana Guia </th>
                                                    <th>Empresa</th>
                                                    <th>Planta</th>
                                                    <th>Temporada</th>
                                                    <th>Cuartel</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($ARRAYRECEPCIONIND as $r) : ?>
                                                    <tr class="text-center">
                                                        <td><?php echo textoReporteGranel($r['FOLIO_DRECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['FECHA_DETALLE']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['CODIGO_ESTANDAR']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_ESTANDAR']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['CSG_PRODUCTOR']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_PRODUCTOR']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_ESPECIES']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_VESPECIES']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['ENVASE']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NETO']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['BRUTO']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NUMERO_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['FECHA_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['TIPO_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['ORIGEN_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NUMERO_GUIA_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['FECHA_GUIA_RECEPCION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_TMANEJO']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_TRANSPORTE']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_CONDUCTOR']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['PATENTE_CAMION']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['PATENTE_CARRO']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['SEMANA']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['SEMANAGUIA']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_EMPRESA']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_PLANTA']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['NOMBRE_TEMPORADA']); ?></td>
                                                        <td><?php echo textoReporteGranel($r['CUARTEL']); ?></td>
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
</body>

</html>
