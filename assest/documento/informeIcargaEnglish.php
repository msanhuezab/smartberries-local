<?php

//BASE
include_once '../../assest/controlador/TUSUARIO_ADO.php';
include_once '../../assest/controlador/USUARIO_ADO.php';
include_once '../../assest/controlador/EMPRESA_ADO.php';
include_once '../../assest/controlador/PLANTA_ADO.php';
include_once '../../assest/controlador/TEMPORADA_ADO.php';

// OPERACION
include_once '../../assest/controlador/MERCADO_ADO.php';
include_once '../../assest/controlador/TSERVICIO_ADO.php';

include_once '../../assest/controlador/EXPORTADORA_ADO.php';
include_once '../../assest/controlador/CONSIGNATARIO_ADO.php';
include_once '../../assest/controlador/NOTIFICADOR_ADO.php';
include_once '../../assest/controlador/BROKER_ADO.php';
include_once '../../assest/controlador/RFINAL_ADO.php';

include_once '../../assest/controlador/AGCARGA_ADO.php';
include_once '../../assest/controlador/AADUANA_ADO.php';
include_once '../../assest/controlador/DFINAL_ADO.php';


include_once '../../assest/controlador/TRANSPORTE_ADO.php';
include_once '../../assest/controlador/LCARGA_ADO.php';
include_once '../../assest/controlador/LDESTINO_ADO.php';

include_once '../../assest/controlador/LAEREA_ADO.php';
include_once '../../assest/controlador/AERONAVE_ADO.php';
include_once '../../assest/controlador/ACARGA_ADO.php';
include_once '../../assest/controlador/ADESTINO_ADO.php';

include_once '../../assest/controlador/NAVIERA_ADO.php';
include_once '../../assest/controlador/PCARGA_ADO.php';
include_once '../../assest/controlador/PDESTINO_ADO.php';


include_once '../../assest/controlador/FPAGO_ADO.php';
include_once '../../assest/controlador/MVENTA_ADO.php';
include_once '../../assest/controlador/CVENTA_ADO.php';
include_once '../../assest/controlador/TFLETE_ADO.php';

include_once '../../assest/controlador/TCONTENEDOR_ADO.php';
include_once '../../assest/controlador/ATMOSFERA_ADO.php';
include_once '../../assest/controlador/EMISIONBL_ADO.php';
include_once '../../assest/controlador/PAIS_ADO.php';
include_once '../../assest/controlador/SEGURO_ADO.php';

include_once '../../assest/controlador/EEXPORTACION_ADO.php';
include_once '../../assest/controlador/ESPECIES_ADO.php';
include_once '../../assest/controlador/VESPECIES_ADO.php';
include_once '../../assest/controlador/TCALIBRE_ADO.php';
include_once '../../assest/controlador/TMONEDA_ADO.php';
include_once '../../assest/controlador/TMANEJO_ADO.php';

include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/DESPACHOEX_ADO.php';


include_once '../../assest/controlador/PAIS_ADO.php';
include_once '../../assest/controlador/REGION_ADO.php';
include_once '../../assest/controlador/PROVINCIA_ADO.php';
include_once '../../assest/controlador/COMUNA_ADO.php';

include_once '../../assest/controlador/ICARGA_ADO.php';
include_once '../../assest/controlador/DICARGA_ADO.php';

//INCIALIZAR LAS VARIBLES
//INICIALIZAR CONTROLADOR

$USUARIO_ADO = new USUARIO_ADO();
$EMPRESA_ADO =  new EMPRESA_ADO();
$PLANTA_ADO =  new PLANTA_ADO();
$TEMPORADA_ADO =  new TEMPORADA_ADO();


$MERCADO_ADO =  new MERCADO_ADO();
$TSERVICIO_ADO =  new TSERVICIO_ADO();
$EXPORTADORA_ADO =  new EXPORTADORA_ADO();
$CONSIGNATARIO_ADO =  new CONSIGNATARIO_ADO();
$NOTIFICADOR_ADO =  new NOTIFICADOR_ADO();
$BROKER_ADO =  new BROKER_ADO();
$RFINAL_ADO =  new RFINAL_ADO();
$AGCARGA_ADO =  new AGCARGA_ADO();
$AADUANA_ADO =  new AADUANA_ADO();
$DFINAL_ADO =  new DFINAL_ADO();
$TRANSPORTE_ADO =  new TRANSPORTE_ADO();
$LCARGA_ADO =  new LCARGA_ADO();
$LDESTINO_ADO =  new LDESTINO_ADO();
$LAEREA_ADO =  new LAEREA_ADO();
$AERONAVE_ADO =  new AERONAVE_ADO();
$ACARGA_ADO =  new ACARGA_ADO();
$ADESTINO_ADO =  new ADESTINO_ADO();
$NAVIERA_ADO =  new NAVIERA_ADO();
$PCARGA_ADO =  new PCARGA_ADO();
$PDESTINO_ADO =  new PDESTINO_ADO();
$FPAGO_ADO =  new FPAGO_ADO();
$MVENTA_ADO =  new MVENTA_ADO();
$CVENTA_ADO =  new CVENTA_ADO();
$TFLETE_ADO =  new TFLETE_ADO();
$TCONTENEDOR_ADO =  new TCONTENEDOR_ADO();
$ATMOSFERA_ADO =  new ATMOSFERA_ADO();
$EMISIONBL_ADO =  new EMISIONBL_ADO();
$SEGURO_ADO =  new SEGURO_ADO();

$EEXPORTACION_ADO = new EEXPORTACION_ADO();
$VESPECIES_ADO =  new VESPECIES_ADO();
$ESPECIES_ADO =  new ESPECIES_ADO();
$TCALIBRE_ADO =  new TCALIBRE_ADO();
$PAIS_ADO =  new PAIS_ADO();
$TCALIBRE_ADO = new TCALIBRE_ADO();
$TMONEDA_ADO = new TMONEDA_ADO();
$TMANEJO_ADO =  new TMANEJO_ADO();

$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$DESPACHOEX_ADO = new DESPACHOEX_ADO();

$PAIS_ADO =  new PAIS_ADO();
$REGION_ADO =  new REGION_ADO();
$PROVINCIA_ADO =  new PROVINCIA_ADO();
$COMUNA_ADO =  new COMUNA_ADO();

$ICARGA_ADO =  new ICARGA_ADO();
$DICARGA_ADO =  new DICARGA_ADO();
//INCIALIZAR VARIBALES A OCUPAR PARA LA FUNCIONALIDAD

$NUMERO = "";
$NUMEROVER = "";
$FECHAINGRESO = "";
$FECHAMODIFCIACION = "";
$IDINOTA = "";
$FECHAINOTA = "";
$TNOTA = "";
$OBSERVACIONINOTA = "";
$ICARGAD="";
$BOOKINGINSTRUCTIVO = "";
$BOLAWBCRTINSTRUCTIVO="";
$CONSIGNATARIO = "";
$FECHAETD = "";
$FECHAETA = "";
$FECHAETDREAL = "";
$TEMBARQUE = "";
$TRANSPORTE = "";
$LCARGA = "";
$LDESTINO = "";
$LAEREA = "";
$ACARGA = "";
$ADESTINO = "";
$NAVIERA = "";
$PCARGA = "";
$PDESTINO = "";
$FPAGO = "";
$CVENTA = "";
$ESTADO = "";
$ESTANDAR = "";
$ESPECIES = "";
$VESPECIES = "";
$ENVASE = "";
$PRECIOUS = "";
$CALIBRE = "";
$EMPRESA = "";
$PLANTA = "";
$TEMPORADA = "";
$TOTALPRECIOUSNUEVO=0;


