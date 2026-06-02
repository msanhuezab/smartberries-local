<?php

class CALIDADINSPECTOR {
    private $ID_CALIDAD_INSPECTOR;
    private $NOMBRE_INSPECTOR;
    private $ID_EMPRESA;
    private $ID_TEMPORADA;
    private $ID_USUARIOI;
    private $ID_USUARIOM;
    private $ESTADO_REGISTRO;

    public function __GET($k){ return $this->$k; }
    public function __SET($k, $v){ return $this->$k = $v; }
}
?>
