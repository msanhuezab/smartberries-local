/assest/documento/informeIcargaInvoiceEU.php<?php

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
include_once '../../assest/controlador/TMANEJO_ADO.php';
include_once '../../assest/controlador/TMONEDA_ADO.php';
include_once '../../assest/controlador/ECOMERCIAL_ADO.php';
 
include_once '../../assest/controlador/PAIS_ADO.php';
include_once '../../assest/controlador/REGION_ADO.php';
include_once '../../assest/controlador/PROVINCIA_ADO.php';
include_once '../../assest/controlador/COMUNA_ADO.php';

include_once '../../assest/controlador/PRODUCTOR_ADO.php';
include_once '../../assest/controlador/DESPACHOEX_ADO.php';
include_once '../../assest/controlador/EXIEXPORTACION_ADO.php';



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
$TMANEJO_ADO = new TMANEJO_ADO();
$PAIS_ADO =  new PAIS_ADO();
$TCALIBRE_ADO = new TCALIBRE_ADO();
$TMONEDA_ADO = new TMONEDA_ADO();
$ECOMERCIAL_ADO = new ECOMERCIAL_ADO();


$PRODUCTOR_ADO = new PRODUCTOR_ADO();
$DESPACHOEX_ADO = new DESPACHOEX_ADO();
$EXIEXPORTACION_ADO = new EXIEXPORTACION_ADO();

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
$FECHAETAREAL = "";
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
$COC = "";
$PLANTA = "";
$TEMPORADA = "";
$TOTALPRECIOUSNUEVO=0;


