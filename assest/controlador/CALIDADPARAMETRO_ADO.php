<?php

include_once '../../assest/modelo/CALIDADPARAMETRO.php';
include_once '../../assest/config/BDCONFIG.php';

class CALIDADPARAMETRO_ADO {
    private $conexion;

    public function __CONSTRUCT()
    {
        try {
            $BDCONFIG = new BDCONFIG();
            $HOST = $BDCONFIG->__GET('HOST');
            $DBNAME = $BDCONFIG->__GET('DBNAME');
            $USER = $BDCONFIG->__GET('USER');
            $PASS = $BDCONFIG->__GET('PASS');

            $this->conexion = new PDO('mysql:host='.$HOST.';dbname='.$DBNAME, $USER, $PASS);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarParametro()
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_parametro ORDER BY ETAPA, ORDEN, NOMBRE_PARAMETRO;");
            $datos->execute();
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarParametroActivo($EMPRESA, $TEMPORADA, $ESPECIES = null, $ETAPA = null, $GRUPO_REPORTE = null)
    {
        try {
            $sql = "SELECT * FROM fruta_calidad_parametro
                    WHERE ID_EMPRESA = ?
                    AND ID_TEMPORADA = ?
                    AND ESTADO_REGISTRO = 1";
            $params = [$EMPRESA, $TEMPORADA];

            if ($ESPECIES !== null && $ESPECIES !== '') {
                $sql .= " AND ID_ESPECIES = ?";
                $params[] = $ESPECIES;
            }

            if ($ETAPA !== null && $ETAPA !== '') {
                $sql .= " AND ETAPA = ?";
                $params[] = $ETAPA;
            }

            if ($GRUPO_REPORTE !== null && $GRUPO_REPORTE !== '') {
                $sql .= " AND GRUPO_REPORTE = ?";
                $params[] = $GRUPO_REPORTE;
            }

            $sql .= " ORDER BY ETAPA, ORDEN, TIPO_PARAMETRO, NOMBRE_PARAMETRO;";
            $datos = $this->conexion->prepare($sql);
            $datos->execute($params);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function verParametro($ID)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_parametro WHERE ID_CALIDAD_PARAMETRO = ?;");
            $datos->execute([$ID]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function contarParametroGrupo($EMPRESA, $TEMPORADA, $ESPECIES, $ETAPA, $GRUPO_REPORTE)
    {
        try {
            $datos = $this->conexion->prepare("SELECT COUNT(*) AS TOTAL
                                               FROM fruta_calidad_parametro
                                               WHERE ID_EMPRESA = ?
                                               AND ID_TEMPORADA = ?
                                               AND ID_ESPECIES = ?
                                               AND ETAPA = ?
                                               AND GRUPO_REPORTE = ?;");
            $datos->execute([$EMPRESA, $TEMPORADA, $ESPECIES, $ETAPA, $GRUPO_REPORTE]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado ? (int) $resultado[0]["TOTAL"] : 0;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarParametro(CALIDADPARAMETRO $CALIDADPARAMETRO)
    {
        try {
            $query = "INSERT INTO fruta_calidad_parametro (
                        TIPO_PARAMETRO,
                        GRUPO_REPORTE,
                        ETAPA,
                        CODIGO_PARAMETRO,
                        NOMBRE_PARAMETRO,
                        UNIDAD_MEDIDA,
                        VALOR_MINIMO,
                        VALOR_MAXIMO,
                        VALOR_REFERENCIA,
                        ES_REQUERIDO,
                        ORDEN,
                        ID_EMPRESA,
                        ID_TEMPORADA,
                        ID_ESPECIES,
                        ID_USUARIOI,
                        ID_USUARIOM,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1);";

            $this->conexion->prepare($query)->execute([
                $CALIDADPARAMETRO->__GET('TIPO_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('GRUPO_REPORTE'),
                $CALIDADPARAMETRO->__GET('ETAPA'),
                $CALIDADPARAMETRO->__GET('CODIGO_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('NOMBRE_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('UNIDAD_MEDIDA'),
                $CALIDADPARAMETRO->__GET('VALOR_MINIMO'),
                $CALIDADPARAMETRO->__GET('VALOR_MAXIMO'),
                $CALIDADPARAMETRO->__GET('VALOR_REFERENCIA'),
                $CALIDADPARAMETRO->__GET('ES_REQUERIDO'),
                $CALIDADPARAMETRO->__GET('ORDEN'),
                $CALIDADPARAMETRO->__GET('ID_EMPRESA'),
                $CALIDADPARAMETRO->__GET('ID_TEMPORADA'),
                $CALIDADPARAMETRO->__GET('ID_ESPECIES'),
                $CALIDADPARAMETRO->__GET('ID_USUARIOI'),
                $CALIDADPARAMETRO->__GET('ID_USUARIOM')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function actualizarParametro(CALIDADPARAMETRO $CALIDADPARAMETRO)
    {
        try {
            $query = "UPDATE fruta_calidad_parametro SET
                        MODIFICACION = SYSDATE(),
                        TIPO_PARAMETRO = ?,
                        GRUPO_REPORTE = ?,
                        ETAPA = ?,
                        CODIGO_PARAMETRO = ?,
                        NOMBRE_PARAMETRO = ?,
                        UNIDAD_MEDIDA = ?,
                        VALOR_MINIMO = ?,
                        VALOR_MAXIMO = ?,
                        VALOR_REFERENCIA = ?,
                        ES_REQUERIDO = ?,
                        ORDEN = ?,
                        ID_TEMPORADA = ?,
                        ID_ESPECIES = ?,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_PARAMETRO = ?;";

            $this->conexion->prepare($query)->execute([
                $CALIDADPARAMETRO->__GET('TIPO_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('GRUPO_REPORTE'),
                $CALIDADPARAMETRO->__GET('ETAPA'),
                $CALIDADPARAMETRO->__GET('CODIGO_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('NOMBRE_PARAMETRO'),
                $CALIDADPARAMETRO->__GET('UNIDAD_MEDIDA'),
                $CALIDADPARAMETRO->__GET('VALOR_MINIMO'),
                $CALIDADPARAMETRO->__GET('VALOR_MAXIMO'),
                $CALIDADPARAMETRO->__GET('VALOR_REFERENCIA'),
                $CALIDADPARAMETRO->__GET('ES_REQUERIDO'),
                $CALIDADPARAMETRO->__GET('ORDEN'),
                $CALIDADPARAMETRO->__GET('ID_TEMPORADA'),
                $CALIDADPARAMETRO->__GET('ID_ESPECIES'),
                $CALIDADPARAMETRO->__GET('ID_USUARIOM'),
                $CALIDADPARAMETRO->__GET('ID_CALIDAD_PARAMETRO')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function deshabilitarParametro(CALIDADPARAMETRO $CALIDADPARAMETRO)
    {
        try {
            $query = "UPDATE fruta_calidad_parametro SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_REGISTRO = 0,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_PARAMETRO = ?;";
            $this->conexion->prepare($query)->execute([
                $CALIDADPARAMETRO->__GET('ID_USUARIOM'),
                $CALIDADPARAMETRO->__GET('ID_CALIDAD_PARAMETRO')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }
}
?>
