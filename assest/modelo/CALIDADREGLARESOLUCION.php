<?php

class CALIDADREGLARESOLUCION {
    private $ID_CALIDAD_REGLA_RESOLUCION;
    private $NOMBRE_REGLA;
    private $RESULTADO;
    private $VALOR_MINIMO;
    private $VALOR_MAXIMO;
    private $ID_EMPRESA;
    private $ID_TEMPORADA;
    private $ID_ESPECIES;
    private $ID_USUARIOI;
    private $ID_USUARIOM;
    private $ESTADO_REGISTRO;

    public function __GET($k){ return $this->$k; }
    public function __SET($k, $v){ return $this->$k = $v; }
}
?>
