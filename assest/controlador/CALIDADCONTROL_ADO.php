<?php

include_once '../../assest/modelo/CALIDADCONTROL.php';
include_once '../../assest/modelo/CALIDADCONTROLDETALLE.php';
include_once '../../assest/modelo/CALIDADCONTROLFOLIO.php';
include_once '../../assest/config/BDCONFIG.php';

class CALIDADCONTROL_ADO {
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

    public function listarControl($EMPRESA, $PLANTA, $TEMPORADA, $ETAPA = null)
    {
        try {
            $sql = "SELECT * FROM fruta_calidad_control
                    WHERE ID_EMPRESA = ?
                    AND ID_TEMPORADA = ?
                    AND ESTADO_REGISTRO = 1";
            $params = [$EMPRESA, $TEMPORADA];

            if ($PLANTA !== null && $PLANTA !== '') {
                $sql .= " AND ID_PLANTA = ?";
                $params[] = $PLANTA;
            }

            if ($ETAPA !== null && $ETAPA !== '') {
                $sql .= " AND ETAPA = ?";
                $params[] = $ETAPA;
            }

            $sql .= " ORDER BY FECHA DESC, ID_CALIDAD_CONTROL DESC;";
            $datos = $this->conexion->prepare($sql);
            $datos->execute($params);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function verControl($ID)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_control WHERE ID_CALIDAD_CONTROL = ?;");
            $datos->execute([$ID]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarDetalle($IDCONTROL)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_control_detalle WHERE ID_CALIDAD_CONTROL = ? AND ESTADO_REGISTRO = 1 ORDER BY ID_CALIDAD_CONTROL_DETALLE ASC;");
            $datos->execute([$IDCONTROL]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarFolio($IDCONTROL)
    {
        try {
            $datos = $this->conexion->prepare("SELECT * FROM fruta_calidad_control_folio WHERE ID_CALIDAD_CONTROL = ? AND ESTADO_REGISTRO = 1 ORDER BY ID_CALIDAD_CONTROL_FOLIO ASC;");
            $datos->execute([$IDCONTROL]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarControl(CALIDADCONTROL $CALIDADCONTROL)
    {
        try {
            $query = "INSERT INTO fruta_calidad_control (
                        ETAPA,
                        MODO_INGRESO,
                        TIPO_PRODUCTO,
                        ID_OPERACION,
                        NUMERO_OPERACION,
                        FECHA,
                        HORA,
                        ID_EMPRESA,
                        ID_PLANTA,
                        ID_TEMPORADA,
                        ID_ESPECIES,
                        ID_USUARIO,
                        ID_CALIDAD_INSPECTOR,
                        MUESTRA_GRAMOS,
                        RESULTADO_GENERAL,
                        ESTADO_CONTROL,
                        PORC_DEFECTO_CONDICION,
                        PORC_DEFECTO_CALIDAD,
                        PORC_FIRMEZA,
                        PORC_ESTIMADO_EXPORTACION,
                        OBSERVACION,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1);";

            $this->conexion->prepare($query)->execute([
                $CALIDADCONTROL->__GET('ETAPA'),
                $CALIDADCONTROL->__GET('MODO_INGRESO'),
                $CALIDADCONTROL->__GET('TIPO_PRODUCTO'),
                $CALIDADCONTROL->__GET('ID_OPERACION'),
                $CALIDADCONTROL->__GET('NUMERO_OPERACION'),
                $CALIDADCONTROL->__GET('FECHA'),
                $CALIDADCONTROL->__GET('HORA'),
                $CALIDADCONTROL->__GET('ID_EMPRESA'),
                $CALIDADCONTROL->__GET('ID_PLANTA'),
                $CALIDADCONTROL->__GET('ID_TEMPORADA'),
                $CALIDADCONTROL->__GET('ID_ESPECIES'),
                $CALIDADCONTROL->__GET('ID_USUARIO'),
                $CALIDADCONTROL->__GET('ID_CALIDAD_INSPECTOR'),
                $CALIDADCONTROL->__GET('MUESTRA_GRAMOS'),
                $CALIDADCONTROL->__GET('RESULTADO_GENERAL'),
                $CALIDADCONTROL->__GET('ESTADO_CONTROL'),
                $CALIDADCONTROL->__GET('PORC_DEFECTO_CONDICION'),
                $CALIDADCONTROL->__GET('PORC_DEFECTO_CALIDAD'),
                $CALIDADCONTROL->__GET('PORC_FIRMEZA'),
                $CALIDADCONTROL->__GET('PORC_ESTIMADO_EXPORTACION'),
                $CALIDADCONTROL->__GET('OBSERVACION')
            ]);

            return $this->conexion->lastInsertId();
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarControlRecepcionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $RECEPCION)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    C.*,
                                                    I.NOMBRE_INSPECTOR,
                                                    (
                                                        SELECT COUNT(DISTINCT F.ID_CALIDAD_CONTROL_FOLIO)
                                                        FROM fruta_calidad_control_folio F
                                                        WHERE F.ID_CALIDAD_CONTROL = C.ID_CALIDAD_CONTROL
                                                        AND F.ESTADO_REGISTRO = 1
                                                    ) AS TOTAL_FOLIOS,
                                                    (
                                                        SELECT GROUP_CONCAT(DISTINCT F.FOLIO_AUXILIAR ORDER BY F.FOLIO_AUXILIAR ASC SEPARATOR ', ')
                                                        FROM fruta_calidad_control_folio F
                                                        WHERE F.ID_CALIDAD_CONTROL = C.ID_CALIDAD_CONTROL
                                                        AND F.ESTADO_REGISTRO = 1
                                                    ) AS FOLIOS,
                                                    (
                                                        SELECT MIN(F.ID_EXISTENCIA)
                                                        FROM fruta_calidad_control_folio F
                                                        WHERE F.ID_CALIDAD_CONTROL = C.ID_CALIDAD_CONTROL
                                                        AND F.ESTADO_REGISTRO = 1
                                                    ) AS ID_FOLIO_CONTROL
                                                FROM fruta_calidad_control C
                                                LEFT JOIN fruta_calidad_inspector I ON I.ID_CALIDAD_INSPECTOR = C.ID_CALIDAD_INSPECTOR
                                                WHERE C.ID_EMPRESA = ?
                                                AND C.ID_PLANTA = ?
                                                AND C.ID_TEMPORADA = ?
                                                AND C.ID_ESPECIES = ?
                                                AND C.ETAPA = 'RECEPCION'
                                                AND C.ID_OPERACION = ?
                                                AND C.ESTADO_REGISTRO = 1
                                                ORDER BY C.ID_CALIDAD_CONTROL DESC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $RECEPCION]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function actualizarControlRecepcion(CALIDADCONTROL $CALIDADCONTROL)
    {
        try {
            $query = "UPDATE fruta_calidad_control SET
                        MODIFICACION = SYSDATE(),
                        ID_CALIDAD_INSPECTOR = ?,
                        MUESTRA_GRAMOS = ?,
                        RESULTADO_GENERAL = ?,
                        PORC_DEFECTO_CONDICION = ?,
                        PORC_DEFECTO_CALIDAD = ?,
                        PORC_FIRMEZA = ?,
                        PORC_ESTIMADO_EXPORTACION = ?,
                        OBSERVACION = ?
                    WHERE ID_CALIDAD_CONTROL = ?
                    AND ID_EMPRESA = ?
                    AND ID_PLANTA = ?
                    AND ID_TEMPORADA = ?
                    AND ESTADO_REGISTRO = 1
                    AND ESTADO_CONTROL = 'ABIERTO';";
            $this->conexion->prepare($query)->execute([
                $CALIDADCONTROL->__GET('ID_CALIDAD_INSPECTOR'),
                $CALIDADCONTROL->__GET('MUESTRA_GRAMOS'),
                $CALIDADCONTROL->__GET('RESULTADO_GENERAL'),
                $CALIDADCONTROL->__GET('PORC_DEFECTO_CONDICION'),
                $CALIDADCONTROL->__GET('PORC_DEFECTO_CALIDAD'),
                $CALIDADCONTROL->__GET('PORC_FIRMEZA'),
                $CALIDADCONTROL->__GET('PORC_ESTIMADO_EXPORTACION'),
                $CALIDADCONTROL->__GET('OBSERVACION'),
                $CALIDADCONTROL->__GET('ID_CALIDAD_CONTROL'),
                $CALIDADCONTROL->__GET('ID_EMPRESA'),
                $CALIDADCONTROL->__GET('ID_PLANTA'),
                $CALIDADCONTROL->__GET('ID_TEMPORADA')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function limpiarDetalleControl($IDCONTROL)
    {
        try {
            $query = "UPDATE fruta_calidad_control_detalle SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_REGISTRO = 0
                    WHERE ID_CALIDAD_CONTROL = ?;";
            $this->conexion->prepare($query)->execute([$IDCONTROL]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function cerrarControl(CALIDADCONTROL $CALIDADCONTROL)
    {
        try {
            $query = "UPDATE fruta_calidad_control SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_CONTROL = 'CERRADO',
                        FECHA_CIERRE = ?,
                        HORA_CIERRE = ?,
                        ID_USUARIO_CIERRE = ?,
                        OBSERVACION_CIERRE = ?
                    WHERE ID_CALIDAD_CONTROL = ?
                    AND ID_EMPRESA = ?
                    AND ID_PLANTA = ?
                    AND ID_TEMPORADA = ?
                    AND ESTADO_REGISTRO = 1
                    AND ESTADO_CONTROL = 'ABIERTO';";
            $this->conexion->prepare($query)->execute([
                $CALIDADCONTROL->__GET('FECHA_CIERRE'),
                $CALIDADCONTROL->__GET('HORA_CIERRE'),
                $CALIDADCONTROL->__GET('ID_USUARIO_CIERRE'),
                $CALIDADCONTROL->__GET('OBSERVACION_CIERRE'),
                $CALIDADCONTROL->__GET('ID_CALIDAD_CONTROL'),
                $CALIDADCONTROL->__GET('ID_EMPRESA'),
                $CALIDADCONTROL->__GET('ID_PLANTA'),
                $CALIDADCONTROL->__GET('ID_TEMPORADA')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarDetalle(CALIDADCONTROLDETALLE $DETALLE)
    {
        try {
            $query = "INSERT INTO fruta_calidad_control_detalle (
                        ID_CALIDAD_CONTROL,
                        ID_CALIDAD_PARAMETRO,
                        TIPO_PARAMETRO,
                        NOMBRE_PARAMETRO,
                        UNIDAD_MEDIDA,
                        VALOR_NUMERICO,
                        VALOR_TEXTO,
                        RESULTADO,
                        OBSERVACION,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1);";

            $this->conexion->prepare($query)->execute([
                $DETALLE->__GET('ID_CALIDAD_CONTROL'),
                $DETALLE->__GET('ID_CALIDAD_PARAMETRO'),
                $DETALLE->__GET('TIPO_PARAMETRO'),
                $DETALLE->__GET('NOMBRE_PARAMETRO'),
                $DETALLE->__GET('UNIDAD_MEDIDA'),
                $DETALLE->__GET('VALOR_NUMERICO'),
                $DETALLE->__GET('VALOR_TEXTO'),
                $DETALLE->__GET('RESULTADO'),
                $DETALLE->__GET('OBSERVACION')
            ]);

            return $this->conexion->lastInsertId();
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function agregarFolio(CALIDADCONTROLFOLIO $FOLIO)
    {
        try {
            $query = "INSERT INTO fruta_calidad_control_folio (
                        ID_CALIDAD_CONTROL,
                        TIPO_PRODUCTO,
                        ID_EXISTENCIA,
                        FOLIO_ORIGINAL,
                        FOLIO_AUXILIAR,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, 1);";

            $this->conexion->prepare($query)->execute([
                $FOLIO->__GET('ID_CALIDAD_CONTROL'),
                $FOLIO->__GET('TIPO_PRODUCTO'),
                $FOLIO->__GET('ID_EXISTENCIA'),
                $FOLIO->__GET('FOLIO_ORIGINAL'),
                $FOLIO->__GET('FOLIO_AUXILIAR')
            ]);

            return $this->conexion->lastInsertId();
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarRecepcionMateriaPrimaCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    R.ID_RECEPCION,
                                                    R.NUMERO_RECEPCION,
                                                    R.FECHA_RECEPCION,
                                                    R.NUMERO_GUIA_RECEPCION,
                                                    MIN(E.ID_PRODUCTOR) AS ID_PRODUCTOR,
                                                    COUNT(DISTINCT E.ID_EXIMATERIAPRIMA) AS TOTAL_FOLIOS,
                                                    IFNULL(SUM(E.KILOS_NETO_EXIMATERIAPRIMA),0) AS KILOS_NETO
                                                FROM fruta_recepcionmp R
                                                INNER JOIN fruta_eximateriaprima E ON E.ID_RECEPCION = R.ID_RECEPCION
                                                WHERE R.ID_EMPRESA = ?
                                                AND R.ID_PLANTA = ?
                                                AND R.ID_TEMPORADA = ?
                                                AND E.ID_VESPECIES IN (
                                                    SELECT ID_VESPECIES
                                                    FROM fruta_vespecies
                                                    WHERE ID_ESPECIES = ?
                                                    AND ESTADO_REGISTRO = 1
                                                )
                                                AND R.ESTADO_REGISTRO = 1
                                                AND E.ESTADO_REGISTRO = 1
                                                GROUP BY R.ID_RECEPCION, R.NUMERO_RECEPCION, R.FECHA_RECEPCION, R.NUMERO_GUIA_RECEPCION
                                                ORDER BY R.FECHA_RECEPCION DESC, R.NUMERO_RECEPCION DESC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function buscarRecepcionPorFolioCalidad($EMPRESA, $PLANTA, $TEMPORADA, $FOLIO)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    R.ID_RECEPCION,
                                                    R.NUMERO_RECEPCION,
                                                    R.FECHA_RECEPCION,
                                                    R.NUMERO_GUIA_RECEPCION,
                                                    R.ID_EMPRESA,
                                                    R.ID_PLANTA,
                                                    R.ID_TEMPORADA,
                                                    V.ID_ESPECIES,
                                                    COUNT(DISTINCT EX.ID_EXIMATERIAPRIMA) AS TOTAL_FOLIOS,
                                                    IFNULL(SUM(EX.KILOS_NETO_EXIMATERIAPRIMA),0) AS KILOS_NETO
                                                FROM fruta_eximateriaprima E
                                                INNER JOIN fruta_recepcionmp R ON R.ID_RECEPCION = E.ID_RECEPCION
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                INNER JOIN fruta_eximateriaprima EX ON EX.ID_RECEPCION = R.ID_RECEPCION
                                                INNER JOIN fruta_vespecies VX ON VX.ID_VESPECIES = EX.ID_VESPECIES
                                                WHERE R.ID_EMPRESA = ?
                                                AND R.ID_PLANTA = ?
                                                AND R.ID_TEMPORADA = ?
                                                AND (E.FOLIO_EXIMATERIAPRIMA = ? OR E.FOLIO_AUXILIAR_EXIMATERIAPRIMA = ?)
                                                AND VX.ID_ESPECIES = V.ID_ESPECIES
                                                AND R.ESTADO_REGISTRO = 1
                                                AND E.ESTADO_REGISTRO = 1
                                                AND EX.ESTADO_REGISTRO = 1
                                                GROUP BY R.ID_RECEPCION, R.NUMERO_RECEPCION, R.FECHA_RECEPCION, R.NUMERO_GUIA_RECEPCION, R.ID_EMPRESA, R.ID_PLANTA, R.ID_TEMPORADA, V.ID_ESPECIES
                                                LIMIT 1;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $FOLIO, $FOLIO]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarFolioRecepcionMateriaPrimaCalidad($RECEPCION, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    E.ID_EXIMATERIAPRIMA,
                                                    E.FOLIO_EXIMATERIAPRIMA,
                                                    E.FOLIO_AUXILIAR_EXIMATERIAPRIMA,
                                                    E.KILOS_NETO_EXIMATERIAPRIMA,
                                                    E.ID_PRODUCTOR,
                                                    V.NOMBRE_VESPECIES
                                                FROM fruta_eximateriaprima E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                WHERE E.ID_RECEPCION = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                ORDER BY E.FOLIO_AUXILIAR_EXIMATERIAPRIMA ASC;");
            $datos->execute([$RECEPCION, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function resolverResultado($EMPRESA, $TEMPORADA, $ESPECIES, $PORCENTAJE)
    {
        try {
            $datos = $this->conexion->prepare("SELECT *
                                               FROM fruta_calidad_regla_resolucion
                                               WHERE ID_EMPRESA = ?
                                               AND ID_TEMPORADA = ?
                                               AND ID_ESPECIES = ?
                                               AND ESTADO_REGISTRO = 1
                                               AND ? BETWEEN VALOR_MINIMO AND VALOR_MAXIMO
                                               ORDER BY VALOR_MINIMO DESC
                                               LIMIT 1;");
            $datos->execute([$EMPRESA, $TEMPORADA, $ESPECIES, $PORCENTAJE]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function actualizarResultadoControl(CALIDADCONTROL $CALIDADCONTROL)
    {
        try {
            $query = "UPDATE fruta_calidad_control SET
                        MODIFICACION = SYSDATE(),
                        RESULTADO_GENERAL = ?,
                        OBSERVACION = ?
                    WHERE ID_CALIDAD_CONTROL = ?;";
            $this->conexion->prepare($query)->execute([
                $CALIDADCONTROL->__GET('RESULTADO_GENERAL'),
                $CALIDADCONTROL->__GET('OBSERVACION'),
                $CALIDADCONTROL->__GET('ID_CALIDAD_CONTROL')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function deshabilitarControl(CALIDADCONTROL $CALIDADCONTROL)
    {
        try {
            $query = "UPDATE fruta_calidad_control SET
                        MODIFICACION = SYSDATE(),
                        ESTADO_REGISTRO = 0
                    WHERE ID_CALIDAD_CONTROL = ?;";
            $this->conexion->prepare($query)->execute([
                $CALIDADCONTROL->__GET('ID_CALIDAD_CONTROL')
            ]);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }
}
?>