$NOMBRERESPONSABLE="";
$TOTALENVASEV = "";
$TOTALNETOV = "";
$TOTALBRUTOV = "";
$TOTALUSV = "";
$FDADESPACHOEX="";
$NUMEROCONTENEDOR="";
$NUMEROSELLO="";
$FECHADESPACHOEX="";
$LUGARDECARGA="";

//INICIALIZAR ARREGLOS
$ARRAYEMPRESA = "";
$ARRAYPLANTA = "";
$ARRAYTEMPORADA = "";
$ARRAYFECHAACTUAL = "";
$ARRAYICARGA = "";
$ARRAYDICARGA = "";
$ARRAYCONSIGNATARIO = "";
$ARRAYTRANSPORTE = "";
$ARRAYLCARGA = "";
$ARRAYLDESTINO = "";
$ARRAYLAEREA = "";
$ARRAYACARGA = "";
$ARRAYADESTINO = "";
$ARRAYNAVIERA = "";
$ARRAYPCARGA = "";
$ARRAYPDESTINO = "";
$ARRAYFPAGO = "";
$ARRAYMVENTA = "";
$ARRAYICARGA2 = "";
$ARRAYPAIS = "";
$ARRAYVERPLANTA = "";
$ARRAYVERDCARGA = "";
$ARRAYSEGURO = "";
$ARRAYPRODUCTOR = "";
$ARRAYDCARGA = "";
$ARRAYCALIBRE = "";
$ARRAYNUMERO = "";
$ARRAYVERNOTADCNC="";

$ARRAYDESPACHOEX="";
$ARRAYDESPACHOEX2="";
$ARRAYCOMUNA="";
$ARRYAPROVINCIA="";
$ARRYAREGION="";




if (isset($_REQUEST['usuario'])) {
  $USUARIO = $_REQUEST['usuario'];
  $ARRAYUSUARIO = $USUARIO_ADO->ObtenerNombreCompleto($USUARIO);
  $NOMBRE = $ARRAYUSUARIO[0]["NOMBRE_COMPLETO"];
}


