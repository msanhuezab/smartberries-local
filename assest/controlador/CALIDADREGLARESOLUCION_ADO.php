<?php

include_once '../../assest/modelo/CALIDADREGLARESOLUCION.php';
include_once '../../assest/config/BDCONFIG.php';

class CALIDADREGLARESOLUCION_ADO {
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

    public function listarReglaActiva($EMPRESA, $TEMPORADA, $ESPECIES = null)
    {
        try {
            $sql = "SELECT * FROM fruta_calidad_regla_resolucion
                    WHERE ID_EMPRESA = ?
                    AND ID_TEMPORADA = ?
                    AND ESTADO_REGISTRO = 1";
            $params = [$EMPRESA, $TEMPORADA];

            if ($ESPECIES !== null && $ESPECIES !== '') {
                $sql .= " AND ID_ESPECIES = ?";
                $params[] = $ESPECIES;
            }

            $sql .= " ORDER BY ID_ESPECIES ASC, VALOR_MINIMO DESC;";
            $datos = $this->conexion->prepare($sql);
            $datos->execute($params);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarRegla(CALIDADREGLARESOLUCION $REGLA)
    {
        try {
            $query = "INSERT INTO fruta_calidad_regla_resolucion (
                        NOMBRE_REGLA,
                        RESULTADO,
                        VALOR_MINIMO,
                        VALOR_MAXIMO,
                        ID_EMPRESA,
                        ID_TEMPORADA,
                        ID_ESPECIES,
                        ID_USUARIOI,
                        ID_USUARIOM,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1);";
            $this->conexion->prepare($query)->execute([
                $REGLA->__GET('NOMBRE_REGLA'),
                $REGLA->__GET('RESULTADO'),
                $REGLA->__GET('VALOR_MINIMO'),
                $REGLA->__GET('VALOR_MAXIMO'),
                $REGLA->__GET('ID_EMPRESA'),
                $REGLA->__GET('ID_TEMPORADA'),
                $REGLA->__GET('ID_ESPECIES'),
                $REGLA->__GET('ID_USUARIOI'),
                $REGLA->__GET('ID_USUARIOM')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function verRegla($ID)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_regla_resolucion WHERE ID_CALIDAD_REGLA_RESOLUCION = ?;");
            $datos->execute([$ID]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function actualizarRegla(CALIDADREGLARESOLUCION $REGLA)
    {
        try {
            $query = "UPDATE fruta_calidad_regla_resolucion SET
                        MODIFICACION = SYSDATE(),
                        RESULTADO = ?,
                        VALOR_MINIMO = ?,
                        VALOR_MAXIMO = ?,
                        ID_ESPECIES = ?,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_REGLA_RESOLUCION = ?;";
            $this->conexion->prepare($query)->execute([
                $REGLA->__GET('RESULTADO'),
                $REGLA->__GET('VALOR_MINIMO'),
                $REGLA->__GET('VALOR_MAXIMO'),
                $REGLA->__GET('ID_ESPECIES'),
                $REGLA->__GET('ID_USUARIOM'),
                $REGLA->__GET('ID_CALIDAD_REGLA_RESOLUCION')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function deshabilitarRegla(CALIDADREGLARESOLUCION $REGLA)
    {
        try {
            $query = "UPDATE fruta_calidad_regla_resolucion SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_REGISTRO = 0,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_REGLA_RESOLUCION = ?;";
            $this->conexion->prepare($query)->execute([
                $REGLA->__GET('ID_USUARIOM'),
                $REGLA->__GET('ID_CALIDAD_REGLA_RESOLUCION')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }
}
?>
