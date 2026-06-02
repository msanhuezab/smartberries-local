<?php

class CALIDADCONTROLFOLIO {
    private $ID_CALIDAD_CONTROL_FOLIO;
    private $ID_CALIDAD_CONTROL;
    private $TIPO_PRODUCTO;
    private $ID_EXISTENCIA;
    private $FOLIO_ORIGINAL;
    private $FOLIO_AUXILIAR;
    private $ESTADO_REGISTRO;

    public function __GET($k){ return $this->$k; }
    public function __SET($k, $v){ return $this->$k = $v; }
}
?>