if (isset($_REQUEST['parametro'])) {
  $IDOP = $_REQUEST['parametro'];
}


  
$ARRAYICARGA=$ICARGA_ADO->verIcarga2($IDOP);
if($ARRAYICARGA){
      
    $ARRAYDCARGA = $DICARGA_ADO->buscarPorIcarga2($IDOP);
    $ARRAYDCARGATOTAL2 = $DICARGA_ADO->totalesPorIcarga2($IDOP);
    $ARRAYCONSOLIDADODESPACHO =  $DESPACHOEX_ADO->consolidadoDespachoExistencia2($IDOP);
    $ARRAYCONSOLIDADODESPACHOTOTAL =  $DESPACHOEX_ADO->obtenerTotalconsolidadoDespachoExistencia2($IDOP);

    $TOTALENVASECONSOLIADO=$ARRAYCONSOLIDADODESPACHOTOTAL[0]['ENVASE'];
    $TOTALNETOCONSOLIADO=$ARRAYCONSOLIDADODESPACHOTOTAL[0]['NETO'];
    $TOTALBRUTOCONSOLIADO=$ARRAYCONSOLIDADODESPACHOTOTAL[0]['BRUTO'];

    
    $IDUSUARIOI = $ARRAYICARGA[0]['ID_USUARIOI'];  
    $ARRAYUSUARIO2 = $USUARIO_ADO->ObtenerNombreCompleto($IDUSUARIOI);
    $NOMBRERESPONSABLE = $ARRAYUSUARIO2[0]["NOMBRE_COMPLETO"];
    
  
    $ARRAYDESPACHOEX=$DESPACHOEX_ADO->buscarDespachoExPorIcarga($IDOP);
    $ARRAYDESPACHOEX2=$DESPACHOEX_ADO->buscarDespachoExPorIcargaAgrupadoPorPlanta($IDOP);
    if($ARRAYDESPACHOEX){
      $NUMEROCONTENEDOR=$ARRAYDESPACHOEX[0]['NUMERO_CONTENEDOR_DESPACHOEX'];
      $_fechas = []; $_sellos = []; $_lugares = []; $_fdas = [];
      foreach ($ARRAYDESPACHOEX2 as $r) :
        if(isset($r['FECHA']) && $r['FECHA']) $_fechas[] = $r['FECHA'];
        if(isset($r['NUMERO_SELLO_DESPACHOEX']) && $r['NUMERO_SELLO_DESPACHOEX']) $_sellos[] = $r['NUMERO_SELLO_DESPACHOEX'];
        $ARRAYVERPLANTA = $PLANTA_ADO->verPlanta($r['ID_PLANTA']);
        if($ARRAYVERPLANTA){
          $_lugares[] = $ARRAYVERPLANTA[0]["RAZON_SOCIAL_PLANTA"];
          $_fdas[]    = $ARRAYVERPLANTA[0]["FDA_PLANTA"];
        }
      endforeach;
      $FECHADESPACHOEX = implode("<br>", array_unique($_fechas))  ?: "";
      $NUMEROSELLO     = implode("<br>", array_unique($_sellos))  ?: "";
      $LUGARDECARGA    = implode("<br>", array_unique($_lugares)) ?: "";
      $FDADESPACHOEX   = implode("<br>", array_unique($_fdas))    ?: "";
    }else{
      $FDADESPACHOEX="";
      $NUMEROCONTENEDOR=$ARRAYICARGA[0]['NCONTENEDOR_ICARGA'];
      $NUMEROSELLO="";
      $FECHADESPACHOEX="";
      $LUGARDECARGA="";
    }
    $TOTALENVASEV = $ARRAYDCARGATOTAL2[0]['ENVASE'];
    $TOTALNETOV = $ARRAYDCARGATOTAL2[0]['NETO'];
    $TOTALBRUTOV = $ARRAYDCARGATOTAL2[0]['BRUTO'];
    $TOTALUSV = $ARRAYDCARGATOTAL2[0]['TOTALUS'];
      
      $NUMEROICARGA=$ARRAYICARGA[0]["NUMERO_ICARGA"];
      $NUMEROIREFERENCIA=$ARRAYICARGA[0]["NREFERENCIA_ICARGA"];
      $FECHACDOCUMENTALICARGA = $ARRAYICARGA[0]["FECHACD"];
      $FECHA=$ARRAYICARGA[0]["FECHA"];
      $BOOKINGINSTRUCTIVO = $ARRAYICARGA[0]['BOOKING_ICARGA'];
      $TEMBARQUE = $ARRAYICARGA[0]['TEMBARQUE_ICARGA'];
      $FECHAETD = $ARRAYICARGA[0]['FECHAETD'];
      $FECHAETA = $ARRAYICARGA[0]['FECHAETA'];
      $FECHAETDREAL = $ARRAYICARGA[0]['FECHAETDREAL'];
      if(!$FECHAETDREAL){
        $FECHAETDREAL = "";
      }
      $FECHAETAREAL = $ARRAYICARGA[0]['FECHAETAREAL'];
        $BOLAWBCRTINSTRUCTIVO = $ARRAYICARGA[0]['CRT_ICARGA'];
        if(!$BOLAWBCRTINSTRUCTIVO){
          $BOLAWBCRTINSTRUCTIVO = "";
        }

      $TINSTRUCTIVO = $ARRAYICARGA[0]['T_ICARGA'];
      $O2INSTRUCTIVO = $ARRAYICARGA[0]['O2_ICARGA'];
      $CO2INSTRUCTIVO = $ARRAYICARGA[0]['C02_ICARGA'];
      $ALAMPAINSTRUCTIVO = $ARRAYICARGA[0]['ALAMPA_ICARGA'];

      $OBSERVACIONES = $ARRAYICARGA[0]['OBSERVACION_ICARGA'];
      if($ARRAYICARGA[0]['FUMIGADO_ICARGA']==1){
        $FUMIGADO="Yes";
      }else if($ARRAYICARGA[0]['FUMIGADO_ICARGA']==2){
        $FUMIGADO="No";
      }else{
        $FUMIGADO="";
      }

      $ESTADO = $ARRAYICARGA[0]['ESTADO'];
      if ($ARRAYICARGA[0]['ESTADO'] == 1) {
        $ESTADO = "Open";
      }else if ($ARRAYICARGA[0]['ESTADO'] == 0) {
        $ESTADO = "Closer";
      }else{
        $ESTADO="";
      }  
      $ARRAYRFINAL=$RFINAL_ADO->verRfinal($ARRAYICARGA[0]["ID_RFINAL"]);
      if($ARRAYRFINAL){
          $NOMBRERFINAL=$ARRAYRFINAL[0]["NOMBRE_RFINAL"];
      }else{
          $NOMBRERFINAL="";
      }
      if($ARRYANOTIFICADOR){
        $NOMBRENOTIFICADOR=$ARRYANOTIFICADOR[0]["NOMBRE_NOTIFICADOR"];
        $DIRECCIONNOTIFICADOR=$ARRYANOTIFICADOR[0]["DIRECCION_NOTIFICADOR"];
        $EORINOTIFICADOR=$ARRYANOTIFICADOR[0]["EORI_NOTIFICADOR"];
        $TELEFONONOTIFICADOR=$ARRYANOTIFICADOR[0]["TELEFONO_NOTIFICADOR"];
        $EMAIL1NOTIFICADOR=$ARRYANOTIFICADOR[0]["EMAIL1_NOTIFICADOR"];
      }else{
        $NOMBRENOTIFICADOR="";
        $EORINOTIFICADOR="";
        $TELEFONONOTIFICADOR="";
        $DIRECCIONNOTIFICADOR="";
        $EMAIL1NOTIFICADOR="";
      }
      $ARRAYCONSIGNATARIO = $CONSIGNATARIO_ADO->verConsignatorio($ARRAYICARGA[0]['ID_CONSIGNATARIO']);            
      if($ARRAYCONSIGNATARIO){
        $NOMBRECONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["NOMBRE_CONSIGNATARIO"];
        $DIRECCIONCONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["DIRECCION_CONSIGNATARIO"];
        $EORICONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["EORI_CONSIGNATARIO"];
        $TELEFONOCONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["TELEFONO_CONSIGNATARIO"];
        $EMAIL1CONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["EMAIL1_CONSIGNATARIO"];
      }else{
        $NOMBRECONSIGNATARIO="";
        $EORICONSIGNATARIO="";
        $TELEFONOCONSIGNATARIO="";
        $DIRECCIONCONSIGNATARIO="";
        $EMAIL1CONSIGNATARIO="";
      }
      $ARRAYFPAGO = $FPAGO_ADO->verFpago(  $ARRAYICARGA[0]['ID_FPAGO']);         
      if($ARRAYFPAGO){
        $NOMBREFPAGO=$ARRAYFPAGO[0]["NOMBRE_FPAGO"];
      }else{
        $NOMBREFPAGO="";
      }
      $ARRAYMVENTA = $MVENTA_ADO->verMventa( $ARRAYICARGA[0]['ID_MVENTA']);        
      if($ARRAYMVENTA){
        $NOMBREMVENTA=$ARRAYMVENTA[0]["NOMBRE_MVENTA"];
      }else{
        $NOMBREMVENTA="";
      }
      $ARRAYCVENTA = $CVENTA_ADO->verCventa( $ARRAYICARGA[0]['ID_CVENTA']);        
      if($ARRAYMVENTA){
        $NOMBRECVENTA=$ARRAYCVENTA[0]["NOMBRE_CVENTA"];
      }else{
        $NOMBRECVENTA="";
      }
      $ARRAYTFLETE= $TFLETE_ADO->verTflete( $ARRAYICARGA[0]['ID_TFLETE']);        
      if($ARRAYTFLETE){
        $NOMBRETFLETE=$ARRAYTFLETE[0]["NOMBRE_TFLETE"];
      }else{
        $NOMBRETFLETE="";
      }      
      $ARRAYATMOSFERA =$ATMOSFERA_ADO->verAtmosfera( $ARRAYICARGA[0]['ID_ATMOSFERA']);
      if($ARRAYATMOSFERA){
        $NOMBREATMOSFERA=$ARRAYATMOSFERA[0]["NOMBRE_ATMOSFERA"];
      }else{
        $NOMBREATMOSFERA="";
      }
      $ARRAYEMISIONBL =$EMISIONBL_ADO->verEmisionbl( $ARRAYICARGA[0]['ID_EMISIONBL']);
      if($ARRAYEMISIONBL){
        $NOMBREEMISIONBL=$ARRAYEMISIONBL[0]["NOMBRE_EMISIONBL"];
      }else{
        $NOMBREEMISIONBL="";
      }
      $ARRAYTCONTENEDOR =$TCONTENEDOR_ADO->verTcontenedorInstructivo( $ARRAYICARGA[0]['ID_TCONTENEDOR']);
      if($ARRAYTCONTENEDOR){
        $NOMBRETCONTENEDOR=$ARRAYTCONTENEDOR[0]["NOMBRE_TCONTENEDOR"];
      }else{
        $NOMBRETCONTENEDOR="";
      }      
      $ARRAYPAIS =$PAIS_ADO->verPais( $ARRAYICARGA[0]['ID_PAIS']);
      if($ARRAYPAIS){
        $NOMBREPAIS=$ARRAYPAIS[0]["NOMBRE_PAIS"];
      }else{
        $NOMBREPAIS="";
      }
      $ARRAYEXPORTADORA = $EXPORTADORA_ADO->verExportadora( $ARRAYICARGA[0]['ID_EXPPORTADORA']);
      if($ARRAYEXPORTADORA){
        $RUTEXPPORTADORA=$ARRAYEXPORTADORA[0]["RUT_EXPORTADORA"]."-".$ARRAYEXPORTADORA[0]["DV_EXPORTADORA"];
        $NOMBREEXPPORTADORA=$ARRAYEXPORTADORA[0]["NOMBRE_EXPORTADORA"];
      }else{
        $RUTEXPPORTADORA="";
        $NOMBREEXPPORTADORA="";
      }
      $ARRAYDFINAL =$DFINAL_ADO->verDfinal( $ARRAYICARGA[0]['ID_DFINAL']);
      if($ARRAYDFINAL){
        $NOMBREDFINAL=$ARRAYDFINAL[0]["NOMBRE_DFINAL"];
      }else{
        $NOMBREDFINAL="";
      }

    if($TEMBARQUE){
          if ($TEMBARQUE == "1") {
              $NOMBRETEMBARQUE="Land";
              $CRT=$ARRAYICARGA[0]['CRT_ICARGA'];
              $ARRAYTRANSPORTE =$TRANSPORTE_ADO->verTransporte( $ARRAYICARGA[0]['ID_TRANSPORTE']);        
              if($ARRAYTRANSPORTE){
                $NOMBRETRANSPORTE=$ARRAYTRANSPORTE[0]["NOMBRE_TRANSPORTE"];
              }else{
                $NOMBRETRANSPORTE="";
              }            
              $ARRAYLCARGA =$LCARGA_ADO->verLcarga(  $ARRAYICARGA[0]['ID_LCARGA']);       
              if($ARRAYLCARGA){
                $NOMBREORIGEN=$ARRAYLCARGA[0]["NOMBRE_LCARGA"];
              }else{
                $NOMBREORIGEN="";
              }
              $ARRAYLDESTINO =$LDESTINO_ADO->verLdestino( $ARRAYICARGA[0]['ID_LDESTINO']);     
              if($ARRAYLDESTINO){
                $NOMBREDESTINO=$ARRAYLDESTINO[0]["NOMBRE_LDESTINO"];
              }else{
                $NOMBREDESTINO="";
              }
          }
          if ($TEMBARQUE == "2") {
              $NOMBRETEMBARQUE="Air";
              $NAVE=$ARRAYICARGA[0]['NAVE_ICARGA'];
              $NVIAJE = $ARRAYICARGA[0]['NVIAJE_ICARGA'];
             
              $ARRAYLAEREA = $LAEREA_ADO->verLaerea( $ARRAYICARGA[0]['ID_LAREA']);      
              if($ARRAYLAEREA){
                $NOMBRETRANSPORTE=$ARRAYLAEREA[0]["NOMBRE_LAEREA"];
              }else{
                $NOMBRETRANSPORTE="";
              }            
              $ARRAYACARGA =$ACARGA_ADO->verAcarga(  $ARRAYICARGA[0]['ID_ACARGA']);  
              if($ARRAYACARGA){
                $NOMBREORIGEN=$ARRAYACARGA[0]["NOMBRE_ACARGA"];
              }else{
                $NOMBREORIGEN="";
              }
              $ARRAYADESTINO =$ADESTINO_ADO->verAdestino( $ARRAYICARGA[0]['ID_ADESTINO']);  
              if($ARRAYADESTINO){
                $NOMBREDESTINO=$ARRAYADESTINO[0]["NOMBRE_ADESTINO"];
              }else{
                $NOMBREDESTINO="";
              }
          }
          if ($TEMBARQUE == "3") {
              $NOMBRETEMBARQUE="Sea";
              $NAVE  = $ARRAYICARGA[0]['NAVE_ICARGA'];
              $NVIAJE = $ARRAYICARGA[0]['NVIAJE_ICARGA'];
              $FECHASTACKING  = $ARRAYICARGA[0]['FECHAESTACKING'];
              $FECHASTACKINGF = $ARRAYICARGA[0]['FECHASTACKINGF'];
              $ARRAYNAVIERA =$NAVIERA_ADO->verNaviera( $ARRAYICARGA[0]['ID_NAVIERA']);   
              if($ARRAYNAVIERA){
                $NOMBRETRANSPORTE=$ARRAYNAVIERA[0]["NOMBRE_NAVIERA"];
              }else{
                $NOMBRETRANSPORTE="";
              }            
              $ARRAYPCARGA =$PCARGA_ADO->verPcarga(  $ARRAYICARGA[0]['ID_PCARGA']);
              if($ARRAYPCARGA){
                $NOMBREORIGEN=$ARRAYPCARGA[0]["NOMBRE_PCARGA"];
              }else{
                $NOMBREORIGEN="";
              }
              $ARRAYPDESTINO =$PDESTINO_ADO->verPdestino( $ARRAYICARGA[0]['ID_PDESTINO']);
              if($ARRAYPDESTINO){
                $NOMBREDESTINO=$ARRAYPDESTINO[0]["NOMBRE_PDESTINO"];
              }else{
                $NOMBREDESTINO="";
              }
          }
    }      
  
    $ARRAYAGCARGA = $AGCARGA_ADO->verAgcarga(  $ARRAYICARGA[0]['ID_AGCARGA']); 
    if($ARRAYAGCARGA){
      $RUTAGCARGA=$ARRAYAGCARGA[0]["RUT_AGCARGA"]."-".$ARRAYAGCARGA[0]["DV_AGCARGA"];
      $NOMBREAGCARGA=$ARRAYAGCARGA[0]["NOMBRE_AGCARGA"];
      $DIRECCIONAGCARGA=$ARRAYAGCARGA[0]["DIRECCION_AGCARGA"];
      $CONTACTOAGCARGA=$ARRAYAGCARGA[0]["CONTACTO_AGCARGA"];
      $EMAILAGCARGA=$ARRAYAGCARGA[0]["EMAIL_AGCARGA"];
      $TELEFONOAGCARGA=$ARRAYAGCARGA[0]["TELEFONO_AGCARGA"];
    }else{
      $RUTAGCARGA="";
      $NOMBREAGCARGA="";
      $DIRECCIONAGCARGA="";
      $CONTACTOAGCARGA="";
      $EMAILAGCARGA="";
      $TELEFONOAGCARGA="";
    } 
    $ARRAYAADUANA = $AADUANA_ADO->verAaduana( $ARRAYICARGA[0]['ID_AADUANA']);
    if($ARRAYAADUANA){
      $RUTAADUANA=$ARRAYAADUANA[0]["RUT_AADUANA"]."-".$ARRAYAADUANA[0]["DV_AADUANA"];
      $NOMBREAADUANA=$ARRAYAADUANA[0]["NOMBRE_AADUANA"];
      $DIRECCIONAADUANA=$ARRAYAADUANA[0]["DIRECCION_AADUANA"];
      $CONTACTOAADUANA=$ARRAYAADUANA[0]["CONTACTO_AADUANA"];
      $EMAILAADUANA=$ARRAYAADUANA[0]["EMAIL_AADUANA"];
      $TELEFONOAADUANA=$ARRAYAADUANA[0]["TELEFONO_AADUANA"];
    }else{
      $RUTAADUANA="";
      $NOMBREAADUANA="";
      $DIRECCIONAADUANA="";
      $CONTACTOAADUANA="";
      $EMAILAADUANA="";
      $TELEFONOAADUANA="";
    }



    $ARRAYEMPRESA = $EMPRESA_ADO->verEmpresa($ARRAYICARGA[0]['ID_EMPRESA']);
    if($ARRAYEMPRESA){
      $NOMBREEMPRESA=$ARRAYEMPRESA[0]["NOMBRE_EMPRESA"];
      $RAZONSOCIALEMPRESA = $ARRAYEMPRESA[0]["RAZON_SOCIAL_EMPRESA"];
      $RUTEMPRESA=$ARRAYEMPRESA[0]["RUT_EMPRESA"]."-".$ARRAYEMPRESA[0]["DV_EMPRESA"];
      $DIRECCIONEMPRESA=$ARRAYEMPRESA[0]["DIRECCION_EMPRESA"];
      $ARRAYCOMUNA=$COMUNA_ADO->verComuna2($ARRAYEMPRESA[0]["ID_COMUNA"]);
      if($ARRAYCOMUNA){
        $UBICACION=$ARRAYCOMUNA[0]["COMUNA"].", ".$ARRAYCOMUNA[0]["PAIS"];
        $DIRECCIONEMPRESA=$DIRECCIONEMPRESA.", ".$UBICACION;
      }else{
        $DIRECCIONEMPRESA=$DIRECCIONEMPRESA;
      }
    }else{    
      $NOMBREEMPRESA="";
      $RAZONSOCIALEMPRESA="";
      $RUTEMPRESA="";
      $DIRECCIONEMPRESA="";
    }
  $ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($ARRAYICARGA[0]['ID_TEMPORADA']);  
  if($ARRAYTEMPORADA){
    $NOMBRETEMPORADA=$ARRAYTEMPORADA[0]["NOMBRE_TEMPORADA"];
  }else{
    $NOMBRETEMPORADA="";
  }

} 