$TOTALENVASEV = 0;
$TOTALNETOV = 0;
$TOTALBRUTOV = 0;
$TOTALUS = 0;
$TOTALUSV = 0;
$LUGARDECARGA = "";
$FDADESPACHOEX = "";
$FECHADESPACHOEX = "";
$NUMEROCONTENEDOR = "";
$NUMEROSELLO = "";

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
$ARRAYDCARGAAGRUPADO = [];
$ARRAYPRECIOSTMONEDA = [];
$ARRAYCALIBRE = "";
$ARRAYNUMERO = "";
$ARRAYVERNOTADCNC="";
$ARRAYCOMUNA="";
$ARRYAPROVINCIA="";
$ARRYAREGION="";
$ARRAYGROSSKILO = [];
$ARRAYNETKILO = [];
$ARRAYENVASEAGRUPADO = [];


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
      
    $ARRAYDCARGA1 = $DICARGA_ADO->buscarInvoicePorIcargaPorCalibre($IDOP);
    $ARRAYDCARGA2 = $DICARGA_ADO->buscarInvoiceIntPorIcargaPorCalibre($IDOP);
    if($ARRAYDCARGA1){
      $ARRAYDCARGA = $DICARGA_ADO->buscarInvoicePorIcargaPorCalibre($IDOP);
    }else if($ARRAYDCARGA2){
      $ARRAYDCARGA = $DICARGA_ADO->buscarInvoiceIntPorIcargaPorCalibre($IDOP);
    }
    




    $IDUSUARIOI = $ARRAYICARGA[0]['ID_USUARIOI'];  
    $ARRAYUSUARIO2 = $USUARIO_ADO->ObtenerNombreCompleto($IDUSUARIOI);
    $NOMBRERESPONSABLE = $ARRAYUSUARIO2[0]["NOMBRE_COMPLETO"];
    
    
    $NUMEROCONTENEDOR = "Sin Datos";
    $ARRAYDESPACHOEX=$DESPACHOEX_ADO->buscarDespachoExPorIcarga($IDOP);
    $ARRAYDESPACHOEX2=$DESPACHOEX_ADO->buscarDespachoExPorIcargaAgrupadoPorPlanta($IDOP);
    if($ARRAYDESPACHOEX){
      $CONTENEDORESDESPACHO = [];
      foreach ($ARRAYDESPACHOEX as $DESPACHOEX) {
        if (
          $DESPACHOEX["ESTADO_REGISTRO"] == 1
          && !empty($DESPACHOEX["NUMERO_CONTENEDOR_DESPACHOEX"])
        ) {
          $CONTENEDORESDESPACHO[] = $DESPACHOEX["NUMERO_CONTENEDOR_DESPACHOEX"];
        }
      }
      if ($CONTENEDORESDESPACHO) {
        $CONTENEDORESDESPACHO = array_values(array_unique($CONTENEDORESDESPACHO));
        $NUMEROCONTENEDOR = $CONTENEDORESDESPACHO[0];
      }

      foreach ($ARRAYDESPACHOEX2 as $r) :  
        $ARRAYVERPLANTA = $PLANTA_ADO->verPlanta($r['ID_PLANTA']);
        if($ARRAYVERPLANTA){
          $LUGARDECARGA= $LUGARDECARGA.$ARRAYVERPLANTA[0]["RAZON_SOCIAL_PLANTA"]."  ";
          $FDADESPACHOEX= $FDADESPACHOEX.$ARRAYVERPLANTA[0]["FDA_PLANTA"]."  ";
        }else{
          $FECHADESPACHOEX=$FECHADESPACHOEX;
          $LUGARDECARGA=$LUGARDECARGA;
        }
      endforeach;     

    }else{
      $FDADESPACHOEX="Sin Datos";
      $NUMEROSELLO="Sin Datos";
      $FECHADESPACHOEX="Sin Datos";
      $LUGARDECARGA="Sin Datos";
    }

    if($ARRAYDESPACHOEX){
    foreach ($ARRAYDESPACHOEX as $despacho) :
        $ARRAYTOMADO = $EXIEXPORTACION_ADO->buscarPordespachoEx($despacho['ID_DESPACHOEX']);
        foreach ($ARRAYTOMADO as $r) :
          $NOMBREECOMERCIAL = "Sin Datos";
          $ARRAYEEXPORTACION = $EEXPORTACION_ADO->verEstandar($r['ID_ESTANDAR']);
          if($ARRAYEEXPORTACION){
            $ARRAYECOMERCIAL = $ECOMERCIAL_ADO->verEcomercial($ARRAYEEXPORTACION[0]['ID_ECOMERCIAL']);
            if($ARRAYECOMERCIAL){
              $NOMBREECOMERCIAL = $ARRAYECOMERCIAL[0]['NOMBRE_ECOMERCIAL'];
            }
          }
          $NOMBRETMANEJO = "Sin Datos";
          if(isset($r['ID_TMANEJO'])){
            $ARRAYTMANEJO = $TMANEJO_ADO->verTmanejo($r['ID_TMANEJO']);
            if($ARRAYTMANEJO){
              $NOMBRETMANEJO = $ARRAYTMANEJO[0]['NOMBRE_TMANEJO'];
            }
          }
          $NOMBRETCALIBRE = "Sin Datos";
          if(isset($r['ID_TCALIBRE'])){
            $ARRAYTCALIBRE = $TCALIBRE_ADO->verCalibre($r['ID_TCALIBRE']);
            if($ARRAYTCALIBRE){
              $NOMBRETCALIBRE = $ARRAYTCALIBRE[0]['NOMBRE_TCALIBRE'];
            }
          }
          $KEYDETALLE = $NOMBREECOMERCIAL.'|'.$NOMBRETMANEJO.'|'.$NOMBRETCALIBRE;
          if(!isset($ARRAYGROSSKILO[$KEYDETALLE])){
            $ARRAYGROSSKILO[$KEYDETALLE] = 0;
          }
          if(!isset($ARRAYNETKILO[$KEYDETALLE])){
            $ARRAYNETKILO[$KEYDETALLE] = 0;
          }
          if(!isset($ARRAYENVASEAGRUPADO[$KEYDETALLE])){
            $ARRAYENVASEAGRUPADO[$KEYDETALLE] = 0;
          }
          $ARRAYGROSSKILO[$KEYDETALLE] = $ARRAYGROSSKILO[$KEYDETALLE] + $r['BRUTO'];
          $ARRAYNETKILO[$KEYDETALLE] = $ARRAYNETKILO[$KEYDETALLE] + $r['NETO'];
          $ARRAYENVASEAGRUPADO[$KEYDETALLE] = $ARRAYENVASEAGRUPADO[$KEYDETALLE] + $r['ENVASE'];
        endforeach;
      endforeach;
    }

    // Preload price and currency per instructive detail to recover missing values
    $ARRAYDICARGADETALLE = $DICARGA_ADO->buscarPorIcarga($IDOP);
    if($ARRAYDICARGADETALLE){
      $CACHEESTANDAR = [];
      $CACHEECOMERCIAL = [];
      $CACHETMANEJO = [];
      $CACHETCALIBRE = [];
      $CACHETMONEDA = [];
      $CACHETMONEDANOMBRE = [];
      foreach($ARRAYDICARGADETALLE as $d){
        $NOMBRECOMERCIALDET = '';
        $NOMBRETMANEODET = '';
        $NOMBRETCALIBREDET = '';
        if(isset($d['ID_ESTANDAR'])){
          if(!isset($CACHEESTANDAR[$d['ID_ESTANDAR']])){
            $CACHEESTANDAR[$d['ID_ESTANDAR']] = $EEXPORTACION_ADO->verEstandar($d['ID_ESTANDAR']);
          }
          $ARRAYESTANDARDET = $CACHEESTANDAR[$d['ID_ESTANDAR']];
          if($ARRAYESTANDARDET){
            $IDECOMERCIALDET = $ARRAYESTANDARDET[0]['ID_ECOMERCIAL'];
            if(!isset($CACHEECOMERCIAL[$IDECOMERCIALDET])){
              $CACHEECOMERCIAL[$IDECOMERCIALDET] = $ECOMERCIAL_ADO->verEcomercial($IDECOMERCIALDET);
            }
            $ARRAYECOMERCIALDET = $CACHEECOMERCIAL[$IDECOMERCIALDET];
            if($ARRAYECOMERCIALDET){
              $NOMBRECOMERCIALDET = $ARRAYECOMERCIALDET[0]['NOMBRE_ECOMERCIAL'];
            }
          }
        }
        if(isset($d['ID_TMANEJO'])){
          if(!isset($CACHETMANEJO[$d['ID_TMANEJO']])){
            $CACHETMANEJO[$d['ID_TMANEJO']] = $TMANEJO_ADO->verTmanejo($d['ID_TMANEJO']);
          }
          $ARRAYTMANEJODET = $CACHETMANEJO[$d['ID_TMANEJO']];
          if($ARRAYTMANEJODET){
            $NOMBRETMANEODET = $ARRAYTMANEJODET[0]['NOMBRE_TMANEJO'];
          }
        }
        if(isset($d['ID_TCALIBRE'])){
          if(!isset($CACHETCALIBRE[$d['ID_TCALIBRE']])){
            $CACHETCALIBRE[$d['ID_TCALIBRE']] = $TCALIBRE_ADO->verCalibre($d['ID_TCALIBRE']);
          }
          $ARRAYTCALIBREDET = $CACHETCALIBRE[$d['ID_TCALIBRE']];
          if($ARRAYTCALIBREDET){
            $NOMBRETCALIBREDET = $ARRAYTCALIBREDET[0]['NOMBRE_TCALIBRE'];
          }
        }
        $KEYPRECIO = $NOMBRECOMERCIALDET.'|'.$NOMBRETMANEODET.'|'.$NOMBRETCALIBREDET;
        if(!isset($ARRAYPRECIOSTMONEDA[$KEYPRECIO])){
          $ARRAYPRECIOSTMONEDA[$KEYPRECIO] = [
            'TMONEDA' => '',
            'US' => '',
          ];
        }
        if($ARRAYPRECIOSTMONEDA[$KEYPRECIO]['TMONEDA'] === ''){
          if(isset($d['ID_TMONEDA'])){
            if(!isset($CACHETMONEDA[$d['ID_TMONEDA']])){
              $CACHETMONEDA[$d['ID_TMONEDA']] = $TMONEDA_ADO->verTmoneda($d['ID_TMONEDA']);
            }
            $ARRAYTMONEDADET = $CACHETMONEDA[$d['ID_TMONEDA']];
            if($ARRAYTMONEDADET){
              $ARRAYPRECIOSTMONEDA[$KEYPRECIO]['TMONEDA'] = $ARRAYTMONEDADET[0]['NOMBRE_TMONEDA'];
            }
          }
        }
        if($ARRAYPRECIOSTMONEDA[$KEYPRECIO]['US'] === '' && isset($d['PRECIO_US_DICARGA'])){
          $ARRAYPRECIOSTMONEDA[$KEYPRECIO]['US'] = $d['PRECIO_US_DICARGA'];
        }
      }
    }

    if($ARRAYDCARGA){
    foreach ($ARRAYDCARGA as $s) {
      $TMONEDANOMBRE = $s['TMONEDA'];
      if($TMONEDANOMBRE !== '' && is_numeric($TMONEDANOMBRE)){
        if(!isset($CACHETMONEDANOMBRE[$TMONEDANOMBRE])){
          $CACHETMONEDANOMBRE[$TMONEDANOMBRE] = $TMONEDA_ADO->verTmoneda($TMONEDANOMBRE);
        }
        $ARRAYTMONEDANOMBRE = $CACHETMONEDANOMBRE[$TMONEDANOMBRE];
        if($ARRAYTMONEDANOMBRE){
          $TMONEDANOMBRE = $ARRAYTMONEDANOMBRE[0]['NOMBRE_TMONEDA'];
        }
      }

      $KEYDETALLE = $s['NOMBRE'].'|'.$s['TMANEJO'].'|'.$s['TCALIBRE'];
      if(!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE])){
        $ARRAYDCARGAAGRUPADO[$KEYDETALLE] = [
          'NOMBRE' => $s['NOMBRE'],
          'TMANEJO' => $s['TMANEJO'],
          'TCALIBRE' => $s['TCALIBRE'],
          'TMONEDA' => $TMONEDANOMBRE,
          'US' => $s['US'],
          'ENVASESF' => 0,
          'NETOSF' => 0,
          'BRUTOSF' => 0,
          'TOTALUSSF' => 0,
        ];
      }
      if((!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA'] === '') && $TMONEDANOMBRE !== ''){
        $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA'] = $TMONEDANOMBRE;
      }
      if((!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US'] === '') && ($s['US'] !== '' || isset($s['USSF']))){
        $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US'] = $s['US'] !== '' ? $s['US'] : ($s['USSF'] ?? '');
      }
        if(
          (!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA'] === '') ||
          (!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US'] === '')
        ) {
          if(isset($ARRAYPRECIOSTMONEDA[$KEYDETALLE])){
            if((!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA'] === '') && $ARRAYPRECIOSTMONEDA[$KEYDETALLE]['TMONEDA'] !== ''){
              $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TMONEDA'] = $ARRAYPRECIOSTMONEDA[$KEYDETALLE]['TMONEDA'];
            }
            if((!isset($ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US']) || $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US'] === '') && $ARRAYPRECIOSTMONEDA[$KEYDETALLE]['US'] !== ''){
              $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['US'] = $ARRAYPRECIOSTMONEDA[$KEYDETALLE]['US'];
            }
          }
        }
      $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['ENVASESF'] += $s['ENVASESF'];
      $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['NETOSF'] += $s['NETOSF'];
      $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['BRUTOSF'] += $s['BRUTOSF'];
      $ARRAYDCARGAAGRUPADO[$KEYDETALLE]['TOTALUSSF'] += $s['TOTALUSSF'];
    }
    }
    
      
      $NUMEROICARGA=$ARRAYICARGA[0]["NUMERO_ICARGA"];
      $NUMEROIREFERENCIA=$ARRAYICARGA[0]["NREFERENCIA_ICARGA"];
      $FECHA=$ARRAYICARGA[0]["FECHA"];
      $BOOKINGINSTRUCTIVO = $ARRAYICARGA[0]['BOOKING_ICARGA'];
      $TEMBARQUE = $ARRAYICARGA[0]['TEMBARQUE_ICARGA'];
      $FECHAETD = $ARRAYICARGA[0]['FECHAETD'];
      $FECHAETA = $ARRAYICARGA[0]['FECHAETA'];
      $FECHAETDREAL = $ARRAYICARGA[0]['FECHAETDREAL'];
      if(!$FECHAETDREAL){
        $FECHAETDREAL = "Sin Datos";
      }
      $FECHAETAREAL = $ARRAYICARGA[0]['FECHAETAREAL'];
      if(!$FECHAETAREAL){
        $FECHAETAREAL = "Sin Datos";
      }
        $BOLAWBCRTINSTRUCTIVO = $ARRAYICARGA[0]['CRT_ICARGA'];
        if(!$BOLAWBCRTINSTRUCTIVO){
          $BOLAWBCRTINSTRUCTIVO = "Sin Datos";
        }
      if(!$BOLAWBCRTINSTRUCTIVO){
        $BOLAWBCRTINSTRUCTIVO = "Sin Datos";
      }


      $TINSTRUCTIVO = $ARRAYICARGA[0]['T_ICARGA'];
      $O2INSTRUCTIVO = $ARRAYICARGA[0]['O2_ICARGA'];
      $CO2INSTRUCTIVO = $ARRAYICARGA[0]['C02_ICARGA'];
      $ALAMPAINSTRUCTIVO = $ARRAYICARGA[0]['ALAMPA_ICARGA'];

      $OBSERVACIONES = $ARRAYICARGA[0]['OBSERVACION_ICARGA'];
      $OBSERVACIONESI = $ARRAYICARGA[0]['OBSERVACIONI_ICARGA'];
      $COSTOFLETEICARGA = $ARRAYICARGA[0]['COSTO_FLETE_ICARGA'];
      if($ARRAYICARGA[0]['FUMIGADO_ICARGA']==1){
        $FUMIGADO="Si";
      }else if($ARRAYICARGA[0]['FUMIGADO_ICARGA']==2){
        $FUMIGADO="No";
      }else{
        $FUMIGADO="Sin Datos";
      }

      $ESTADO = $ARRAYICARGA[0]['ESTADO'];
      if ($ARRAYICARGA[0]['ESTADO'] == 1) {
        $ESTADO = "Abierto";
      }else if ($ARRAYICARGA[0]['ESTADO'] == 0) {
        $ESTADO = "Cerrado";
      }else{
        $ESTADO="Sin Datos";
      }  
      $ARRAYRFINAL=$RFINAL_ADO->verRfinal($ARRAYICARGA[0]["ID_RFINAL"]);
      if($ARRAYRFINAL){
          $NOMBRERFINAL=$ARRAYRFINAL[0]["NOMBRE_RFINAL"];
      }else{
          $NOMBRERFINAL="Sin Datos";
      }
      $ARRAYNOTIFICADOR=$NOTIFICADOR_ADO->verNotificador($ARRAYICARGA[0]["ID_NOTIFICADOR"]);
      if($ARRAYNOTIFICADOR){
        $NOMBRENOTIFICADOR=$ARRAYNOTIFICADOR[0]["NOMBRE_NOTIFICADOR"];
        $DIRECCIONNOTIFICADOR=$ARRAYNOTIFICADOR[0]["DIRECCION_NOTIFICADOR"];
        $EORINOTIFICADOR=$ARRAYNOTIFICADOR[0]["EORI_NOTIFICADOR"];
        $TELEFONONOTIFICADOR=$ARRAYNOTIFICADOR[0]["TELEFONO_NOTIFICADOR"];
        $EMAIL1NOTIFICADOR=$ARRAYNOTIFICADOR[0]["EMAIL1_NOTIFICADOR"];
      }else{
        $NOMBRENOTIFICADOR="Sin Datos";
        $EORINOTIFICADOR="Sin Datos";
        $TELEFONONOTIFICADOR="Sin Datos";
        $DIRECCIONNOTIFICADOR="Sin Datos";
        $EMAIL1NOTIFICADOR="Sin Datos";
      }
      $ARRAYCONSIGNATARIO = $CONSIGNATARIO_ADO->verConsignatorio($ARRAYICARGA[0]['ID_CONSIGNATARIO']);            
      if($ARRAYCONSIGNATARIO){
        $NOMBRECONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["NOMBRE_CONSIGNATARIO"];
        $DIRECCIONCONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["DIRECCION_CONSIGNATARIO"];
        $EORICONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["EORI_CONSIGNATARIO"];
        $TELEFONOCONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["TELEFONO_CONSIGNATARIO"];
        $EMAIL1CONSIGNATARIO=$ARRAYCONSIGNATARIO[0]["EMAIL1_CONSIGNATARIO"];
      }else{
        $NOMBRECONSIGNATARIO="Sin Datos";
        $EORICONSIGNATARIO="Sin Datos";
        $TELEFONOCONSIGNATARIO="Sin Datos";
        $DIRECCIONCONSIGNATARIO="Sin Datos";
        $EMAIL1CONSIGNATARIO="Sin Datos";
      }
      $ARRAYFPAGO = $FPAGO_ADO->verFpago(  $ARRAYICARGA[0]['ID_FPAGO']);         
      if($ARRAYFPAGO){
        $NOMBREFPAGO=$ARRAYFPAGO[0]["NOMBRE_FPAGO"];
      }else{
        $NOMBREFPAGO="Sin Datos";
      }
      $ARRAYMVENTA = $MVENTA_ADO->verMventa( $ARRAYICARGA[0]['ID_MVENTA']);        
      if($ARRAYMVENTA){
        $NOMBREMVENTA=$ARRAYMVENTA[0]["NOMBRE_MVENTA"];
      }else{
        $NOMBREMVENTA="Sin Datos";
      }
      $ARRAYCVENTA = $CVENTA_ADO->verCventa( $ARRAYICARGA[0]['ID_CVENTA']);        
      if($ARRAYMVENTA){
        $NOMBRECVENTA=$ARRAYCVENTA[0]["NOMBRE_CVENTA"];
      }else{
        $NOMBRECVENTA="Sin Datos";
      }
      $ARRAYTFLETE= $TFLETE_ADO->verTflete( $ARRAYICARGA[0]['ID_TFLETE']);        
      if($ARRAYTFLETE){
        $NOMBRETFLETE=$ARRAYTFLETE[0]["NOMBRE_TFLETE"];
      }else{
        $NOMBRETFLETE="Sin Datos";
      }      
      $ARRAYATMOSFERA =$ATMOSFERA_ADO->verAtmosfera( $ARRAYICARGA[0]['ID_ATMOSFERA']);
      if($ARRAYATMOSFERA){
        $NOMBREATMOSFERA=$ARRAYATMOSFERA[0]["NOMBRE_ATMOSFERA"];
      }else{
        $NOMBREATMOSFERA="Sin Datos";
      }
      $ARRAYTCONTENEDOR =$TCONTENEDOR_ADO->verTcontenedor( $ARRAYICARGA[0]['ID_TCONTENEDOR']);
      if($ARRAYTCONTENEDOR){
        $NOMBRETCONTENEDOR=$ARRAYTCONTENEDOR[0]["NOMBRE_TCONTENEDOR"];
      }else{
        $NOMBRETCONTENEDOR="Sin Datos";
      }      
      $ARRAYPAIS =$PAIS_ADO->verPais( $ARRAYICARGA[0]['ID_PAIS']);
      if($ARRAYPAIS){
        $NOMBREPAIS=$ARRAYPAIS[0]["NOMBRE_PAIS"];
      }else{
        $NOMBREPAIS="Sin Datos";
      }
      $ARRAYEXPORTADORA = $EXPORTADORA_ADO->verExportadora( $ARRAYICARGA[0]['ID_EXPPORTADORA']);
      if($ARRAYEXPORTADORA){
        $RUTEXPPORTADORA=$ARRAYEXPORTADORA[0]["RUT_EXPORTADORA"]."-".$ARRAYEXPORTADORA[0]["DV_EXPORTADORA"];
        $NOMBREEXPPORTADORA=$ARRAYEXPORTADORA[0]["NOMBRE_EXPORTADORA"];
      }else{
        $RUTEXPPORTADORA="Sin Datos";
        $NOMBREEXPPORTADORA="Sin Datos";
      }
      $ARRAYDFINAL =$DFINAL_ADO->verDfinal( $ARRAYICARGA[0]['ID_DFINAL']);
      if($ARRAYDFINAL){
        $NOMBREDFINAL=$ARRAYDFINAL[0]["NOMBRE_DFINAL"];
      }else{
        $NOMBREDFINAL="Sin Datos";
      }

    if($TEMBARQUE){
          if ($TEMBARQUE == "1") {
              $NOMBRETEMBARQUE="Terrestre";
              $CRT=$ARRAYICARGA[0]['CRT_ICARGA'];
              $ARRAYTRANSPORTE =$TRANSPORTE_ADO->verTransporte( $ARRAYICARGA[0]['ID_TRANSPORTE']);        
              if($ARRAYTRANSPORTE){
                $NOMBRETRANSPORTE=$ARRAYTRANSPORTE[0]["NOMBRE_TRANSPORTE"];
              }else{
                $NOMBRETRANSPORTE="Sin Datos";
              }            
              $ARRAYLCARGA =$LCARGA_ADO->verLcarga(  $ARRAYICARGA[0]['ID_LCARGA']);       
              if($ARRAYLCARGA){
                $NOMBREORIGEN=$ARRAYLCARGA[0]["NOMBRE_LCARGA"];
              }else{
                $NOMBREORIGEN="Sin Datos";
              }
              $ARRAYLDESTINO =$LDESTINO_ADO->verLdestino( $ARRAYICARGA[0]['ID_LDESTINO']);     
              if($ARRAYLDESTINO){
                $NOMBREDESTINO=$ARRAYLDESTINO[0]["NOMBRE_LDESTINO"];
              }else{
                $NOMBREDESTINO="Sin Datos";
              }
          }
          if ($TEMBARQUE == "2") {
              $NOMBRETEMBARQUE="Aereo";
              $NAVE=$ARRAYICARGA[0]['NAVE_ICARGA'];
              $NVIAJE = $ARRAYICARGA[0]['NVIAJE_ICARGA'];
             
              $ARRAYLAEREA = $LAEREA_ADO->verLaerea( $ARRAYICARGA[0]['ID_LAREA']);      
              if($ARRAYLAEREA){
                $NOMBRETRANSPORTE=$ARRAYLAEREA[0]["NOMBRE_LAEREA"];
              }else{
                $NOMBRETRANSPORTE="Sin Datos";
              }            
              $ARRAYACARGA =$ACARGA_ADO->verAcarga(  $ARRAYICARGA[0]['ID_ACARGA']);  
              if($ARRAYACARGA){
                $NOMBREORIGEN=$ARRAYACARGA[0]["NOMBRE_ACARGA"];
              }else{
                $NOMBREORIGEN="Sin Datos";
              }
              $ARRAYADESTINO =$ADESTINO_ADO->verAdestino( $ARRAYICARGA[0]['ID_ADESTINO']);  
              if($ARRAYADESTINO){
                $NOMBREDESTINO=$ARRAYADESTINO[0]["NOMBRE_ADESTINO"];
              }else{
                $NOMBREDESTINO="Sin Datos";
              }
          }
          if ($TEMBARQUE == "3") {
              $NOMBRETEMBARQUE="Maritimo";
              $NAVE  = $ARRAYICARGA[0]['NAVE_ICARGA'];
              $NVIAJE = $ARRAYICARGA[0]['NVIAJE_ICARGA'];
              $FECHASTACKING = $ARRAYICARGA[0]['FECHAESTACKING'];
              $ARRAYNAVIERA =$NAVIERA_ADO->verNaviera( $ARRAYICARGA[0]['ID_NAVIERA']);   
              if($ARRAYNAVIERA){
                $NOMBRETRANSPORTE=$ARRAYNAVIERA[0]["NOMBRE_NAVIERA"];
              }else{
                $NOMBRETRANSPORTE="Sin Datos";
              }            
              $ARRAYPCARGA =$PCARGA_ADO->verPcarga(  $ARRAYICARGA[0]['ID_PCARGA']);
              if($ARRAYPCARGA){
                $NOMBREORIGEN=$ARRAYPCARGA[0]["NOMBRE_PCARGA"];
              }else{
                $NOMBREORIGEN="Sin Datos";
              }
              $ARRAYPDESTINO =$PDESTINO_ADO->verPdestino( $ARRAYICARGA[0]['ID_PDESTINO']);
              if($ARRAYPDESTINO){
                $NOMBREDESTINO=$ARRAYPDESTINO[0]["NOMBRE_PDESTINO"];
              }else{
                $NOMBREDESTINO="Sin Datos";
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
      $RUTAGCARGA="Sin Datos";
      $NOMBREAGCARGA="Sin Datos";
      $DIRECCIONAGCARGA="Sin Datos";
      $CONTACTOAGCARGA="Sin Datos";
      $EMAILAGCARGA="Sin Datos";
      $TELEFONOAGCARGA="Sin Datos";
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
      $RUTAADUANA="Sin Datos";
      $NOMBREAADUANA="Sin Datos";
      $DIRECCIONAADUANA="Sin Datos";
      $CONTACTOAADUANA="Sin Datos";
      $EMAILAADUANA="Sin Datos";
      $TELEFONOAADUANA="Sin Datos";
    }



  $ARRAYEMPRESA = $EMPRESA_ADO->verEmpresa($ARRAYICARGA[0]['ID_EMPRESA']);
  if($ARRAYEMPRESA){
    $NOMBREEMPRESA=$ARRAYEMPRESA[0]["NOMBRE_EMPRESA"];
    $COC=$ARRAYEMPRESA[0]["COC"];
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
    $NOMBREEMPRESA="Sin Datos";
    $RAZONSOCIALEMPRESA="Sin Datos";
    $RUTEMPRESA="Sin Datos";
    $DIRECCIONEMPRESA="Sin Datos";
  }
  $ARRAYTEMPORADA = $TEMPORADA_ADO->verTemporada($ARRAYICARGA[0]['ID_TEMPORADA']);  
  if($ARRAYTEMPORADA){
    $NOMBRETEMPORADA=$ARRAYTEMPORADA[0]["NOMBRE_TEMPORADA"];
  }else{
    $NOMBRETEMPORADA="Sin Datos";
  }

} 






//OBTENCION DE LA FECHA
date_default_timezone_set('America/Santiago');
//SE LE PASA LA FECHA ACTUAL A UN ARREGLO
$ARRAYFECHADOCUMENTO = getdate();

//SE OBTIENE INFORMACION RELACIONADA CON LA HORA
$HORA = "" . $ARRAYFECHADOCUMENTO['hours'];
$MINUTO = "" . $ARRAYFECHADOCUMENTO['minutes'];
$SEGUNDO = "" . $ARRAYFECHADOCUMENTO['seconds'];
//EN CASO DE VALORES MENOS A 2 LENGHT, SE LE CONCATENA UN 0
if ($MINUTO < 10) {
  $MINUTO = "0" . $MINUTO;
}
if ($SEGUNDO < 10) {
  $SEGUNDO = "0" . $SEGUNDO;
}

// SE JUNTA LA INFORMAICON DE LA HORA Y SE LE DA UN FORMATO
$HORAFINAL = $HORA . "" . $MINUTO . "" . $SEGUNDO;
$HORAFINAL2 = $HORA . ":" . $MINUTO . ":" . $SEGUNDO;

//SE OBTIENE INFORMACION RELACIONADA CON LA FECHA
$DIA = "" . $ARRAYFECHADOCUMENTO['mday'];

$MES = "" . $ARRAYFECHADOCUMENTO['mon'];
$ANO = "" . $ARRAYFECHADOCUMENTO['year'];
$NOMBREMES = "" . $ARRAYFECHADOCUMENTO['month'];
$NOMBREDIA = "" . $ARRAYFECHADOCUMENTO['weekday'];
//EN CASO DE VALORES MENOS A 2 LENGHT, SE LE CONCATENA UN 0
if ($DIA < 10) {
  $DIA = "0" . $DIA;
}
//PARA TRAUDCIR EL MES AL ESPAÑOL
$MESESNOMBRES = array(
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
$DIASNOMBRES = array(
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
$FECHANORMAL = $DIA . "" . $MES . "" . $ANO;
$FECHANORMAL2 = $DIA . "/" . $MES . "/" . $ANO;
$FECHANOMBRE = $NOMBREDIA . ", " . $DIA . " de " . $NOMBREMES . " del " . $ANO;



$html = '
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <title>Invoice</title>
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
          '.$COC.' <br>
          '.$DIRECCIONEMPRESA.' <br>          
        </td>
        <td class="color2 right">
        
        </td>
      </tr>
    </table>    
    </header>
    <main>    
    <div class="titulo bcolor" >
      <div class="f20 titulo"  style="text-align: left; font-weight: bold;">  INVOICE  </div>    
      <div class="f15 titulo"  style="text-align: right;">  <b>  Reference Number: ' . $NUMEROIREFERENCIA . '   </b>  </div>      
    </div>  
    <br>
<div id="details" class="clearfix">
  <div id="client">
    <div class="address"> <b>  Consignee:  </b> '.$NOMBRECONSIGNATARIO.'  </div>
    <div class="address"> <b>  Address Consignee:  </b> '.$DIRECCIONCONSIGNATARIO.'  </div>
    <div class="address"> <b> Tributary id Consignee: </b>'.$EORICONSIGNATARIO.'  </div>
    <div class="address"> <b> Phone / Fax Consignee: </b>'.$TELEFONOCONSIGNATARIO.'  </div>
    <div class="address"> <b>  Email Consignee:  </b> '.$EMAIL1CONSIGNATARIO.'  </div>
    <div class="address">&nbsp;  </div>

    ';
    if ($TEMBARQUE == "1") {
      $html = $html . '
        <div class="address"> <b>  Container number:  </b> '.$NUMEROCONTENEDOR.'  </div>
        <div class="address"> <b>  FDA Packing:  </b> '.$FDADESPACHOEX.'  </div>
        <div class="address"> <b>  Date ETD :   </b>  '.$FECHAETDREAL.'</div>
        <div class="address"> <b>  Date ETA:  </b>  '.$FECHAETAREAL.' </div>
      ';
    }
    if ($TEMBARQUE == "2") {
        $html = $html . '

        <div class="address"> <b>  Container number:  </b> '.$NUMEROCONTENEDOR.'  </div>
        <div class="address"> <b>  FDA Packing:  </b> '.$FDADESPACHOEX.'  </div>
        <div class="address"> <b>  Date ETD :   </b>  '.$FECHAETDREAL.'</div>
        <div class="address"> <b>  Date ETA:  </b>  '.$FECHAETAREAL.' </div>

        ';
     }
    if ($TEMBARQUE == "3") {
        $html = $html . '

        <div class="address"> <b>  Container number:  </b> '.$NUMEROCONTENEDOR.'  </div>
        <div class="address"> <b>  FDA Packing:  </b> '.$FDADESPACHOEX.'  </div>
        <div class="address"> <b>  Date ETD :   </b>  '.$FECHAETDREAL.'</div>
        <div class="address"> <b>  Date ETA:  </b>  '.$FECHAETAREAL.' </div>

        ';
    }

$html = $html . '



  </div>
  <div id="client"> 
  
    <div class="address"> <b> Date Inovice:  </b> '.$FECHAETD.'  </div>
    <div class="address"> <b>  Sales method:  </b>  '.$NOMBREMVENTA.' </div>
    <div class="address"> <b>  Incoterm:  </b>   '.$NOMBRECVENTA.'</div>
    <div class="address"> <b>  BL/AWB/CRT:  </b> '.$BOLAWBCRTINSTRUCTIVO.'  </div>

    ';
    if ($TEMBARQUE == "1") {
      $html = $html . '
        <div class="address">&nbsp;  </div>
        <div class="address">&nbsp;  </div>
        <div class="address"> <b>  Transport Name:  </b> '.$NOMBRETRANSPORTE.'  </div>
        <div class="address"> <b>  CRT:  </b> '.$CRT.'  </div>
        <div class="address"> <b>  Place of Shipment:   </b>'.$NOMBREORIGEN.'  </div>
        <div class="address"> <b>  Place of Destination:  </b> '.$NOMBREDESTINO.'  </div>
        <div class="address"> <b>  Loading place:   </b> '.$LUGARDECARGA.'  </div>
      ';
    }
    if ($TEMBARQUE == "2") {
        $html = $html . '
    
        <div class="address">&nbsp;  </div>
        <div class="address">&nbsp;  </div>
        <div class="address"> <b>  Airline Name:   </b>'.$NOMBRETRANSPORTE.'  </div>
        <div class="address"> <b>  Airplane:   </b>'.$NAVE.' <b>Number Travel: </b>'.$NVIAJE.'  </div>
        <div class="address"> <b>  Airport of Shipment:  </b> '.$NOMBREORIGEN.'  </div>
        <div class="address"> <b>  Airport of Destination:   </b>'.$NOMBREDESTINO.'  </div>
        <div class="address"> <b>  Loading place:   </b>'.$LUGARDECARGA.'  </div>
    
        ';
     }
    if ($TEMBARQUE == "3") {
        $html = $html . '
    
        <div class="address">&nbsp;  </div>
        <div class="address">&nbsp;  </div>
        <div class="address"> <b>  Shipping company name:  </b> '.$NOMBRETRANSPORTE.'  </div>
        <div class="address"> <b>  Vessel:   </b>'.$NAVE.' - <b>Number Travel: </b>'.$NVIAJE.' </div>
        <div class="address"> <b>  Port of Shipment:   </b>'.$NOMBREORIGEN.'  </div>
        <div class="address"> <b>  Port of Destination:  </b> '.$NOMBREDESTINO.'  </div>
        <div class="address"> <b>  Loading place:   </b>'.$LUGARDECARGA.'  </div>
    
        ';
    }    

$html = $html . '


        </div>          
      </div>
        ';

        $html = $html . '        
        <table border="0" cellspacing="0" cellpadding="0">
          <thead>
            <tr>
              <th colspan="9" class="center">DETAIL.</th>
            </tr>
            <tr>
              <th class="color center ">Quantity Boxes</th>
              <th class="color center ">Description of goods </th>
              <th class="color center ">Handling</th>
              <th class="color center ">Type of Caliber</th>
              <th class="color center ">Net Kilo </th>
              <th class="color center ">Gross Kilo </th>
              <th class="color center ">Type of currency </th>
              <th class="color center ">Price Box</th>
              <th class="color center ">Total Price</th>
            </tr>
          </thead>
           <tbody>
          ';
          foreach ($ARRAYDCARGAAGRUPADO as $keyDetalle => $s) :
            $DETALLEPARTS = explode('|', $keyDetalle);
            $NOMBREECOMERCIAL = $s['NOMBRE'] ?? ($DETALLEPARTS[0] ?? '');
            $NOMBRETMANEJO = $s['TMANEJO'] ?? ($DETALLEPARTS[1] ?? '');
            $NOMBRETCALIBRE = $s['TCALIBRE'] ?? ($DETALLEPARTS[2] ?? '');
            if($NOMBRETMANEJO === '' && isset($DETALLEPARTS[1])){
              $NOMBRETMANEJO = $DETALLEPARTS[1];
            }
            if($NOMBRETCALIBRE === '' && isset($DETALLEPARTS[2])){
              $NOMBRETCALIBRE = $DETALLEPARTS[2];
            }

            $KEYDETALLEUSO = $keyDetalle;
            if(($NOMBRETMANEJO === '' || $NOMBRETCALIBRE === '') && is_array($ARRAYNETKILO) && count($ARRAYNETKILO) > 0){
              foreach (array_keys($ARRAYNETKILO) as $keyAgrupado) {
                $PARTESAGRUPADO = explode('|', $keyAgrupado);
                if(($PARTESAGRUPADO[0] ?? '') === $NOMBREECOMERCIAL){
                  $COINCIDECALIBRE = $NOMBRETCALIBRE === '' || ($PARTESAGRUPADO[2] ?? '') === $NOMBRETCALIBRE;
                  if($COINCIDECALIBRE){
                    $KEYDETALLEUSO = $keyAgrupado;
                    if($NOMBRETMANEJO === '' && isset($PARTESAGRUPADO[1])){
                      $NOMBRETMANEJO = $PARTESAGRUPADO[1];
                    }
                    if($NOMBRETCALIBRE === '' && isset($PARTESAGRUPADO[2])){
                      $NOMBRETCALIBRE = $PARTESAGRUPADO[2];
                    }
                    break;
                  }
                }
              }
            }

            $DETALLEAGRUPADOUSO = $ARRAYDCARGAAGRUPADO[$KEYDETALLEUSO] ?? [];
            $NOMBRETMONEDA = $DETALLEAGRUPADOUSO['TMONEDA'] ?? ($s['TMONEDA'] ?? '');
            $PRECIOUS = $DETALLEAGRUPADOUSO['US'] ?? ($s['US'] ?? '');
            if($NOMBRETMONEDA === '' || $PRECIOUS === ''){
              $keyPrecioCompleto = $NOMBREECOMERCIAL.'|'.$NOMBRETMANEJO.'|'.$NOMBRETCALIBRE;
              if(isset($ARRAYPRECIOSTMONEDA[$keyPrecioCompleto])){
                if($NOMBRETMONEDA === ''){
                  $NOMBRETMONEDA = $ARRAYPRECIOSTMONEDA[$keyPrecioCompleto]['TMONEDA'];
                }
                if($PRECIOUS === ''){
                  $PRECIOUS = $ARRAYPRECIOSTMONEDA[$keyPrecioCompleto]['US'];
                }
              }
            }

            if($NOMBRETMONEDA === '' || $PRECIOUS === ''){
              if(isset($ARRAYPRECIOSTMONEDA[$KEYDETALLEUSO])){
                if($NOMBRETMONEDA === ''){
                  $NOMBRETMONEDA = $ARRAYPRECIOSTMONEDA[$KEYDETALLEUSO]['TMONEDA'];
                }
                if($PRECIOUS === ''){
                  $PRECIOUS = $ARRAYPRECIOSTMONEDA[$KEYDETALLEUSO]['US'];
                }
              }
            }

            if($NOMBRETMONEDA === '' || $PRECIOUS === ''){
              foreach ($ARRAYPRECIOSTMONEDA as $keyPrecio => $precioDato) {
                $partesPrecio = explode('|', $keyPrecio);
                if(($partesPrecio[0] ?? '') !== $NOMBREECOMERCIAL){
                  continue;
                }
                $coincideManejo = $NOMBRETMANEJO === '' || ($partesPrecio[1] ?? '') === $NOMBRETMANEJO;
                $coincideCalibre = $NOMBRETCALIBRE === '' || ($partesPrecio[2] ?? '') === $NOMBRETCALIBRE;
                if($coincideManejo && $coincideCalibre){
                  if($NOMBRETMONEDA === '' && $precioDato['TMONEDA'] !== ''){
                    $NOMBRETMONEDA = $precioDato['TMONEDA'];
                  }
                  if($PRECIOUS === '' && $precioDato['US'] !== ''){
                    $PRECIOUS = $precioDato['US'];
                  }
                  if($NOMBRETMONEDA !== '' && $PRECIOUS !== ''){
                    break;
                  }
                }
              }
            }

            $NETOAGRUPADO = $s['NETOSF'];
            $BRUTOAGRUPADO = $s['BRUTOSF'];
            if(isset($ARRAYNETKILO[$KEYDETALLEUSO])){
              $NETOAGRUPADO = $ARRAYNETKILO[$KEYDETALLEUSO];
              if(isset($ARRAYENVASEAGRUPADO[$KEYDETALLEUSO]) && $ARRAYENVASEAGRUPADO[$KEYDETALLEUSO] > 0){
                $NETOAGRUPADO = ($ARRAYNETKILO[$KEYDETALLEUSO] / $ARRAYENVASEAGRUPADO[$KEYDETALLEUSO]) * $s['ENVASESF'];
              }
            }
            if(isset($ARRAYGROSSKILO[$KEYDETALLEUSO])){
              $BRUTOAGRUPADO = $ARRAYGROSSKILO[$KEYDETALLEUSO];
              if(isset($ARRAYENVASEAGRUPADO[$KEYDETALLEUSO]) && $ARRAYENVASEAGRUPADO[$KEYDETALLEUSO] > 0){
                $BRUTOAGRUPADO = ($ARRAYGROSSKILO[$KEYDETALLEUSO] / $ARRAYENVASEAGRUPADO[$KEYDETALLEUSO]) * $s['ENVASESF'];
              }
            }

            $DESCRIPCIONDETALLE = $OBSERVACIONESI ? $OBSERVACIONESI : $s['NOMBRE'];
            $html = $html . '
              <tr class="">
                    <td class="center">'.$s['ENVASESF'].'</td>
                    <td class="center">'.$NOMBREECOMERCIAL.'</td>
                    <td class="center">'.$NOMBRETMANEJO.'</td>
                    <td class="center">'.$NOMBRETCALIBRE.'</td>
                    <td class="center">'.number_format($NETOAGRUPADO, 2, ",", ".").'</td>
                    <td class="center">'.number_format($BRUTOAGRUPADO, 2, ",", ".").'</td>
                    <td class="center" style="text-transform: uppercase;">'.$NOMBRETMONEDA.'</td>
                    <td class="center">'.$PRECIOUS.'</td>
                    <td class="center">'.number_format($s['TOTALUSSF'], 2, ",", ".").'</td>
              </tr>
            ';
            $TOTALENVASEV += $s['ENVASESF'];
            $TOTALNETOV += $NETOAGRUPADO;
            $TOTALBRUTOV += $BRUTOAGRUPADO;
            $TOTALUSV += $s['TOTALUSSF'];
            endforeach;

if($COSTOFLETEICARGA!=""){
  if($COSTOFLETEICARGA>0){
    $TOTALUSV+=$COSTOFLETEICARGA;  
            $html = $html . '              
              <tr class="">
                  <td class="center"> - </td>
                    <td class="center">Freight cost </td>
                    <td class="center"> - </td>
                    <td class="center"> - </td>
                    <td class="center"> - </td>
                    <td class="center"> - </td>
                    <td class="center"> - </td>
                    <td class="center"> - </td>
                    <td class="center">'.number_format($COSTOFLETEICARGA, 2, ",", ".").'</td>
              </tr>
            ';

  }else{
    $COSTOFLETEICARGA=0;
  }
}else{
  $COSTOFLETEICARGA=0;
}

            $html = $html . '
                    
                        <tr class="bt">
                          <th class="color center">'.number_format($TOTALENVASEV, 2, ",", ".").'</th>
                            <th class="color right">Overall Kilogram </td>
                            <th class="color center">&nbsp;</th>
                            <th class="color center">&nbsp;</th>
                            <th class="color center">'.number_format($TOTALNETOV, 2, ",", ".").'</th>
                            <th class="color center">'.number_format($TOTALBRUTOV, 2, ",", ".").'</th>
                            <td class="color center">&nbsp;</td>
                            <td class="color center">&nbsp;</td>
                            <th class="color center">'.number_format($TOTALUSV, 2, ",", ".").'</th>
                          </tr>
                    ';
            
            
            

$html = $html . '
    
  </tbody>
  </table>
<br><br><br><br><br>
  <div id="details" class="clearfix">

        <div id="client">
          <div class="address"><b>Invoice Note</b></div>
          <div class="address">  ' . $OBSERVACIONESI . ' </div>
          <div class="address">&nbsp;  </div>
          <div class="address">&nbsp;  </div>
          <div class="address"> <b>“The exporter of the products covered by this document (77.223.122-9) declares that, except where otherwise clearly indicated,
          <br>these products are of CHILE preferential origin Product Description: Fresh Blueberries 0810.40”</b></div>
        </div>
        
      </div>

    </main>
  </body>
</html>

';







//CREACION NOMBRE DEL ARCHIVO
$NOMBREARCHIVO = "reportInvoice_";
$FECHADOCUMENTO = $FECHANORMAL . "_" . $HORAFINAL;
$TIPODOCUMENTO = "Report";
$FORMATO = ".pdf";
$NOMBREARCHIVOFINAL = $NOMBREARCHIVO . $FECHADOCUMENTO . $FORMATO;

//CONFIGURACIOND DEL DOCUMENTO
$TIPOPAPEL = "LETTER";
$ORIENTACION = "P";
$LENGUAJE = "ES";
$UNICODE = "true";
$ENCODING = "UTF-8";

//DETALLE DEL CREADOR DEL INFORME
$TIPOINFORME = "Report Invoice";
$CREADOR = "Usuario";
$AUTOR = "Usuario";
$ASUNTO = "Report";

//API DE GENERACION DE PDF
require_once '../../api/mpdf/mpdf/autoload.php';
//$PDF = new \Mpdf\Mpdf();W
$PDF = new \Mpdf\Mpdf(['format' => 'letter']);

//CONFIGURACION FOOTER Y HEADER DEL PDF
$PDF->SetHTMLHeader('


<table width="100%" >
<tbody>
    <tr>
      <th width="55%" class="left f10"></th>
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
  Printed by: <b>' . $NOMBREEMPRESA . '.</b> print time: <b>' . $HORAFINAL2 . '</b>
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

//GENERAR PDF
$PDF->WriteHTML($html);
//METODO DE SALIDA
$PDF->Output($NOMBREARCHIVOFINAL, \Mpdf\Output\Destination::INLINE);
