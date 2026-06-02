<?php

include_once '../../assest/modelo/CALIDADINSPECTOR.php';
include_once '../../assest/config/BDCONFIG.php';

class CALIDADINSPECTOR_ADO {
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

    public function listarInspectorActivo($EMPRESA, $TEMPORADA)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_inspector
                                               WHERE ID_EMPRESA = ?
                                               AND ID_TEMPORADA = ?
                                               AND ESTADO_REGISTRO = 1
                                               ORDER BY NOMBRE_INSPECTOR ASC;");
            $datos->execute([$EMPRESA, $TEMPORADA]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function verInspector($ID)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_inspector WHERE ID_CALIDAD_INSPECTOR = ?;");
            $datos->execute([$ID]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarInspector(CALIDADINSPECTOR $CALIDADINSPECTOR)
    {
        try {
            $query = "INSERT INTO fruta_calidad_inspector (
                        NOMBRE_INSPECTOR,
                        ID_EMPRESA,
                        ID_TEMPORADA,
                        ID_USUARIOI,
                        ID_USUARIOM,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, 1);";
            $this->conexion->prepare($query)->execute([
                $CALIDADINSPECTOR->__GET('NOMBRE_INSPECTOR'),
                $CALIDADINSPECTOR->__GET('ID_EMPRESA'),
                $CALIDADINSPECTOR->__GET('ID_TEMPORADA'),
                $CALIDADINSPECTOR->__GET('ID_USUARIOI'),
                $CALIDADINSPECTOR->__GET('ID_USUARIOM')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function actualizarInspector(CALIDADINSPECTOR $CALIDADINSPECTOR)
    {
        try {
            $query = "UPDATE fruta_calidad_inspector SET
                        MODIFICACION = SYSDATE(),
                        NOMBRE_INSPECTOR = ?,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_INSPECTOR = ?;";
            $this->conexion->prepare($query)->execute([
                $CALIDADINSPECTOR->__GET('NOMBRE_INSPECTOR'),
                $CALIDADINSPECTOR->__GET('ID_USUARIOM'),
                $CALIDADINSPECTOR->__GET('ID_CALIDAD_INSPECTOR')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function deshabilitarInspector(CALIDADINSPECTOR $CALIDADINSPECTOR)
    {
        try {
            $query = "UPDATE fruta_calidad_inspector SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_REGISTRO = 0,
                        ID_USUARIOM = ?
                    WHERE ID_CALIDAD_INSPECTOR = ?;";
            $this->conexion->prepare($query)->execute([
                $CALIDADINSPECTOR->__GET('ID_USUARIOM'),
                $CALIDADINSPECTOR->__GET('ID_CALIDAD_INSPECTOR')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }
}
?>
