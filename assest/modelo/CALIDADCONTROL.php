<?php

class CALIDADCONTROL {
    private $ID_CALIDAD_CONTROL;
    private $ETAPA;
    private $MODO_INGRESO;
    private $TIPO_PRODUCTO;
    private $ID_OPERACION;
    private $NUMERO_OPERACION;
    private $FECHA;
    private $HORA;
    private $ID_EMPRESA;
    private $ID_PLANTA;
    private $ID_TEMPORADA;
    private $ID_ESPECIES;
    private $ID_USUARIO;
    private $ID_CALIDAD_INSPECTOR;
    private $MUESTRA_GRAMOS;
    private $RESULTADO_GENERAL;
    private $ESTADO_CONTROL;
    private $PORC_DEFECTO_CONDICION;
    private $PORC_DEFECTO_CALIDAD;
    private $PORC_FIRMEZA;
    private $PORC_ESTIMADO_EXPORTACION;
    private $SCORE_GENERAL;
    private $OBSERVACION;
    private $FECHA_CIERRE;
    private $HORA_CIERRE;
    private $ID_USUARIO_CIERRE;
    private $OBSERVACION_CIERRE;
    private $ESTADO_REGISTRO;

    public function __GET($k){ return $this->$k; }
    public function __SET($k, $v){ return $this->$k = $v; }
}
?>
