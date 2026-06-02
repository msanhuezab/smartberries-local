<?php

class CALIDADCONTROLDETALLE {
    private $ID_CALIDAD_CONTROL_DETALLE;
    private $ID_CALIDAD_CONTROL;
    private $ID_CALIDAD_PARAMETRO;
    private $TIPO_PARAMETRO;
    private $NOMBRE_PARAMETRO;
    private $UNIDAD_MEDIDA;
    private $VALOR_NUMERICO;
    private $VALOR_TEXTO;
    private $RESULTADO;
    private $OBSERVACION;
    private $ESTADO_REGISTRO;

    public function __GET($k){ return $this->$k; }
    public function __SET($k, $v){ return $this->$k = $v; }
}
?>