//OBTENCION DE LA FECHA
date_default_timezone_set('America/Santiago');
//SE LE PASA LA FECHA ACTUAL A UN ARREGLO
$ARRAYFECHADOCUMENTO =getdate();

//SE OBTIENE INFORMACION RELACIONADA CON LA HORA
$HORA="".$ARRAYFECHADOCUMENTO['hours'];
$MINUTO="".$ARRAYFECHADOCUMENTO['minutes'];
$SEGUNDO="".$ARRAYFECHADOCUMENTO['seconds'];
//EN CASO DE VALORES MENOS A 2 LENGHT, SE LE CONCATENA UN 0
if ($MINUTO < 10) {
    $MINUTO = "0".$MINUTO;
}
if ($SEGUNDO < 10) {
    $SEGUNDO = "0".$SEGUNDO;
}

// SE JUNTA LA INFORMAICON DE LA HORA Y SE LE DA UN FORMATO
$HORAFINAL=$HORA."".$MINUTO."".$SEGUNDO;
$HORAFINAL2=$HORA.":".$MINUTO.":".$SEGUNDO;

//SE OBTIENE INFORMACION RELACIONADA CON LA FECHA
$DIA="".$ARRAYFECHADOCUMENTO['mday'];

$MES="".$ARRAYFECHADOCUMENTO['mon'];
$ANO="".$ARRAYFECHADOCUMENTO['year'];
$NOMBREMES="".$ARRAYFECHADOCUMENTO['month'];
$NOMBREDIA="".$ARRAYFECHADOCUMENTO['weekday'];
//EN CASO DE VALORES MENOS A 2 LENGHT, SE LE CONCATENA UN 0
if ($DIA < 10) {
    $DIA = "0".$DIA;
}
//PARA TRAUDCIR EL MES AL ESPAÑOL
$MESESNOMBRES= array(
    "January" => "Enero",
    "February" => "Febrero",
    "March" => "Marzo",
    "April" => "Abril",
    "May" => "Mayo",
    "June" => "Junio",
    "July" => "Julio",
    "August" => "Agosto",
    "September" => "Septiembre",
    "October" => "Octubre",
    "November" => "Noviembre",
    "December" => "Diciembre"
); 
//PARA TRAUDCIR EL DIA AL ESPAÑOL
$DIASNOMBRES= array(
    "Monday" => "Lunes",
    "Tuesday" => "Martes",
    "Wednesday" => "Miércoles",
    "Thursday" => "Jueves",
    "Friday" => "Viernes",
    "Saturday" => "Sábado",
    "Sunday" => "Domingo"
); 

