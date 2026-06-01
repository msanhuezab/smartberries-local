<?php


include_once "../../assest/config/validarUsuarioFruta.php";

//LLAMADA ARCHIVOS NECESARIOS PARA LAS OPERACIONES

include_once '../../assest/controlador/ICARGA_ADO.php';
include_once '../../assest/controlador/DICARGA_ADO.php';
include_once '../../assest/controlador/TCONTENEDOR_ADO.php';
include_once '../../assest/controlador/AERONAVE_ADO.php';
include_once '../../assest/controlador/NAVE_ADO.php';
include_once '../../assest/controlador/DFINAL_ADO.php';
include_once '../../assest/controlador/DESPACHOEX_ADO.php';
include_once '../../assest/controlador/NAVIERA_ADO.php';
include_once '../../assest/controlador/EMISIONBL_ADO.php';


include_once '../../assest/modelo/ICARGA.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR

$ICARGA_ADO =  new ICARGA_ADO();
$DICARGA_ADO =  new DICARGA_ADO();

$TCONTENEDOR_ADO =  new TCONTENEDOR_ADO();
$AERONAVE_ADO =  new AERONAVE_ADO();
$NAVE_ADO =  new NAVE_ADO();
$DFINAL_ADO =  new DFINAL_ADO();
$DESPACHOEX_ADO =  new DESPACHOEX_ADO();
$NAVIERA_ADO =  new NAVIERA_ADO();
$EMISIONBL_ADO =  new EMISIONBL_ADO();
//INICIALIZAR MODELO

$ICARGA =  new ICARGA();

//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD
$TOTALENVASE = "";
$TOTALNETO = "";
$TOTALBRUTO = "";
$TOTALUS   = "";

$FECHADESDE = "";
$EMISIONBL = "";
$FECHAHASTA = "";


//INICIALIZAR ARREGLOS
$ARRAYICARGA = "";
$ARRAYTOTALICARGA = "";

$ARRAYEMISIONBL = "";
$ARRAYTCONTENEDOR = "";
$ARRAYTVEHICULO = "";
$ARRAYAERONAVE = "";
$ARRAYNAVE = "";
$ARRAYDFINAL = "";
$ARRAYNAVIERA = "";

//DEFINIR ARREGLOS CON LOS DATOS OBTENIDOS DE LAS FUNCIONES DE LOS CONTROLADORES


if ($EMPRESAS   && $TEMPORADAS) {

    $ARRAYICARGA = $ICARGA_ADO->listarIcargaEmpresaTemporadaCBX($EMPRESAS,  $TEMPORADAS);
}
include_once "../../assest/config/validarDatosUrl.php";
include_once "../../assest/config/datosUrLP.php";






?>


<!DOCTYPE html>
<html lang="es">

