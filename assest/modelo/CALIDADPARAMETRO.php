<?php

class CALIDADPARAMETRO {
    private $ID_CALIDAD_PARAMETRO;
    private $TIPO_PARAMETRO;
    private $GRUPO_REPORTE;
    private $ETAPA;
    private $CODIGO_PARAMETRO;
    private $NOMBRE_PARAMETRO;
    private $UNIDAD_MEDIDA;
    private $VALOR_MINIMO;
    private $VALOR_MAXIMO;
    private $VALOR_REFERENCIA;
    private $ES_REQUERIDO;
    private $ORDEN;
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