$NOMBREDIA = $DIASNOMBRES[$NOMBREDIA];
$NOMBREMES = $MESESNOMBRES[$NOMBREMES];
// SE JUNTA LA INFORMAICON DE LA FECHA Y SE LE DA UN FORMATO
$FECHANORMAL=$DIA."".$MES."".$ANO;
$FECHANORMAL2=$DIA."/".$MES."/".$ANO;
$FECHANOMBRE=$NOMBREDIA.", ".$DIA." de ".$NOMBREMES." del ".$ANO;


$html='
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Shipping Instruction</title>
  </head>
  <body>
    <header class="clearfix">
      <table>
        <tr>
          <td class="color2 left">
            <div id="logo">
                <img src="../../assest/img/logo2.png" width="150px" height="45px"/>
            </div>
          </td>
          <td class="color2 left" width="70%">
            <b>'.$RAZONSOCIALEMPRESA.'</b> <br>
            '.$RUTEMPRESA.' <br>
            '.$DIRECCIONEMPRESA.' <br>          
          </td>
          <td class="color2 right">
              &nbsp;&nbsp;
          </td>
        </tr>
      </table>  
    </header>
    <main>

    <table border="0" cellspacing="0" cellpadding="8" style="width:100%; background:#505050; margin-bottom:4px;">
      <tr>
        <td style="background:#505050; color:#ffffff; font-size:20px; font-weight:bold; text-align:left; border-bottom:none;"><b>SHIPPING INSTRUCTION</b></td>
        <td style="background:#505050; color:#ffffff; font-size:13px; text-align:right; border-bottom:none;"><b>Reference Number: ' . $NUMEROIREFERENCIA . '</b></td>
      </tr>
    </table>

    <table class="t6" border="0" cellspacing="0" cellpadding="0">
      <thead>
        <tr>
          <th colspan="2" class="center color">Shipping Instruction Data</th>
          <th colspan="2" class="center color">Consignee</th>
          <th colspan="2" class="center color">Notifier</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <th class="color2 left">Date:</th><td class="color2 left">'.$FECHA.'</td>
          <th class="color2 left">Name:</th><td class="color2 left">'.$NOMBRECONSIGNATARIO.'</td>
          <th class="color2 left">Name:</th><td class="color2 left">'.$NOMBRENOTIFICADOR.'</td>
        </tr>
        <tr>
          <th class="color2 left">Instructive No.:</th><td class="color2 left">'.$NUMEROICARGA.'</td>
          <th class="color2 left">Address:</th><td class="color2 left">'.$DIRECCIONCONSIGNATARIO.'</td>
          <th class="color2 left">Address:</th><td class="color2 left">'.$DIRECCIONNOTIFICADOR.'</td>
        </tr>
        <tr>
          <th class="color2 left">Reference No.:</th><td class="color2 left">'.$NUMEROIREFERENCIA.'</td>
          <th class="color2 left">Tax Id:</th><td class="color2 left">'.$EORICONSIGNATARIO.'</td>
          <th class="color2 left">Tax Id:</th><td class="color2 left">'.$EORINOTIFICADOR.'</td>
        </tr>
        <tr>
          <th class="color2 left">Status:</th><td class="color2 left">'.$ESTADO.'</td>
          <th class="color2 left">Phone/Fax:</th><td class="color2 left">'.$TELEFONOCONSIGNATARIO.'</td>
          <th class="color2 left">Phone/Fax:</th><td class="color2 left">'.$TELEFONONOTIFICADOR.'</td>
        </tr>
        <tr>
          <th class="color2 left">Season:</th><td class="color2 left">'.$NOMBRETEMPORADA.'</td>
          <th class="color2 left">Email:</th><td class="color2 left">'.$EMAIL1CONSIGNATARIO.'</td>
          <th class="color2 left">Email:</th><td class="color2 left">'.$EMAIL1NOTIFICADOR.'</td>
        </tr>
      </tbody>
    </table>
    ';

     $html=$html.'
     <table class="t4" border="0" cellspacing="0" cellpadding="0">
       <thead>
         <tr>
           <th colspan="4" class="center color">Dispatch Info</th>
         </tr>
       </thead>
       <tbody>     
        <tr>                       
          <th class="color2 left">Loading place: </th> 
          <td class="color2 left">'.$LUGARDECARGA.'</td>   
          <th class="color2 left">FDA Packing: </th> 
          <td class="color2 left">'.$FDADESPACHOEX.'</td>       
        </tr>      
          <tr>                       
            <th class="color2 left">Date  dispatch: </th> 
            <td class="color2 left">'.$FECHADESPACHOEX.'</td>   
            <th class="color2 left">Packing seal: </th>       
            <td class="color2 left">'.$NUMEROSELLO.'</td>        
        </tr>  
       </tbody>  
     </table>
       
       ' ;

     $html=$html.'
     <table class="t6" border="0" cellspacing="0" cellpadding="0">
       <thead>
         <tr>
           <th colspan="6" class="center color">Shipment Info</th>
         </tr>
       </thead>
       <tbody>
         <tr>
           <th class="color2 left">Date ETD: </th>    
           <td class="color2 left">'.$FECHAETD.'</td>      
           <th class="color2 left">Booking: </th>        
           <td class="color2 left">'.$BOOKINGINSTRUCTIVO.'</td>      
           <th class="color2 left">Date Documentary cut : </th>       
           <td class="color2 left">'.$FECHACDOCUMENTALICARGA.'</td>               
         </tr>    
         <tr>                       
         <th class="color2 left">Date ETA: </th>
          <td class="color2 left">'.$FECHAETA.'</td>
          <th class="color2 left">Fumigation: </th>
          <td class="color2 left">'.$FUMIGADO.'</td>
          <th class="color2 left">Container number: </th>
          <td class="color2 left">'.$NUMEROCONTENEDOR.'</td>
        </tr>
         <tr>
           <th class="color2 left">Date Real ETD : </th>
           <td class="color2 left">'.$FECHAETDREAL.'</td>
           <th class="color2 left">&nbsp; </th>
           <td class="color2 left">&nbsp;</td>
           <th class="color2 left">&nbsp; </th>
           <td class="color2 left">&nbsp;</td>
         </tr>
         <tr>
           <th class="color2 left">Date True ETA : </th>
           <td class="color2 left">'.$FECHAETAREAL.'</td>
           <th class="color2 left">&nbsp; </th>    
           <td class="color2 left">&nbsp;</td>      
           <th class="color2 left">Issue BL: </th>       
           <td class="color2 left">'.$NOMBREEMISIONBL.'</td>     
         </tr>  
         ';
         if ($TEMBARQUE == "1") {
           $html = $html . '
           
           <tr>
             <th class="color2 left"> Transport Name: </th>    
             <td class="color2 left">'.$NOMBRETRANSPORTE.'</td>     
             <th class="color2 left">CRT: </th> 
             <td class="color2 left">'.$CRT.'</td>     
             <th class="color2 left">&nbsp;</th>       
             <td class="color2 left">&nbsp;</td>       
           </tr>    
           ';
         }
     
         if ($TEMBARQUE == "2") {
           $html = $html . '
           
           <tr>
             <th class="color2 left"> Airline Name: </th>
             <td class="color2 left">'.$NOMBRETRANSPORTE.'</td>
             <th class="color2 left">Aircraft: </th>
             <td class="color2 left">'.$NAVE.'</td>
             <th class="color2 left">Flight No.: </th>
             <td class="color2 left">'.$NVIAJE.'</td>
           </tr>    
           ';
         }
         if ($TEMBARQUE == "3") {
           $html = $html . '
           
           <tr>
             <th class="color2 left">Shipping Company: </th>
             <td class="color2 left">'.$NOMBRETRANSPORTE.'</td>
             <th class="color2 left">Vessel: </th>
             <td class="color2 left">'.$NAVE.'</td>
             <th class="color2 left">Voyage No.: </th>
             <td class="color2 left">'.$NVIAJE.'</td>
           </tr>   
           <tr>
             <th class="color2 left">Stacking Opening Date: </th>
             <td class="color2 left">'.$FECHASTACKING.'</td>
             <th class="color2 left">Stacking Closing Date: </th>
             <td class="color2 left">'.$FECHASTACKINGF.'</td>
             <th class="color2 left">&nbsp;</th>
             <td class="color2 left">&nbsp;</td>
           </tr>
           ';
         }
     
     $html = $html . '      
       </tbody>  
     </table>
         
     ';


     $html=$html.'
     <table class="t4" border="0" cellspacing="0" cellpadding="0">
       <thead>
         <tr>
           <th colspan="4" class="center color">Shipment Conditions</th>
         </tr>
       </thead>
       <tbody>
         <tr>                       
           <th class="color2 left">incoterm: </th> 
           <td class="color2 left">'.$NOMBRECVENTA.'</td>     
           <th class="color2 left">Fraight condition: </th>     
           <td class="color2 left">'.$NOMBRETFLETE.'</td>      
         </tr> 
         <tr>   
           <th class="color2 left">Selling Arragement: </th> 
           <td class="color2 left">'.$NOMBREMVENTA.'</td>    
           <th class="color2 left">BOL/AWB/CRT: </th>   
           <td class="color2 left">'.$BOLAWBCRTINSTRUCTIVO.'</td>        
         </tr>  
         <tr>                       
           <th class="color2 left">Paynebt condition: </th>    
           <td class="color2 left">'.$NOMBREFPAGO.'</td>      
           <th class="color2 left">&nbsp;</th>       
           <td class="color2 left">&nbsp;</td>      
         </tr>           
          
       </tbody>  
     </table>    
     ';
     
