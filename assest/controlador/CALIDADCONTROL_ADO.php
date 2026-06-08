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
                        SCORE_GENERAL,
                        GRUPO_SCORE,
                        OBSERVACION,
                        ESTADO_REGISTRO
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1);";

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
                $CALIDADCONTROL->__GET('SCORE_GENERAL'),
                $CALIDADCONTROL->__GET('GRUPO_SCORE'),
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
                        SCORE_GENERAL = ?,
                        GRUPO_SCORE = ?,
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
                $CALIDADCONTROL->__GET('SCORE_GENERAL'),
                $CALIDADCONTROL->__GET('GRUPO_SCORE'),
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

    private function normalizarParametroScore($nombre)
    {
        $nombre = strtolower(trim((string) $nombre));
        $nombre = strtr($nombre, array(
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
            'Á' => 'a', 'É' => 'e', 'Í' => 'i', 'Ó' => 'o', 'Ú' => 'u', 'Ñ' => 'n'
        ));
        $nombre = preg_replace('/[^a-z0-9]+/', ' ', $nombre);
        return trim(preg_replace('/\s+/', ' ', $nombre));
    }

    private function reglasScoreEstandar()
    {
        return array(
            'calidad' => array(
                'dust' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 0), array(2, 0.01, 2), array(3, 2.01, 10), array(4, 10.01, 30), array(5, 30.01, 100))),
                'contamination' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 0), array(4, 0.01, 10), array(5, 10.01, 100))),
                'size' => array('tipo' => 'directo', 'suma' => false, 'rangos' => array(array(1, 1, 2), array(2, 3, 4))),
                'consistency' => array('tipo' => 'directo', 'suma' => false, 'rangos' => array(array(1, 1, 1), array(2, 2, 2))),
                'bloom' => array('tipo' => 'directo', 'suma' => false, 'rangos' => array(array(1, 80.01, 100), array(2, 0, 80))),
                'russet scars' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 15), array(4, 15.01, 25), array(5, 25.01, 100))),
                'attached stems' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 25), array(4, 25.01, 50), array(5, 50.01, 100))),
                'no bloom' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 25), array(4, 25.01, 50), array(5, 50.01, 100))),
                'flower remains' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 15), array(4, 15.01, 25), array(5, 25.01, 100))),
                'undersize' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 15), array(4, 15.01, 25), array(5, 25.01, 100))),
                'immature red' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 25), array(4, 25.01, 50), array(5, 50.01, 100)))
            ),
            'condicion' => array(
                'decay' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 0), array(2, 0.01, 0.1), array(3, 0.11, 0.6), array(4, 0.61, 4), array(5, 4.01, 100))),
                'decay incidence' => array('tipo' => 'directo', 'suma' => false, 'rangos' => array(array(2, 1, 1), array(3, 2, 2), array(4, 3, 7), array(5, 8, 10))),
                'mold' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 0), array(2, 0.01, 1), array(3, 1.01, 2), array(4, 2.01, 4), array(5, 4.01, 100))),
                'mold incidence' => array('tipo' => 'directo', 'suma' => false, 'rangos' => array(array(2, 1, 2), array(3, 3, 4), array(4, 5, 7), array(5, 8, 10))),
                'soft' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 2), array(2, 2.01, 5), array(3, 5.01, 9), array(4, 9.01, 20), array(5, 20.01, 100))),
                'sensitive' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 20), array(2, 20.01, 35), array(3, 35.01, 60), array(4, 60.01, 80), array(5, 80.01, 100))),
                'shriveling' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 2), array(2, 2.01, 5), array(3, 5.01, 10), array(4, 10.01, 20), array(5, 20.01, 100))),
                'broken skin' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 1), array(2, 1.01, 2), array(3, 2.01, 4), array(4, 4.01, 10), array(5, 10.01, 100))),
                'wounds' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 1), array(2, 1.01, 2), array(3, 2.01, 4), array(4, 4.01, 10), array(5, 10.01, 100))),
                'crushed' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 0), array(2, 0.01, 1), array(3, 1.01, 2), array(4, 2.01, 4), array(5, 4.01, 100))),
                'wet berries' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 1), array(2, 1.01, 2), array(3, 2.01, 4), array(4, 4.01, 10), array(5, 10.01, 100))),
                'so2 damage' => array('tipo' => 'porcentaje', 'suma' => true, 'rangos' => array(array(1, 0, 2), array(2, 2.01, 4), array(3, 4.01, 8), array(4, 8.01, 20), array(5, 20.01, 100)))
            ),
            'totales' => array(
                'calidad' => array(array(1, 0, 6), array(2, 6.01, 12), array(3, 12.01, 25), array(4, 25.01, 50), array(5, 50.01, 100)),
                'condicion' => array(array(1, 0, 4), array(2, 4.01, 8), array(3, 8.01, 15), array(4, 15.01, 25), array(5, 25.01, 100)),
                'total' => array(array(1, 0, 6), array(2, 6.01, 12), array(3, 12.01, 25), array(4, 25.01, 50), array(5, 50.01, 100))
            )
        );
    }

    private function scorePorRango($valor, $rangos)
    {
        foreach ($rangos as $rango) {
            if ((float) $valor >= (float) $rango[1] && (float) $valor <= (float) $rango[2]) {
                return (int) $rango[0];
            }
        }
        return null;
    }

    private function gruposScoreRecepcion($EMPRESA, $TEMPORADA, $ESPECIES, $RECEPCION, $MODO, $FOLIO = null)
    {
        try {
            $datosVariedad = $this->conexion->prepare("SELECT DISTINCT E.ID_EXIMATERIAPRIMA, V.NOMBRE_VESPECIES
                                                        FROM fruta_eximateriaprima E
                                                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                        WHERE E.ID_RECEPCION = ?
                                                        AND V.ID_ESPECIES = ?
                                                        AND E.ESTADO_REGISTRO = 1" . ($MODO === 'FOLIO' ? " AND E.ID_EXIMATERIAPRIMA = ?" : "") . ";");
            $paramsVariedad = [$RECEPCION, $ESPECIES];
            if ($MODO === 'FOLIO') {
                $paramsVariedad[] = $FOLIO;
            }
            $datosVariedad->execute($paramsVariedad);
            $variedades = $datosVariedad->fetchAll(PDO::FETCH_ASSOC);
            $grupos = array();
            $datosGrupo = $this->conexion->prepare("SELECT GRUPO_SCORE
                                                    FROM fruta_calidad_score_grupo_variedad
                                                    WHERE ID_EMPRESA = ?
                                                    AND ID_TEMPORADA = ?
                                                    AND ID_ESPECIES = ?
                                                    AND NOMBRE_NORMALIZADO = ?
                                                    AND ESTADO_REGISTRO = 1
                                                    LIMIT 1;");
            foreach ($variedades as $variedad) {
                $datosGrupo->execute([$EMPRESA, $TEMPORADA, $ESPECIES, $this->normalizarParametroScore($variedad['NOMBRE_VESPECIES'])]);
                $grupo = $datosGrupo->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($grupo)) {
                    $grupos[$grupo[0]['GRUPO_SCORE']] = $grupo[0]['GRUPO_SCORE'];
                }
            }
            return array_values($grupos);
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    private function reglasScoreDesdeBase($EMPRESA, $TEMPORADA, $ESPECIES, $GRUPO_SCORE)
    {
        try {
            $datos = $this->conexion->prepare("SELECT *
                                               FROM fruta_calidad_score_regla
                                               WHERE ID_EMPRESA = ?
                                               AND ID_TEMPORADA = ?
                                               AND ID_ESPECIES = ?
                                               AND GRUPO_SCORE = ?
                                               AND ESTADO_REGISTRO = 1
                                               ORDER BY NOMBRE_NORMALIZADO ASC, SCORE ASC;");
            $datos->execute([$EMPRESA, $TEMPORADA, $ESPECIES, $GRUPO_SCORE]);
            $rows = $datos->fetchAll(PDO::FETCH_ASSOC);
            $reglas = array('calidad' => array(), 'condicion' => array(), 'otros' => array(), 'totales' => array());
            foreach ($rows as $row) {
                $tipo = 'otros';
                if ($row['GRUPO_PARAMETRO'] === 'DEFECTOS_CONDICION') {
                    $tipo = 'condicion';
                } elseif ($row['GRUPO_PARAMETRO'] === 'DEFECTOS_CALIDAD') {
                    $tipo = 'calidad';
                }
                $nombre = $row['NOMBRE_NORMALIZADO'];
                if (!isset($reglas[$tipo][$nombre])) {
                    $reglas[$tipo][$nombre] = array(
                        'tipo' => ((int) $row['ES_PORCENTAJE'] === 1 ? 'porcentaje' : 'directo'),
                        'suma' => ($tipo === 'condicion' || $tipo === 'calidad'),
                        'rangos' => array(),
                        'comparadores' => array()
                    );
                }
                $reglas[$tipo][$nombre]['comparadores'][] = array(
                    'score' => (int) $row['SCORE'],
                    'operador' => $row['OPERADOR'],
                    'valor' => (float) $row['VALOR'],
                    'incluye' => (int) $row['INCLUYE_IGUAL']
                );
            }
            return $reglas;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    private function scorePorComparadores($valor, $comparadores)
    {
        foreach ($comparadores as $comparador) {
            $limite = (float) $comparador['valor'];
            $incluye = (int) $comparador['incluye'] === 1;
            $cumple = false;
            if ($comparador['operador'] === '>') {
                $cumple = $incluye ? ((float) $valor >= $limite) : ((float) $valor > $limite);
            } elseif ($comparador['operador'] === '<') {
                $cumple = $incluye ? ((float) $valor <= $limite) : ((float) $valor < $limite);
            } else {
                $cumple = ((float) $valor == $limite);
            }
            if ($cumple) {
                return (int) $comparador['score'];
            }
        }
        return null;
    }

    private function buscarReglaScore($reglas, $tipoRegla, $normalizado)
    {
        $orden = array();
        if ($tipoRegla !== null) {
            $orden[] = $tipoRegla;
        }
        $orden[] = 'otros';
        $orden[] = 'condicion';
        $orden[] = 'calidad';
        foreach ($orden as $tipo) {
            if (isset($reglas[$tipo][$normalizado])) {
                return array($reglas[$tipo][$normalizado], $tipo);
            }
        }
        return array(null, null);
    }

    private function calcularResolucionScoreConReglas($valores, $muestra, $reglas, $grupoScore)
    {
        $muestra = (float) $muestra;
        $totalCondicion = 0;
        $totalCalidad = 0;
        $valorFirme = 0;
        $score = 1;
        $evaluados = array();

        foreach ($valores as $item) {
            $grupo = $item['grupo'] ?? '';
            $nombre = $item['nombre'] ?? '';
            $valor = (float) ($item['valor'] ?? 0);
            $normalizado = $this->normalizarParametroScore($nombre);

            if ($grupo === 'PRESIONES' && strpos($normalizado, 'firme') !== false) {
                $valorFirme += $valor;
            }

            $tipoRegla = null;
            if ($grupo === 'DEFECTOS_CONDICION') {
                $tipoRegla = 'condicion';
            } elseif ($grupo === 'DEFECTOS_CALIDAD') {
                $tipoRegla = 'calidad';
            }

            if ($tipoRegla === 'condicion') {
                $totalCondicion += $valor;
            } elseif ($tipoRegla === 'calidad') {
                $totalCalidad += $valor;
            }

            list($regla, $tipoReglaUsado) = $this->buscarReglaScore($reglas, $tipoRegla, $normalizado);
            if ($regla === null) {
                continue;
            }

            $valorEvaluado = $regla['tipo'] === 'porcentaje' ? $this->porcentajeCalidad($valor, $muestra) : $valor;
            $scoreItem = null;
            if (!empty($regla['comparadores'])) {
                $scoreItem = $this->scorePorComparadores($valorEvaluado, $regla['comparadores']);
            } elseif (!empty($regla['rangos'])) {
                $scoreItem = $this->scorePorRango($valorEvaluado, $regla['rangos']);
            }
            if ($scoreItem !== null) {
                $score = max($score, $scoreItem);
                $evaluados[] = array(
                    'grupo' => $grupo,
                    'nombre' => $nombre,
                    'valor' => $valorEvaluado,
                    'grupo_score' => $grupoScore,
                    'score' => $scoreItem
                );
            }
        }

        $porcDefectoCondicion = $this->porcentajeCalidad($totalCondicion, $muestra);
        $porcDefectoCalidad = $this->porcentajeCalidad($totalCalidad, $muestra);
        $porcTotalDefectos = round($porcDefectoCondicion + $porcDefectoCalidad, 4);
        $porcFirmeza = $this->porcentajeCalidad($valorFirme, $muestra);
        $porcEstimadoExportacion = max(0, round(100 - $porcTotalDefectos, 4));

        if (!empty($reglas['totales'])) {
            $scoreCondicion = isset($reglas['totales']['condicion']) ? $this->scorePorRango($porcDefectoCondicion, $reglas['totales']['condicion']) : null;
            $scoreCalidad = isset($reglas['totales']['calidad']) ? $this->scorePorRango($porcDefectoCalidad, $reglas['totales']['calidad']) : null;
            $scoreTotal = isset($reglas['totales']['total']) ? $this->scorePorRango($porcTotalDefectos, $reglas['totales']['total']) : null;
            foreach (array($scoreCondicion, $scoreCalidad, $scoreTotal) as $scoreResumen) {
                if ($scoreResumen !== null) {
                    $score = max($score, $scoreResumen);
                }
            }
        }

        return array(
            'SCORE_GENERAL' => $score,
            'GRUPO_SCORE' => $grupoScore,
            'RESULTADO_GENERAL' => $this->resolverResultadoPorScore($score),
            'COLOR_RESOLUCION' => $this->colorResolucion($this->resolverResultadoPorScore($score)),
            'PORC_DEFECTO_CONDICION' => $porcDefectoCondicion,
            'PORC_DEFECTO_CALIDAD' => $porcDefectoCalidad,
            'PORC_FIRMEZA' => $porcFirmeza,
            'PORC_ESTIMADO_EXPORTACION' => $porcEstimadoExportacion,
            'PORC_TOTAL_DEFECTOS' => $porcTotalDefectos,
            'DETALLE_SCORE' => $evaluados
        );
    }

    public function calcularResolucionScore($valores, $muestra, $EMPRESA = null, $TEMPORADA = null, $ESPECIES = null, $RECEPCION = null, $MODO = null, $FOLIO = null)
    {
        $grupos = array();
        if ($EMPRESA !== null && $TEMPORADA !== null && $ESPECIES !== null && $RECEPCION !== null) {
            $grupos = $this->gruposScoreRecepcion($EMPRESA, $TEMPORADA, $ESPECIES, $RECEPCION, $MODO, $FOLIO);
        }

        if (empty($grupos)) {
            return $this->calcularResolucionScoreConReglas($valores, $muestra, $this->reglasScoreEstandar(), 'ESTANDAR');
        }

        $peor = null;
        $gruposEvaluados = array();
        foreach ($grupos as $grupoScore) {
            $reglas = $this->reglasScoreDesdeBase($EMPRESA, $TEMPORADA, $ESPECIES, $grupoScore);
            if (empty($reglas['condicion']) && empty($reglas['calidad']) && empty($reglas['otros'])) {
                continue;
            }
            $resultado = $this->calcularResolucionScoreConReglas($valores, $muestra, $reglas, $grupoScore);
            $gruposEvaluados[] = $grupoScore;
            if ($peor === null || (float) $resultado['SCORE_GENERAL'] > (float) $peor['SCORE_GENERAL']) {
                $peor = $resultado;
            }
        }

        if ($peor === null) {
            return $this->calcularResolucionScoreConReglas($valores, $muestra, $this->reglasScoreEstandar(), 'ESTANDAR');
        }

        $peor['GRUPO_SCORE'] = implode(',', $gruposEvaluados);
        return $peor;
    }

    private function porcentajeCalidad($valor, $muestra)
    {
        if ((float) $muestra <= 0) {
            return 0;
        }
        return round(((float) $valor / (float) $muestra) * 100, 4);
    }

    public function resolverResultadoPorScore($score)
    {
        $score = (int) $score;
        if ($score >= 1 && $score <= 3) {
            return 'APROBADO';
        }
        if ($score === 4) {
            return 'OBJETADO';
        }
        if ($score >= 5) {
            return 'RECHAZADO';
        }
        return 'SIN_SCORE';
    }

    public function colorResolucion($resultado)
    {
        switch (strtoupper((string) $resultado)) {
            case 'APROBADO':
                return '#2e7d32';
            case 'OBJETADO':
                return '#f9a825';
            case 'RECHAZADO':
                return '#c62828';
            default:
                return '#607d8b';
        }
    }

    public function listarProcesoCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    P.ID_PROCESO,
                                                    P.NUMERO_PROCESO,
                                                    P.FECHA_PROCESO,
                                                    P.TURNO,
                                                    P.KILOS_NETO_ENTRADA,
                                                    P.KILOS_NETO_PROCESO,
                                                    PR.NOMBRE_PRODUCTOR,
                                                    V.NOMBRE_VESPECIES,
                                                    V.ID_ESPECIES,
                                                    COUNT(DISTINCT E.ID_EXIEXPORTACION) AS TOTAL_PALLETS
                                                FROM fruta_proceso P
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = P.ID_VESPECIES
                                                LEFT JOIN fruta_productor PR ON PR.ID_PRODUCTOR = P.ID_PRODUCTOR
                                                LEFT JOIN fruta_exiexportacion E ON E.ID_PROCESO = P.ID_PROCESO AND E.ESTADO_REGISTRO = 1
                                                WHERE P.ID_EMPRESA = ?
                                                AND P.ID_PLANTA = ?
                                                AND P.ID_TEMPORADA = ?
                                                AND V.ID_ESPECIES = ?
                                                AND P.ESTADO_REGISTRO = 1
                                                GROUP BY P.ID_PROCESO
                                                ORDER BY P.FECHA_PROCESO DESC, P.NUMERO_PROCESO DESC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarPalletProcesoCalidad($PROCESO, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    E.ID_EXIEXPORTACION,
                                                    E.FOLIO_EXIEXPORTACION,
                                                    E.FOLIO_AUXILIAR_EXIEXPORTACION,
                                                    E.KILOS_NETO_EXIEXPORTACION,
                                                    E.CANTIDAD_ENVASE_EXIEXPORTACION,
                                                    E.ID_PROCESO,
                                                    E.ID_ICARGA,
                                                    E.REFERENCIA,
                                                    V.ID_ESPECIES,
                                                    V.NOMBRE_VESPECIES
                                                FROM fruta_exiexportacion E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                WHERE E.ID_PROCESO = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                ORDER BY E.FOLIO_AUXILIAR_EXIEXPORTACION ASC;");
            $datos->execute([$PROCESO, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarControlOperacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $ETAPA, $MODO, $IDOPERACION, $NUMEROOPERACION = null)
    {
        try {
            $sql = "SELECT
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
                    AND C.ETAPA = ?
                    AND C.MODO_INGRESO = ?
                    AND C.ESTADO_REGISTRO = 1";
            $params = [$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $ETAPA, $MODO];

            if ($IDOPERACION !== null && $IDOPERACION !== '') {
                $sql .= " AND C.ID_OPERACION = ?";
                $params[] = $IDOPERACION;
            } else {
                $sql .= " AND C.ID_OPERACION IS NULL";
            }

            if ($NUMEROOPERACION !== null && $NUMEROOPERACION !== '') {
                $sql .= " AND C.NUMERO_OPERACION = ?";
                $params[] = $NUMEROOPERACION;
            }

            $sql .= " ORDER BY C.ID_CALIDAD_CONTROL DESC;";
            $datos = $this->conexion->prepare($sql);
            $datos->execute($params);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function buscarPalletExportacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $FOLIO)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    E.ID_EXIEXPORTACION,
                                                    E.FOLIO_EXIEXPORTACION,
                                                    E.FOLIO_AUXILIAR_EXIEXPORTACION,
                                                    E.KILOS_NETO_EXIEXPORTACION,
                                                    E.CANTIDAD_ENVASE_EXIEXPORTACION,
                                                    COALESCE(E.ID_ICARGA, D.ID_ICARGA) AS ID_ICARGA,
                                                    COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '') AS REFERENCIA_CALIDAD,
                                                    COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '') AS CONTENEDOR_CALIDAD,
                                                    V.ID_ESPECIES,
                                                    V.NOMBRE_VESPECIES
                                                FROM fruta_exiexportacion E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                                                LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                                                WHERE E.ID_EMPRESA = ?
                                                AND E.ID_PLANTA = ?
                                                AND E.ID_TEMPORADA = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                AND (E.FOLIO_AUXILIAR_EXIEXPORTACION = ? OR E.FOLIO_EXIEXPORTACION = ?)
                                                ORDER BY E.ID_EXIEXPORTACION DESC
                                                LIMIT 1;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $FOLIO, $FOLIO]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarReferenciaExportacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '') AS REFERENCIA_CALIDAD,
                                                    MIN(COALESCE(E.ID_ICARGA, D.ID_ICARGA)) AS ID_ICARGA,
                                                    COUNT(DISTINCT E.ID_EXIEXPORTACION) AS TOTAL_PALLETS,
                                                    SUM(IFNULL(E.KILOS_NETO_EXIEXPORTACION, 0)) AS KILOS_NETO
                                                FROM fruta_exiexportacion E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                                                LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                                                WHERE E.ID_EMPRESA = ?
                                                AND E.ID_PLANTA = ?
                                                AND E.ID_TEMPORADA = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                AND COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '') <> ''
                                                GROUP BY REFERENCIA_CALIDAD
                                                ORDER BY REFERENCIA_CALIDAD DESC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarContenedorExportacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES)
    {
        try {
            $datos = $this->conexion->prepare("SELECT
                                                    COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '') AS CONTENEDOR_CALIDAD,
                                                    MIN(COALESCE(E.ID_ICARGA, D.ID_ICARGA)) AS ID_ICARGA,
                                                    COUNT(DISTINCT E.ID_EXIEXPORTACION) AS TOTAL_PALLETS,
                                                    SUM(IFNULL(E.KILOS_NETO_EXIEXPORTACION, 0)) AS KILOS_NETO
                                                FROM fruta_exiexportacion E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                                                LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                                                WHERE E.ID_EMPRESA = ?
                                                AND E.ID_PLANTA = ?
                                                AND E.ID_TEMPORADA = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                AND COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '') <> ''
                                                GROUP BY CONTENEDOR_CALIDAD
                                                ORDER BY CONTENEDOR_CALIDAD DESC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarPalletExportacionPorAgrupacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $TIPO, $VALOR)
    {
        try {
            $campo = strtoupper((string) $TIPO) === 'CONTENEDOR'
                ? "COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '')"
                : "COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '')";

            $datos = $this->conexion->prepare("SELECT
                                                    E.ID_EXIEXPORTACION,
                                                    E.FOLIO_EXIEXPORTACION,
                                                    E.FOLIO_AUXILIAR_EXIEXPORTACION,
                                                    E.KILOS_NETO_EXIEXPORTACION,
                                                    E.CANTIDAD_ENVASE_EXIEXPORTACION,
                                                    COALESCE(E.ID_ICARGA, D.ID_ICARGA) AS ID_ICARGA,
                                                    COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '') AS REFERENCIA_CALIDAD,
                                                    COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '') AS CONTENEDOR_CALIDAD
                                                FROM fruta_exiexportacion E
                                                INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                                                LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                                                LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                                                WHERE E.ID_EMPRESA = ?
                                                AND E.ID_PLANTA = ?
                                                AND E.ID_TEMPORADA = ?
                                                AND V.ID_ESPECIES = ?
                                                AND E.ESTADO_REGISTRO = 1
                                                AND {$campo} = ?
                                                ORDER BY E.FOLIO_AUXILIAR_EXIEXPORTACION ASC;");
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $VALOR]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarRevisionRecepcionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $MODO)
    {
        try {
            if ($MODO === 'FOLIO') {
                $sql = "SELECT
                            E.ID_EXIMATERIAPRIMA AS ID_OPERACION,
                            E.FOLIO_AUXILIAR_EXIMATERIAPRIMA AS NUMERO_OPERACION,
                            R.FECHA_RECEPCION AS FECHA_OPERACION,
                            CONCAT('Recepcion ', R.NUMERO_RECEPCION) AS DESCRIPCION,
                            E.KILOS_NETO_EXIMATERIAPRIMA AS KILOS_NETO,
                            1 AS TOTAL_PALLETS,
                            COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                            MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                            MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL
                        FROM fruta_eximateriaprima E
                        INNER JOIN fruta_recepcionmp R ON R.ID_RECEPCION = E.ID_RECEPCION
                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                        LEFT JOIN fruta_calidad_control_folio F ON F.ID_EXISTENCIA = E.ID_EXIMATERIAPRIMA AND F.TIPO_PRODUCTO = 'MP' AND F.ESTADO_REGISTRO = 1
                        LEFT JOIN fruta_calidad_control C ON C.ID_CALIDAD_CONTROL = F.ID_CALIDAD_CONTROL AND C.ETAPA = 'RECEPCION' AND C.MODO_INGRESO = 'FOLIO' AND C.ESTADO_REGISTRO = 1
                        WHERE R.ID_EMPRESA = ?
                        AND R.ID_PLANTA = ?
                        AND R.ID_TEMPORADA = ?
                        AND V.ID_ESPECIES = ?
                        AND R.ESTADO_REGISTRO = 1
                        AND E.ESTADO_REGISTRO = 1
                        GROUP BY E.ID_EXIMATERIAPRIMA
                        ORDER BY R.FECHA_RECEPCION DESC, E.FOLIO_AUXILIAR_EXIMATERIAPRIMA DESC;";
            } else {
                $sql = "SELECT
                            R.ID_RECEPCION AS ID_OPERACION,
                            R.NUMERO_RECEPCION AS NUMERO_OPERACION,
                            R.FECHA_RECEPCION AS FECHA_OPERACION,
                            CONCAT('Guia ', R.NUMERO_GUIA_RECEPCION) AS DESCRIPCION,
                            IFNULL(SUM(E.KILOS_NETO_EXIMATERIAPRIMA), 0) AS KILOS_NETO,
                            COUNT(DISTINCT E.ID_EXIMATERIAPRIMA) AS TOTAL_PALLETS,
                            COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                            MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                            MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL
                        FROM fruta_recepcionmp R
                        INNER JOIN fruta_eximateriaprima E ON E.ID_RECEPCION = R.ID_RECEPCION
                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                        LEFT JOIN fruta_calidad_control C ON C.ETAPA = 'RECEPCION' AND C.MODO_INGRESO = 'AGRUPADO' AND C.ID_OPERACION = R.ID_RECEPCION AND C.ESTADO_REGISTRO = 1
                        WHERE R.ID_EMPRESA = ?
                        AND R.ID_PLANTA = ?
                        AND R.ID_TEMPORADA = ?
                        AND V.ID_ESPECIES = ?
                        AND R.ESTADO_REGISTRO = 1
                        AND E.ESTADO_REGISTRO = 1
                        GROUP BY R.ID_RECEPCION
                        ORDER BY R.FECHA_RECEPCION DESC, R.NUMERO_RECEPCION DESC;";
            }
            $datos = $this->conexion->prepare($sql);
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarRevisionProcesoCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $MODO)
    {
        try {
            if ($MODO === 'PALLET') {
                $sql = "SELECT
                            E.ID_EXIEXPORTACION AS ID_OPERACION,
                            E.FOLIO_AUXILIAR_EXIEXPORTACION AS NUMERO_OPERACION,
                            E.FECHA_PROCESO AS FECHA_OPERACION,
                            CONCAT('Proceso ', P.NUMERO_PROCESO) AS DESCRIPCION,
                            E.KILOS_NETO_EXIEXPORTACION AS KILOS_NETO,
                            1 AS TOTAL_PALLETS,
                            COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                            MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                            MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL,
                            E.ID_PROCESO
                        FROM fruta_exiexportacion E
                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                        LEFT JOIN fruta_proceso P ON P.ID_PROCESO = E.ID_PROCESO
                        LEFT JOIN fruta_calidad_control_folio F ON F.ID_EXISTENCIA = E.ID_EXIEXPORTACION AND F.TIPO_PRODUCTO = 'PT' AND F.ESTADO_REGISTRO = 1
                        LEFT JOIN fruta_calidad_control C ON C.ID_CALIDAD_CONTROL = F.ID_CALIDAD_CONTROL AND C.ETAPA = 'PROCESO' AND C.MODO_INGRESO = 'PALLET' AND C.ESTADO_REGISTRO = 1
                        WHERE E.ID_EMPRESA = ?
                        AND E.ID_PLANTA = ?
                        AND E.ID_TEMPORADA = ?
                        AND V.ID_ESPECIES = ?
                        AND E.ID_PROCESO IS NOT NULL
                        AND E.ESTADO_REGISTRO = 1
                        GROUP BY E.ID_EXIEXPORTACION
                        ORDER BY E.FECHA_PROCESO DESC, E.FOLIO_AUXILIAR_EXIEXPORTACION DESC;";
            } else {
                $sql = "SELECT
                            P.ID_PROCESO AS ID_OPERACION,
                            P.NUMERO_PROCESO AS NUMERO_OPERACION,
                            P.FECHA_PROCESO AS FECHA_OPERACION,
                            PR.NOMBRE_PRODUCTOR AS DESCRIPCION,
                            P.KILOS_NETO_PROCESO AS KILOS_NETO,
                            COUNT(DISTINCT E.ID_EXIEXPORTACION) AS TOTAL_PALLETS,
                            COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                            MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                            MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL,
                            P.ID_PROCESO
                        FROM fruta_proceso P
                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = P.ID_VESPECIES
                        LEFT JOIN fruta_productor PR ON PR.ID_PRODUCTOR = P.ID_PRODUCTOR
                        LEFT JOIN fruta_exiexportacion E ON E.ID_PROCESO = P.ID_PROCESO AND E.ESTADO_REGISTRO = 1
                        LEFT JOIN fruta_calidad_control C ON C.ETAPA = 'PROCESO' AND C.MODO_INGRESO = 'PROCESO' AND C.ID_OPERACION = P.ID_PROCESO AND C.ESTADO_REGISTRO = 1
                        WHERE P.ID_EMPRESA = ?
                        AND P.ID_PLANTA = ?
                        AND P.ID_TEMPORADA = ?
                        AND V.ID_ESPECIES = ?
                        AND P.ESTADO_REGISTRO = 1
                        GROUP BY P.ID_PROCESO
                        ORDER BY P.FECHA_PROCESO DESC, P.NUMERO_PROCESO DESC;";
            }
            $datos = $this->conexion->prepare($sql);
            $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos = null;
            return $resultado;
        } catch(Exception $e) {
            die($e->getMessage());
        }
    }

    public function listarRevisionExportacionCalidad($EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES, $MODO)
    {
        try {
            if ($MODO === 'CONTENEDOR') {
                $campo = "COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), '')";
                $descripcion = "MIN(COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), ''))";
            } elseif ($MODO === 'REFERENCIA') {
                $campo = "COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '')";
                $descripcion = "MIN(COALESCE(NULLIF(D.NUMERO_CONTENEDOR_DESPACHOEX, ''), NULLIF(I.NCONTENEDOR_ICARGA, ''), ''))";
            } else {
                $sql = "SELECT
                            E.ID_EXIEXPORTACION AS ID_OPERACION,
                            E.FOLIO_AUXILIAR_EXIEXPORTACION AS NUMERO_OPERACION,
                            E.FECHA_DESPACHOEX AS FECHA_OPERACION,
                            COALESCE(NULLIF(I.NREFERENCIA_ICARGA, ''), NULLIF(E.REFERENCIA, ''), '') AS DESCRIPCION,
                            E.KILOS_NETO_EXIEXPORTACION AS KILOS_NETO,
                            1 AS TOTAL_PALLETS,
                            COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                            COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                            MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                            MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL
                        FROM fruta_exiexportacion E
                        INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                        LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                        LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                        LEFT JOIN fruta_calidad_control_folio F ON F.ID_EXISTENCIA = E.ID_EXIEXPORTACION AND F.TIPO_PRODUCTO = 'PT' AND F.ESTADO_REGISTRO = 1
                        LEFT JOIN fruta_calidad_control C ON C.ID_CALIDAD_CONTROL = F.ID_CALIDAD_CONTROL AND C.ETAPA = 'EXPORTACION' AND C.MODO_INGRESO = 'PALLET' AND C.ESTADO_REGISTRO = 1
                        WHERE E.ID_EMPRESA = ?
                        AND E.ID_PLANTA = ?
                        AND E.ID_TEMPORADA = ?
                        AND V.ID_ESPECIES = ?
                        AND E.ESTADO_REGISTRO = 1
                        GROUP BY E.ID_EXIEXPORTACION
                        ORDER BY E.FECHA_DESPACHOEX DESC, E.FOLIO_AUXILIAR_EXIEXPORTACION DESC;";
                $datos = $this->conexion->prepare($sql);
                $datos->execute([$EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
                $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
                $datos = null;
                return $resultado;
            }

            $sql = "SELECT
                        {$campo} AS ID_OPERACION,
                        {$campo} AS NUMERO_OPERACION,
                        MAX(E.FECHA_DESPACHOEX) AS FECHA_OPERACION,
                        {$descripcion} AS DESCRIPCION,
                        SUM(IFNULL(E.KILOS_NETO_EXIEXPORTACION, 0)) AS KILOS_NETO,
                        COUNT(DISTINCT E.ID_EXIEXPORTACION) AS TOTAL_PALLETS,
                        COUNT(DISTINCT C.ID_CALIDAD_CONTROL) AS TOTAL_CONTROLES,
                        COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'ABIERTO' THEN C.ID_CALIDAD_CONTROL END) AS ABIERTOS,
                        COUNT(DISTINCT CASE WHEN C.ESTADO_CONTROL = 'CERRADO' THEN C.ID_CALIDAD_CONTROL END) AS CERRADOS,
                        MAX(C.SCORE_GENERAL) AS SCORE_GENERAL,
                        MAX(C.RESULTADO_GENERAL) AS RESULTADO_GENERAL
                    FROM fruta_exiexportacion E
                    INNER JOIN fruta_vespecies V ON V.ID_VESPECIES = E.ID_VESPECIES
                    LEFT JOIN fruta_despachoex D ON D.ID_DESPACHOEX = E.ID_DESPACHOEX
                    LEFT JOIN fruta_icarga I ON I.ID_ICARGA = COALESCE(E.ID_ICARGA, D.ID_ICARGA)
                    LEFT JOIN fruta_calidad_control C ON C.ETAPA = 'EXPORTACION' AND C.MODO_INGRESO = ? AND C.NUMERO_OPERACION COLLATE utf8mb4_general_ci = {$campo} COLLATE utf8mb4_general_ci AND C.ESTADO_REGISTRO = 1
                    WHERE E.ID_EMPRESA = ?
                    AND E.ID_PLANTA = ?
                    AND E.ID_TEMPORADA = ?
                    AND V.ID_ESPECIES = ?
                    AND E.ESTADO_REGISTRO = 1
                    AND {$campo} <> ''
                    GROUP BY {$campo}
                    ORDER BY FECHA_OPERACION DESC, NUMERO_OPERACION DESC;";
            $datos = $this->conexion->prepare($sql);
            $datos->execute([$MODO, $EMPRESA, $PLANTA, $TEMPORADA, $ESPECIES]);
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