<head>
    <title> Agrupado Instructivo Carga</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="description" content="">
    <meta name="author" content="">
    <!- LLAMADA DE LOS ARCHIVOS NECESARIOS PARA DISEÑO Y FUNCIONES BASE DE LA VISTA -!>
        <?php include_once "../../assest/config/urlHead.php"; ?>
        <style>
            .action-btn-compact {
                border-radius: 12px;
                padding: 0.35rem 0.75rem;
                line-height: 1.25;
            }
        </style>
        <!- FUNCIONES BASES -!>
            <script type="text/javascript">
                //REDIRECCIONAR A LA PAGINA SELECIONADA
                function irPagina(url) {
                    location.href = "" + url;
                }

                function abrirVentana(url) {
                    var opciones =
                        "'directories=no, location=no, menubar=no, scrollbars=yes, statusbar=no, tittlebar=no, width=1600, height=1000'";
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
        <!- LLAMADA AL MENU PRINCIPAL DE LA PAGINA-!>
            <?php include_once "../../assest/config/menuFruta.php"; ?>
            <!-- Content Wrapper. Contains page content -->
            <div class="content-wrapper">
                <div class="container-full">

                    <!-- Content Header (Page header) -->
                    <div class="content-header">
                        <div class="d-flex align-items-center">
                            <div class="mr-auto">
                                <h3 class="page-title">Exportación </h3>
                                <div class="d-inline-block align-items-center">
                                    <nav>
                                        <ol class="breadcrumb">
                                            <li class="breadcrumb-item"> <a href="index.php"> <i class="mdi mdi-home-outline"></i></a></li>
                                            <li class="breadcrumb-item" aria-current="page">Módulo</li>
                                            <li class="breadcrumb-item" aria-current="page">Exportación</li>
                                            <li class="breadcrumb-item" aria-current="page">Instructivo Carga</li>
                                            <li class="breadcrumb-item active" aria-current="page"> <a href="#">Agrupado Instructivo Carga </a>
                                            </li>
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
                                            <table id="icarga" class="table-hover " style="width: 100%;">
                                                <thead>
                                                    <tr class="text-center">
                                                        <th>Número </th>
                                                        <th>Número Referencia</th>
                                                        <th>Folios Asignados</th>
                                                        <th>Estado</th>
                                                        <th>Fecha Instructivo</th>
                                                        <th class="text-center">Operaciónes </th>
                                                        <th class="text-center">Duplicar</th>
                                                        <th class="text-center">Eliminar</th>
                                                        
                                                        <th>Fecha Corte Documental</th>
                                                        <th>Tipo Emarque</th>
                                                        <th>Fecha ETD</th>
                                                        <th>Fecha ETA</th>
                                                        <th>Fecha Real ETA</th>
                                                        <th>Fecha Real ETD</th>
                                                        <th>BL/AWB </th>
                                                        <th>Emisión BL </th>
                                                        <th>Naviera </th>
                                                        <th>Nave </th>
                                                        <th>Viaje </th>
                                                        <th>Tipo Contenedor</th>
                                                        <th>N° Contenedor</th>
                                                        
                                                        <th>Días Estimados</th>
                                                        <th>N° Courier</th>
                                                        <th>Días Reales </th>
                                                        <th>Destino Final </th>
                                                        <th>Total Envase</th>
                                                        <th>Total Kg. Neto</th>
                                                        <th>Total Kg. Bruto</th>
                                                        <th>Total Precio Us</th>
                                                        <th>Semana Instructivo</th>
                                                        <th>Semana Corte Documental</th>
                                                        <th>Semana ETD</th>
                                                        <th>Semana ETA</th>
                                                        <th>Semana Real ETA</th>
                                                        <th>Empresa</th>
                                                        <th>Temporada</th>
                                                        <th>Otro</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($ARRAYICARGA as $r) : ?>

                                                        <?php
                                                        if ($r['ESTADO_ICARGA'] == "1") {
                                                            $ESTADOICARGA = "Creado";
                                                        }
                                                        if ($r['ESTADO_ICARGA'] == "2") {
                                                            $ESTADOICARGA = "Por Cargar";
                                                        }
                                                        if ($r['ESTADO_ICARGA'] == "3") {
                                                            $ESTADOICARGA = "Cargado";
                                                        }
                                                        if ($r['ESTADO_ICARGA'] == "4") {
                                                            $ESTADOICARGA = "Arrivado";
                                                        }
                                                        if ($r['ESTADO_ICARGA'] == "5") {
                                                            $ESTADOICARGA = "Cancelado";
                                                        }
                                                        if ($r['TEMBARQUE_ICARGA'] == "1") {
                                                            $TEMBARQUE = "Terrestre";
                                                        }
                                                        if ($r['TEMBARQUE_ICARGA'] == "2") {
                                                            $TEMBARQUE = "Aereo";
                                                        }
                                                        if ($r['TEMBARQUE_ICARGA'] == "3") {
                                                            $TEMBARQUE = "Maritimo";
                                                        }
                                                        $ARRAYTCONTENEDOR = $TCONTENEDOR_ADO->verTcontenedorCarga($r['ID_TCONTENEDOR']);
                                                        if ($ARRAYTCONTENEDOR) {
                                                            $NOMBRETCONTENEDOR = $ARRAYTCONTENEDOR[0]['NOMBRE_TCONTENEDOR'];
                                                        } else {
                                                            $NOMBRETCONTENEDOR = "Sin Datos";
                                                        }
                                                        $ARRAYDFINAL = $DFINAL_ADO->verDfinal($r['ID_DFINAL']);
                                                        if ($ARRAYDFINAL) {
                                                            $NOMBRDFINAL = $ARRAYDFINAL[0]['NOMBRE_DFINAL'];
                                                        } else {
                                                            $NOMBRDFINAL = "Sin Datos";
                                                        }

                                                        $ARRAYEMPRESA = $EMPRESA_ADO->verEmpresa($r['ID_EMPRESA']);
                                                        if ($ARRAYEMPRESA) {
                                                            $NOMBREEMPRESA = $ARRAYEMPRESA[0]['NOMBRE_EMPRESA'];
                                                        } else {
                                                            $NOMBREEMPRESA = "Sin Datos";
                                                        }
                                                        $ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($r['ID_TEMPORADA']);
                                                        if ($ARRAYTEMPORADA) {
                                                            $NOMBRETEMPORADA = $ARRAYTEMPORADA[0]['NOMBRE_TEMPORADA'];
                                                        } else {
                                                            $NOMBRETEMPORADA = "Sin Datos";
                                                        }                                                        
                                                        $ARRAYDESPACHOEX=$DESPACHOEX_ADO->buscarDespachoExPorIcarga($r['ID_ICARGA']);  
                                                        if($ARRAYDESPACHOEX){
                                                            $NUMEROCONTENEDOR=$ARRAYDESPACHOEX[0]["NUMERO_CONTENEDOR_DESPACHOEX"];
                                                        }else{
                                                            $NUMEROCONTENEDOR=$r['NCONTENEDOR_ICARGA'];
                                                        }      
                                                        
                                                        $ARRAYNAVIERA = $NAVIERA_ADO->buscarNombreNavieraID($r['ID_NAVIERA']);
                                                            if($ARRAYNAVIERA){
                                                                $NOMBRENAVIERA = $ARRAYNAVIERA[0]["NOMBRE_NAVIERA"];
                                                            }else{
                                                                $NOMBRENAVIERA = "Sin Datos";
                                                            }

                                                            $ARRAYEMISIONBL = $EMISIONBL_ADO->verEmisionbl($r['ID_EMISIONBL']);
                                                            if($ARRAYEMISIONBL){
                                                                $NOMBREEMISIONBL = $ARRAYEMISIONBL[0]["NOMBRE_EMISIONBL"];
                                                            }else{
                                                                $NOMBREEMISIONBL = "Sin Datos";
                                                            }
                                                        ?>
                                                        <tr class="text-center">
                                                            <td> <?php echo $r['NUMERO_ICARGA']; ?>  </td>
                                                            <td> <?php echo $r['NREFERENCIA_ICARGA']; ?>  </td>
                                                            <td>  <?php echo $r['N_FOLIOS']; ?> </td>
                                                            <td>
                                                                <?php if ($r['ESTADO'] == "0") { ?>
                                                                    <button type="button" class="btn btn-block btn-danger">Cerrado</button>
                                                                <?php  }  ?>
                                                                <?php if ($r['ESTADO'] == "1") { ?>
                                                                    <button type="button" class="btn btn-block btn-success">Abierto</button>
                                                                <?php  }  ?>
                                                            </td>
                                                            <td> <?php echo $r['FECHA']; ?> </td>
                                                           
                                                            <td class="text-center">
                                                                <form method="post" id="form1">
                                                                    <div class="list-icons d-inline-flex">
                                                                        <div class="list-icons-item dropdown">
                                                                            <button class="btn btn-secondary" type="button" id="dropdownMenuButton" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                                <i class="glyphicon glyphicon-cog"></i>
                                                                            </button>
                                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                                <button class="dropdown-menu" aria-labelledby="dropdownMenuButton"></button>
                                                                                <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_ICARGA']; ?>" />
                                                                                <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroICarga" />
                                                                                <input type="hidden" class="form-control" placeholder="URL" id="URLO" name="URLO" value="listarICarga" />
                                                                                <span href="#" class="dropdown-item">                         
                                                                                    <div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales">  
                                                                                        <?php if ($r['ESTADO'] == "0") { ?>
                                                                                            <button type="submit" class="btn btn-info  " id="VERURL" name="VERURL" data-toggle="tooltip"  title="Ver">
                                                                                                <i class="ti-eye"></i> Ver
                                                                                            </button>
                                                                                        <?php } ?>
                                                                                        <?php if ($r['ESTADO'] == "1") { ?>
                                                                                                <button type="submit" class="btn  btn-warning " id="EDITARURL" name="EDITARURL" data-toggle="tooltip"  title="Editar">
                                                                                                    <i class="ti-pencil-alt"></i> Editar
                                                                                                </button>
                                                                                        <?php } ?> 
                                                                                        <?php if ($r['ESTADO'] == "1") { ?>
                                                                                                <a type="submit" class="btn  btn-primary " id="ASIGNARFOLIOURL" name="ASIGNARFOLIOURL" data-toggle="tooltip"  title="Asignar Folios A Instructivo" href="./registroSeleccionExistenciaReferencia.php?op&id=<?php echo $r['ID_ICARGA']; ?>">
                                                                                                    <i class="ti-pencil-alt"></i> Asignar Folios A Instructivo
                                                                                                </a>
                                                                                        <?php } ?> 
                                                                                        <?php if ($r['ESTADO'] == "1") { ?>
                                                                                                <a type="submit" class="btn  btn-danger " id="LISTAASIGNARFOLIOURL" name="LISTAASIGNARFOLIOURL" data-toggle="tooltip"  title="Lista de Folios Asignados" href="./registroSeleccionExistenciaReferenciaAsignado.php?op&id=<?php echo $r['ID_ICARGA']; ?>">
                                                                                                    <i class="ti-pencil-alt"></i> Lista Folios Asignados
                                                                                                </a>
                                                                                        <?php } ?>                                                                                          
                                                                                        <?php if ($ARRAYDESPACHOEX) { ?>
                                                                                            <button type="submit" class="btn btn-success " id="CARGADO" name="CARGADO"  data-toggle="tooltip"  title="Cargado"
                                                                                            <?php  if ($r['ESTADO_ICARGA'] == "3")  { echo "disabled"; }?>>
                                                                                                <i class="fa fa-check"></i> Cargado
                                                                                            </button>  
                                                                                        <?php } ?>  
                                                                                    </div>                                                                                   
                                                                                </span>                                                                            
                                                                                <hr>     
                                                                                <span href="#" class="dropdown-item">                         
                                                                                    <div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales"> 
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Instructivo Español" Onclick="abrirPestana('../../assest/documento/informeIcargaEspanol.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                            <i class="fa fa-file-pdf-o"></i><br> Instructivo Español
                                                                                        </button>    
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Instruction English" Onclick="abrirPestana('../../assest/documento/informeIcargaEnglish.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                                <i class="fa fa-file-pdf-o"></i><br> Instruction English
                                                                                        </button>                                                                                 
                                                                                    </div>    
                                                                                    <hr> 
                                
                                                                                    <div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales"> 
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Report Invoice" Onclick="abrirPestana('../../assest/documento/informeIcargaInvoice.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                            <i class="fa fa-file-pdf-o"></i><br> Invoice
                                                                                        </button>    
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Report Invoice v2" Onclick="abrirPestana('../../assest/documento/informeIcargaInvoicev2.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                                <i class="fa fa-file-pdf-o"></i><br> Invoice v2
                                                                                        </button>                                                                                 
                                                                                    </div> 
                                                                                    <hr> 
                                
                                                                                    <!--<div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales"> 
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Report Invoice" Onclick="abrirPestana('../../assest/documento/informeIcargaInvoiceII.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                            <i class="fa fa-file-pdf-o"></i><br> Invoice II
                                                                                        </button>    
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Report Invoice v2" Onclick="abrirPestana('../../assest/documento/informeIcargaInvoicev2II.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                                <i class="fa fa-file-pdf-o"></i><br> Invoice v2 II
                                                                                        </button>                                                                                 
                                                                                    </div>   
                                                                                    <hr>  -->                       
                                                                                    <div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales">
                                                                                        <button type="button" class="btn  btn-danger  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Informe Carga Real" Onclick="abrirPestana('../../assest/documento/informeICargaReal.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                                <i class="fa fa-file-pdf-o"></i><br>  Carga Real
                                                                                        </button>                                                                                 
                                                                                    </div>                                                           
                                                                                </span>                                                                                     
                                                                                <span href="#" class="dropdown-item">                         
                                                                                    <div class="btn-group btn-block col-12" role="group" aria-label="Acciones generales"> 
                                                                                        <button type="button" class="btn  btn-success  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Reporte Carga Real" Onclick="abrirPestana('../../assest/reporte/reporteCargaRealcarga.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                            <i class="fa fa-file-excel-o"></i><br> Carga Real
                                                                                        </button>    
                                                                                        <button type="button" class="btn  btn-success  btn-sm" id="defecto" name="informe" data-toggle="tooltip"  title="Reporte Packing Lis" Onclick="abrirPestana('../../assest/reporte/reporteICargaPackingList.php?parametro=<?php echo $r['ID_ICARGA']; ?>&&usuario=<?php echo $IDUSUARIOS; ?>'); ">
                                                                                            <i class="fa fa-file-excel-o"></i><br>   Packing List
                                                                                        </button>                                                                                 
                                                                                    </div>                                                                                   
                                                                                </span>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </form>
                                                                <!-- <form method="post" id="form1">
                                                                    <div class="list-icons d-inline-flex">
                                                                        <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_ICARGA']; ?>" />
                                                                        <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroICarga" />
                                                                        <input type="hidden" class="form-control" placeholder="URLO" id="URLO" name="URLO" value="listarICarga" />                                                            <td>
                                                                        <span>
                                                                            <button type="submit" name="DUPLICARURL" class="btn btn-block btn-success" title="DUPLICAR">
                                                                                <i class="fa fa-copy"></i> Duplicar
                                                                            </button>
                                                                                                                                                                                                         
                                                                        </span>

                                                                    </div>
                                                                    
                                                               </form> -->
                                                            </td>
                                                            <td>
                                                                <form method="post" class="mb-0">
                                                                    <input type="hidden" name="ID" value="<?php echo $r['ID_ICARGA']; ?>" />
                                                                    <button type="submit" class="btn btn-block btn-success btn-sm action-btn-compact" name="DUPLICAR" value="1" data-toggle="tooltip" title="Duplicar Instructivo Carga">
                                                                        <i class="fa fa-copy"></i> Duplicar
                                                                    </button>
                                                                </form>
                                                            </td>
                                                            <td>
                                                                <form  method="post" id="form1" class="mb-0">
                                                                    <div class="list-icons d-inline-flex">
                                                                        <input type="hidden" class="form-control" placeholder="ID" id="ID" name="ID" value="<?php echo $r['ID_ICARGA']; ?>" />
                                                                        <input type="hidden" class="form-control" placeholder="URL" id="URL" name="URL" value="registroICarga" />
                                                                        <input type="hidden" class="form-control" placeholder="URLO" id="URLO" name="URLO" value="listarICarga" />
                                                                        <button type="submit" class="btn btn-block btn-danger btn-sm action-btn-compact" id="ELIMINAR" name="ELIMINAR" data-toggle="tooltip" title="Eliminar Instructivo Carga" >
                                                                            <i class="fa fa-trash"></i> Eliminar
                                                                        </button>
                                                                    </div>
                                                                </form>
                                                            </td>
                                    
                                                            <td> <?php echo $ESTADOICARGA; ?> </td>
                                                            <td> <?php echo $TEMBARQUE; ?> </td>
                                                            <td> <?php echo $r['FECHACORTEDOCUMENTAL']; ?> </td>
                                                            <td> <?php echo $r['FECHAETD']; ?> </td>
                                                            <td> <?php echo $r['FECHAETA']; ?> </td>
                                                            <td> <?php echo $r['FECHAETAREAL']; ?> </td>
                                                            <td> <?php echo $r['FECHAETDREAL']; ?> </td>
                                                            <td> <?php echo $r['BLAWB']; ?> </td>
                                                            <td> <?php echo $NOMBREEMISIONBL; ?> </td>
                                                            <td> <?php echo $NOMBRENAVIERA; ?> </td>
                                                            <td> <?php echo $r['NAVE_ICARGA']; ?> </td>
                                                            <td> <?php echo $r['NVIAJE_ICARGA']; ?> </td>
                                                            <td> <?php echo $NOMBRETCONTENEDOR; ?> </td>
                                                            <td> <?php echo $NUMEROCONTENEDOR; ?> </td>
                                                            <td> <?php echo $r['NUMEROCOURIER_ICARGA']; ?> </td>
                                                            <td> <?php echo $r['ESTIMADO']; ?> </td>
                                                            <td> <?php echo $r['REAL']; ?> </td>
                                                            <td> <?php echo $NOMBRDFINAL; ?> </td>
                                                            <td> <?php echo $r['ENVASE']; ?> </td>
                                                            <td> <?php echo $r['NETO'];  ?> </td>
                                                            <td> <?php echo $r['BRUTO'];  ?> </td>
                                                            <td> <?php echo $r['US'];  ?> </td>
                                                            <td> <?php echo $r['SEMANA']; ?> </td>
                                                            <td> <?php echo $r['SEMANACORTEDOCUMENTAL']; ?> </td>
                                                            <td> <?php echo $r['SEMANAETD']; ?> </td>
                                                            <td> <?php echo $r['SEMANAETA']; ?> </td>
                                                            <td> <?php echo $r['SEMANAETAREAL']; ?> </td>
                                                            <td><?php echo $NOMBREEMPRESA; ?></td>
                                                            <td><?php echo $NOMBRETEMPORADA; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
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
                                        <div class="col-auto">
                                            <div class="input-group mb-2">
                                                <div class="input-group-prepend">
                                                    <div class="input-group-text">Total US</div>
                                                    <button class="btn   btn-default" id="TOTALUSV" name="TOTALUSV" >                                                           
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
            <!- LLAMADA ARCHIVO DEL DISEÑO DEL FOOTER Y MENU USUARIO -!>
                <?php include_once "../../assest/config/footer.php"; ?>
                <?php include_once "../../assest/config/menuExtraFruta.php"; ?>
    </div>
    <!- LLAMADA URL DE ARCHIVOS DE DISEÑO Y JQUERY E OTROS -!>
        <?php include_once "../../assest/config/urlBase.php"; ?>
        <?php
        if (isset($_REQUEST['DUPLICAR'])) {
            $IDDUPLICAR = $_REQUEST['ID'];
            $ARRAYVERICARGA = $ICARGA_ADO->verIcarga($IDDUPLICAR);
            if ($ARRAYVERICARGA) {
                $ARRAYNUMERO = $ICARGA_ADO->obtenerNumero($EMPRESAS, $TEMPORADAS);
                $NUMERO = isset($ARRAYNUMERO[0]['NUMERO']) ? $ARRAYNUMERO[0]['NUMERO'] + 1 : 1;
                $ORIGINAL = $ARRAYVERICARGA[0];

                $ICARGA->__SET('NUMERO_ICARGA', $NUMERO);
                $ICARGA->__SET('FECHA_ICARGA', $ORIGINAL['FECHA_ICARGA']);
                $ICARGA->__SET('FECHA_CDOCUMENTAL_ICARGA', $ORIGINAL['FECHA_CDOCUMENTAL_ICARGA']);
                $ICARGA->__SET('BOOKING_ICARGA', $ORIGINAL['BOOKING_ICARGA']);
                $ICARGA->__SET('NREFERENCIA_ICARGA', $ORIGINAL['NREFERENCIA_ICARGA']);
                $ICARGA->__SET('FECHAETD_ICARGA', $ORIGINAL['FECHAETD_ICARGA']);
                $ICARGA->__SET('FECHAETA_ICARGA', $ORIGINAL['FECHAETA_ICARGA']);
                $ICARGA->__SET('FECHAETAREAL_ICARGA', $ORIGINAL['FECHAETAREAL_ICARGA']);
                $ICARGA->__SET('FECHAETDREAL_ICARGA', $ORIGINAL['FECHAETDREAL_ICARGA']);
                $ICARGA->__SET('NCONTENEDOR_ICARGA', $ORIGINAL['NCONTENEDOR_ICARGA']);
                $ICARGA->__SET('NCOURIER_ICARGA', $ORIGINAL['NCOURIER_ICARGA']);
                $ICARGA->__SET('CRT_ICARGA', $ORIGINAL['CRT_ICARGA']);
                $ICARGA->__SET('FECHASTACKING_ICARGA', $ORIGINAL['FECHASTACKING_ICARGA']);
                $ICARGA->__SET('FECHASTACKINGF_ICARGA', $ORIGINAL['FECHASTACKINGF_ICARGA']);
                $ICARGA->__SET('NVIAJE_ICARGA', $ORIGINAL['NVIAJE_ICARGA']);
                $ICARGA->__SET('FUMIGADO_ICARGA', $ORIGINAL['FUMIGADO_ICARGA']);
                $ICARGA->__SET('T_ICARGA', $ORIGINAL['T_ICARGA']);
                $ICARGA->__SET('O2_ICARGA', $ORIGINAL['O2_ICARGA']);
                $ICARGA->__SET('C02_ICARGA', $ORIGINAL['C02_ICARGA']);
                $ICARGA->__SET('ALAMPA_ICARGA', $ORIGINAL['ALAMPA_ICARGA']);
                $ICARGA->__SET('COSTO_FLETE_ICARGA', $ORIGINAL['COSTO_FLETE_ICARGA']);
                $ICARGA->__SET('DUS_ICARGA', $ORIGINAL['DUS_ICARGA']);
                $ICARGA->__SET('BOLAWBCRT_ICARGA', $ORIGINAL['BOLAWBCRT_ICARGA']);
                $ICARGA->__SET('NETO_ICARGA', $ORIGINAL['NETO_ICARGA']);
                $ICARGA->__SET('REBATE_ICARGA', $ORIGINAL['REBATE_ICARGA']);
                $ICARGA->__SET('PUBLICA_ICARGA', $ORIGINAL['PUBLICA_ICARGA']);
                $ICARGA->__SET('FDA_ICARGA', $ORIGINAL['FDA_ICARGA']);
                $ICARGA->__SET('TEMBARQUE_ICARGA', $ORIGINAL['TEMBARQUE_ICARGA']);
                $ICARGA->__SET('OBSERVACION_ICARGA', $ORIGINAL['OBSERVACION_ICARGA']);
                $ICARGA->__SET('OBSERVACIONI_ICARGA', $ORIGINAL['OBSERVACIONI_ICARGA']);
                $ICARGA->__SET('NAVE_ICARGA', $ORIGINAL['NAVE_ICARGA']);
                $ICARGA->__SET('ID_EXPPORTADORA', $ORIGINAL['ID_EXPPORTADORA']);
                $ICARGA->__SET('ID_CONSIGNATARIO', $ORIGINAL['ID_CONSIGNATARIO']);
                $ICARGA->__SET('ID_EMISIONBL', $ORIGINAL['ID_EMISIONBL']);
                $ICARGA->__SET('ID_NOTIFICADOR', $ORIGINAL['ID_NOTIFICADOR']);
                $ICARGA->__SET('ID_BROKER', $ORIGINAL['ID_BROKER']);
                $ICARGA->__SET('ID_RFINAL', $ORIGINAL['ID_RFINAL']);
                $ICARGA->__SET('ID_MERCADO', $ORIGINAL['ID_MERCADO']);
                $ICARGA->__SET('ID_AADUANA', $ORIGINAL['ID_AADUANA']);
                $ICARGA->__SET('ID_AGCARGA', $ORIGINAL['ID_AGCARGA']);
                $ICARGA->__SET('ID_DFINAL', $ORIGINAL['ID_DFINAL']);
                $ICARGA->__SET('ID_TRANSPORTE', $ORIGINAL['ID_TRANSPORTE']);
                $ICARGA->__SET('ID_LCARGA', $ORIGINAL['ID_LCARGA']);
                $ICARGA->__SET('ID_LDESTINO', $ORIGINAL['ID_LDESTINO']);
                $ICARGA->__SET('ID_LAREA', $ORIGINAL['ID_LAREA']);
                $ICARGA->__SET('ID_ACARGA', $ORIGINAL['ID_ACARGA']);
                $ICARGA->__SET('ID_ADESTINO', $ORIGINAL['ID_ADESTINO']);
                $ICARGA->__SET('ID_NAVIERA', $ORIGINAL['ID_NAVIERA']);
                $ICARGA->__SET('ID_PCARGA', $ORIGINAL['ID_PCARGA']);
                $ICARGA->__SET('ID_PDESTINO', $ORIGINAL['ID_PDESTINO']);
                $ICARGA->__SET('ID_FPAGO', $ORIGINAL['ID_FPAGO']);
                $ICARGA->__SET('ID_CVENTA', $ORIGINAL['ID_CVENTA']);
                $ICARGA->__SET('ID_MVENTA', $ORIGINAL['ID_MVENTA']);
                $ICARGA->__SET('ID_TCONTENEDOR', $ORIGINAL['ID_TCONTENEDOR']);
                $ICARGA->__SET('ID_ATMOSFERA', $ORIGINAL['ID_ATMOSFERA']);
                $ICARGA->__SET('ID_TSERVICIO', $ORIGINAL['ID_TSERVICIO']);
                $ICARGA->__SET('ID_TFLETE', $ORIGINAL['ID_TFLETE']);
                $ICARGA->__SET('ID_SEGURO', $ORIGINAL['ID_SEGURO']);
                $ICARGA->__SET('ID_PAIS', $ORIGINAL['ID_PAIS']);
                $ICARGA->__SET('ID_EMPRESA', $ORIGINAL['ID_EMPRESA']);
                $ICARGA->__SET('ID_TEMPORADA', $ORIGINAL['ID_TEMPORADA']);
                $ICARGA->__SET('ID_USUARIOI', $IDUSUARIOS);
                $ICARGA->__SET('ID_USUARIOM', $IDUSUARIOS);

                $ICARGA_ADO->agregarIcarga($ICARGA);

                $ARRYAOBTENERID = $ICARGA_ADO->obtenerId(
                    $ORIGINAL['FECHA_ICARGA'],
                    $ORIGINAL['OBSERVACION_ICARGA'],
                    $ORIGINAL['ID_EMPRESA'],
                    $ORIGINAL['ID_TEMPORADA']
                );
                $IDNUEVO = $ARRYAOBTENERID[0]['ID_ICARGA'];
                $AUSUARIO_ADO->agregarAusuario2($NUMERO, 1, 1, "" . $_SESSION["NOMBRE_USUARIO"] . ", Duplicación Instructivo Carga", "fruta_icarga", $IDNUEVO, $_SESSION["ID_USUARIO"], $_SESSION['ID_EMPRESA'], $_SESSION['ID_PLANTA'], $_SESSION['ID_TEMPORADA']);

                echo '<script>
                    Swal.fire({
                        icon:"success",
                        title:"Registro Duplicado",
                        text:"El Instructivo de carga se ha duplicado correctamente",
                        showConfirmButton: true,
                        confirmButtonText:"Cerrar",
                        closeOnConfirm:false
                    }).then((result)=>{
                        location.href = "listarICarga.php";
                    })
                </script>';
            }
        }
        if (isset($_REQUEST['CARGADO'])) {

            $ICARGA->__SET('ID_USUARIOM', $IDUSUARIOS);
            $ICARGA->__SET('ID_ICARGA', $_REQUEST['ID']);
            //LLAMADA AL METODO DE EDITAR DEL CONTROLADOR
            $ICARGA_ADO->CargadoCerrado($ICARGA);   

            $AUSUARIO_ADO->agregarAusuario2("NULL",1,2,"".$_SESSION["NOMBRE_USUARIO"].", Modificación Instructivo Carga, se cambia estado a cargado","fruta_icarga",$_REQUEST['ID'],$_SESSION["ID_USUARIO"],$_SESSION['ID_EMPRESA'],$_SESSION['ID_PLANTA'],$_SESSION['ID_TEMPORADA'] );  

            echo '<script>
                Swal.fire({
                    icon:"info",
                    title:"Registro Modificado",
                    text:"El registro de Instructivo se ha modificada correctamente",
                    showConfirmButton: true,
                    confirmButtonText:"Cerrar",
                    closeOnConfirm:false
                }).then((result)=>{
                    location.href = "listarICarga.php";                        
                })
            </script>';
        }   
        if (isset($_REQUEST['ELIMINAR'])) {
            $IDELIMINAR = $_REQUEST['ID'];
            $ICARGA->__SET('ID_ICARGA', $IDELIMINAR);
            //LLAMADA AL METODO DE EDITAR DEL CONTROLADOR
            $ICARGA_ADO->deshabilitar($ICARGA);

            $AUSUARIO_ADO->agregarAusuario2("NULL",1, 3,"".$_SESSION["NOMBRE_USUARIO"].", Deshabilitar Detalle Instructivo Carga","fruta_icarga",$_REQUEST['ID'],$_SESSION["ID_USUARIO"],$_SESSION['ID_EMPRESA'],$_SESSION['ID_PLANTA'],$_SESSION['ID_TEMPORADA'] );

            //REDIRECCIONAR A PAGINA listarICarga.php
            $id_dato =  $_REQUEST['IDP'];
            $accion_dato =  $_REQUEST['OPP'];
            // echo "<script type='text/javascript'> location.href ='" . $_REQUEST['URLO'] . ".php?op';</script>";   
            echo '<script>
                Swal.fire({
                    icon:"error",
                    title:"Registro Eliminado",
                    text:"El Instructivo de carga se ha eliminado correctamente ",
                    showConfirmButton:true,
                    confirmButtonText:"Volver al agrupado"
                }).then((result)=>{
                    location.href ="' . $_REQUEST['URLO'] . '.php?op&id='.$id_dato.'&a='.$accion_dato.'&urlo='.$urlo_dato.'&idd='.$idd_dato.'&ad='.$acciond_dato.'";                        
                })
            </script>'; 
        }
        ?>
</body>
</html>