$html=$html.'
<table class="t6" border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th colspan="6" class="center color">Fumigation Info</th>
    </tr>
  </thead>
  <tbody>
    <tr>                       
      <th class="color2 left">Atmosphere: </th> 
      <td class="color2 left">'.$NOMBREATMOSFERA.'</td>         
      <th class="color2 left">Type container: </th>    
      <td class="color2 left">'.$NOMBRETCONTENEDOR.'</td>          
      <th class="color2 left">Opening Lampa(CBM): </th>       
      <td class="color2 left">'.$ALAMPAINSTRUCTIVO.'%</td>       
    </tr> 
    <tr>   
      <th class="color2 left">Temperature: </th> 
      <td class="color2 left">'.$TINSTRUCTIVO.'</td>      
      <th class="color2 left"> O2: </th>   
      <td class="color2 left">'.$O2INSTRUCTIVO.'%</td>       
      <th class="color2 left"> CO2: </th>   
      <td class="color2 left">'.$CO2INSTRUCTIVO.'%</td>              
    </tr>         
  </tbody>  
</table>    
';

$html=$html.'
<table class="t6" border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th colspan="6" class="center color">Other Shipment Info</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <th class="color2 left">Type of shipment: </th>
      <td class="color2 left">'.$NOMBRETEMBARQUE.'</td>
      <th class="color2 left">Final Destination: </th>
      <td class="color2 left">'.$NOMBREDFINAL.'</td>
      <th class="color2 left">&nbsp;</th>
      <td class="color2 left">&nbsp;</td>
    </tr>
    ';
    if ($TEMBARQUE == "1") {
      $html = $html . '
      <tr>
        <th class="color2 left">Place of Origin: </th>
        <td class="color2 left">'.$NOMBREORIGEN.'</td>
        <th class="color2 left">Place of Destination: </th>
        <td class="color2 left">'.$NOMBREDESTINO.'</td>
        <th class="color2 left">&nbsp;</th>
        <td class="color2 left">&nbsp;</td>
      </tr>
      ';
    }
    if ($TEMBARQUE == "2") {
      $html = $html . '
      <tr>
        <th class="color2 left">Airport of Origin: </th>
        <td class="color2 left">'.$NOMBREORIGEN.'</td>
        <th class="color2 left">Airport of Destination: </th>
        <td class="color2 left">'.$NOMBREDESTINO.'</td>
        <th class="color2 left">&nbsp;</th>
        <td class="color2 left">&nbsp;</td>
      </tr>
      ';
    }
    if ($TEMBARQUE == "3") {
      $html = $html . '
      <tr>
        <th class="color2 left">Port of Origin: </th>
        <td class="color2 left">'.$NOMBREORIGEN.'</td>
        <th class="color2 left">Port of Destination: </th>
        <td class="color2 left">'.$NOMBREDESTINO.'</td>
        <th class="color2 left">&nbsp;</th>
        <td class="color2 left">&nbsp;</td>
      </tr>
      ';
    }

$html = $html . '
    <tr>
      <th class="color2 left">Exporter Rut: </th>
      <td class="color2 left">'.$RUTEXPPORTADORA.'</td>
      <th class="color2 left">Exporter Name: </th>
      <td class="color2 left">'.$NOMBREEXPPORTADORA.'</td>
      <th class="color2 left">&nbsp;</th>
      <td class="color2 left">&nbsp;</td>
    </tr>
    <tr>
      <th class="color2 left">Country of Origin: </th>
      <td class="color2 left">Chile</td>
      <th class="color2 left">Country of Destination: </th>
      <td class="color2 left">'.$NOMBREPAIS.'</td>
      <th class="color2 left">&nbsp;</th>
      <td class="color2 left">&nbsp;</td>
    </tr>
  </tbody>
</table>
';
$html=$html.'
';

$html=$html.'
<table class="t6" border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th colspan="6" class="center color">Cargo Agent Info</th>
    </tr>
  </thead>
  <tbody>
    <tr>                       
      <th class="color2 left">Rut: </th> 
      <td class="color2 left">'.$RUTAGCARGA.'</td>         
      <th class="color2 left">Name: </th>    
      <td class="color2 left">'.$NOMBREAGCARGA.'</td>            
      <th class="color2 left">Address: </th>       
      <td class="color2 left">'.$DIRECCIONAGCARGA.'</td>        
    </tr> 
    <tr>   
      <th class="color2 left">Contact:</th> 
      <td class="color2 left">'.$CONTACTOAGCARGA.'</td>         
      <th class="color2 left">Contact Phone: </th>  
      <td class="color2 left">'.$TELEFONOAGCARGA.'</td>   
      <th class="color2 left">Contact Email: </th>  
      <td class="color2 left">'.$EMAILAGCARGA.'</td>          
    </tr>           
  </tbody>  
</table>    
';




$html=$html.'
<table class="t6" border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th colspan="6" class="center color">Customs Agency Info</th>
    </tr>
  </thead>
  <tbody>
    <tr>                       
      <th class="color2 left">Rut: </th> 
      <td class="color2 left">'.$RUTAADUANA.'</td>         
      <th class="color2 left">Name: </th>    
      <td class="color2 left">'.$NOMBREAADUANA.'</td>              
      <th class="color2 left">Address: </th>  
      <td class="color2 left">'.$DIRECCIONAADUANA.'</td>             
    </tr> 
    <tr>   
      <th class="color2 left">Contact: </th> 
      <td class="color2 left">'.$CONTACTOAADUANA.'</td>         
      <th class="color2 left">Contact Phone: </th>   
      <td class="color2 left">'.$TELEFONOAADUANA.'</td>     
      <th class="color2 left">Contact Email : </th>  
      <td class="color2 left">'.$EMAILAADUANA.'</td>           
    </tr>        
  </tbody>  
  
</table>    
';

$html=$html.'
<table  border="0" cellspacing="0" cellpadding="0">
  <thead>
    <tr>
      <th colspan="13" class="center">Instruity Charge</th>
    </tr>
    <tr>                       
      <th class="color center ">Code </th>
      <th class="color center ">Description of goods </th>
      <th class="color center ">Type of condition </th>
      <th class="color center ">Net Weight </th>
      <th class="color center ">Gross Weight </th>
      <th class="color center ">Amount Boxes</th>
      <th class="color center ">Net Kilo </th>
      <th class="color center ">Gross Kilo </th>
      <th class="color center ">Caliber </th>
      <th class="color center ">Type of currency </th>
      <th class="color center ">variety </th>
      <th class="color center ">Price</th>
      <th class="color center ">Total</th>    
    </tr> 
  </thead>
  ';
$html = $html . '    
  <tbody>        
    ';

    foreach ($ARRAYDCARGA as $s) :  


      $ARRAYEEXPORTACION = $EEXPORTACION_ADO->verEstandar($s['ID_ESTANDAR']);
      if ($ARRAYEEXPORTACION) {
          $CODIGOESTANDAR = $ARRAYEEXPORTACION[0]['CODIGO_ESTANDAR'];
          $NOMBREESTANTAR = $ARRAYEEXPORTACION[0]['NOMBRE_ESTANDAR'];
          $NETOESTANTAR = $ARRAYEEXPORTACION[0]['PESO_NETO_ESTANDAR'];
          $BRUTOESTANTAR = $ARRAYEEXPORTACION[0]['PESO_BRUTO_ESTANDAR'];
      } else {
          $CODIGOESTANDAR = "";
          $NOMBREESTANTAR = "";
          $NETOESTANTAR = "";
          $BRUTOESTANTAR = "";
      }
   
      $ARRAYCALIBRE = $TCALIBRE_ADO->verCalibre($s['ID_TCALIBRE']);
      if ($ARRAYCALIBRE) {
          $NOMBRECALIBRE = $ARRAYCALIBRE[0]['NOMBRE_TCALIBRE'];
      } else {
          $NOMBRECALIBRE = "";
      }
      $ARRAYTMONEDA = $TMONEDA_ADO->verTmoneda($s['ID_TMONEDA']);
      if ($ARRAYTMONEDA) {
          $NOMBRETMONEDA = $ARRAYTMONEDA[0]['NOMBRE_TMONEDA'];
      } else {
          $NOMBRETMONEDA = "";
      }
      $ARRAYTMANEJO = $TMANEJO_ADO->verTmanejo($s['ID_TMANEJO']);
      if ($ARRAYTMANEJO) {
          $NOMBRETMANEJO = $ARRAYTMANEJO[0]['NOMBRE_TMANEJO'];
      } else {
          $NOMBRETMANEJO = "";
      }
      $ARRAYVERVESPECIESID = $VESPECIES_ADO->verVespecies($s['ID_VESPECIES']);
      if ($ARRAYVERVESPECIESID) {
          $NOMBREVARIEDAD = $ARRAYVERVESPECIESID[0]['NOMBRE_VESPECIES'];
      } else {
          $NOMBREVARIEDAD = "";
      }

      $html = $html . '  
      <tr>   
              <td class="center">'.$CODIGOESTANDAR.'</td>
              <td class="center">'.$NOMBREESTANTAR.'</td>
              <td class="center">'.$NOMBRETMANEJO.'</td>
              <td class="center">'.number_format($NETOESTANTAR, 2, ",", ".").'</td>
              <td class="center">'.number_format($BRUTOESTANTAR, 4, ",", ".").'</td>
              <td class="center">'.$s['ENVASE'].'</td>
              <td class="center">'.$s['NETO'].'</td>
              <td class="center">'.$s['BRUTO'].'</td>
              <td class="center">'.$NOMBRECALIBRE.'</td>
              <td class="center">'.$NOMBRETMONEDA.'</td>
              <td class="center">'.$NOMBREVARIEDAD.'</td>
              <td class="center">'.$s['US'].'</td>
              <td class="center">'.$s['TOTALUS'].'</td>
      </tr>
              
  ';
    endforeach; 

$html = $html . '      

        <tr>   
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <th class="color right">Sub total</td>
          <th class="color center">'.$TOTALENVASEV.'</th>
          <th class="color center">'.$TOTALNETOV.'</th>
          <th class="color center">'.$TOTALBRUTOV.'</th>
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <td class="color center">&nbsp;</td>
          <th class="color center">'.$TOTALUSV.'</th>
        </tr>
  </tbody>    
</table>    
';




$html = $html . '


  <div id="details" class="clearfix">      
        <div id="client">
          <div class="address"><b>Observations</b></div>
          <div class="address">  '.$OBSERVACIONES.' </div>
  </div>
    </div>
    </div>
    </div>
  
    </main>

  <!-- FIRMA INSPECTOR – anclada cerca del footer -->
<div style="position:fixed;bottom:10mm;right:12mm;display:table;">
  <div style="border-top:1px solid #555;padding-top:8px;padding-bottom:4px;font-size:9px;font-family:Arial,sans-serif;color:#333;white-space:nowrap;">
     ' . $NOMBRERESPONSABLE  . '
  </div>
</div>


  </body>
</html>

';





//CREACION NOMBRE DEL ARCHIVO
$NOMBREARCHIVO="ShippingInstruction_";
$FECHADOCUMENTO = $FECHANORMAL."_".$HORAFINAL;
$TIPODOCUMENTO="Report";
$FORMATO=".pdf";
$NOMBREARCHIVOFINAL=$NOMBREARCHIVO.$FECHADOCUMENTO.$FORMATO;

//CONFIGURACIOND DEL DOCUMENTO
$TIPOPAPEL="LETTER";
$ORIENTACION="P";
$LENGUAJE="ES";
$UNICODE="true";
$ENCODING="UTF-8";

//DETALLE DEL CREADOR DEL INFORME
$TIPOINFORME = "Shipping Instruction ";
$CREADOR = "User";
$AUTOR = "User";
$ASUNTO = "Instruction";

//API DE GENERACION DE PDF
require_once '../../api/mpdf/mpdf/autoload.php';
$PDF = new \Mpdf\Mpdf([
    'format'            => 'letter',
    'margin_top'        => 18,
    'margin_bottom'     => 10,
    'margin_left'       => 8,
    'margin_right'      => 8,
    'default_font_size' => 9,
]);

//CONFIGURACION FOOTER Y HEADER DEL PDF//CONFIGURACION FOOTER Y HEADER DEL PDF
$PDF->SetHTMLHeader('
<table width="100%" >
    <tbody>
        <tr>
          <th width="55%" class="left f10">' . $NOMBREEMPRESA . '</th>
          <td width="45%" class="right f10">' . $FECHANORMAL2 . '</td>
          <td width="5%"  class="right f10"><span>{PAGENO}/{nbpg}</span></td>
        </tr>
    </tbody>
</table>
<br>

');

$PDF->SetHTMLFooter('



<footer>
  Report generated by IT Department Frutícola Volcán<a href="mailto:ti@fvolcan.cl">ti@fvolcan.cl.</a>
  <br>
  Printed by: <b>' . $NOMBRE . '.</b> print time: <b>' . $HORAFINAL2 . '</b>
</footer>

');


$PDF->SetTitle($TIPOINFORME); //titulo pdf
$PDF->SetCreator($CREADOR); //CREADOR PDF
$PDF->SetAuthor($AUTOR); //AUTOR PDF
$PDF->SetSubject($ASUNTO); //ASUNTO PDF


//CONFIGURACION

//$PDF->simpleTables = true; 
//$PDF->packTableData = true;

//INICIALIZACION DEL CSS
$stylesheet = file_get_contents('../../assest/css/stylePdf.css'); // carga archivo css
$stylesheet2 = file_get_contents('../../assest/css/reset.css'); // carga archivo css

//ENLASAR CSS CON LA VISTA DEL PDF
$PDF->WriteHTML($stylesheet, 1);
$PDF->WriteHTML($stylesheet2, 1);
$stylesheetCompact = '
table { margin-bottom: 4px; }
header { padding: 4px 0; margin-bottom: 4px; }
table th { padding: 2px 4px; font-size: 9px !important; }
table td { padding: 2px 4px; font-size: 9px !important; }
table .color { font-size: 9px !important; }
table th.color2 { background: #eeeeee; text-align: left; white-space: nowrap; }
table td.color2 { background: #ffffff; text-align: left; }
table.t4 th.color2 { width: 22%; }
table.t4 td.color2 { width: 28%; }
table.t6 th.color2 { width: 11%; }
table.t6 td.color2 { width: 22%; }
.titulo { padding: 4px; }
';
$PDF->WriteHTML($stylesheetCompact, 1);

//GENERAR PDF
$PDF->WriteHTML($html);
//METODO DE SALIDA
$PDF->Output($NOMBREARCHIVOFINAL, \Mpdf\Output\Destination::INLINE);
