<?php
include_once __DIR__ . '/../../../assest/config/BDCONFIG.php';

function listarRecepcionGranelVista($vista, $empresa, $planta, $temporada)
{
    if (!$empresa || !$planta || !$temporada) {
        return array();
    }

    $vistasPermitidas = array(
        'vw_recepcion_mp_detallado',
        'vw_recepcion_ind_detallado',
        'vw_recepcion_granel_consolidado',
        'vw_recepcion_mp_listado',
        'vw_recepcion_ind_listado',
    );

    if (!in_array($vista, $vistasPermitidas, true)) {
        return array();
    }

    $conexion = BDCONFIG::conectar();
    if (!$conexion) {
        return array();
    }

    $orden = in_array($vista, array('vw_recepcion_mp_listado', 'vw_recepcion_ind_listado'), true)
        ? 'FECHA_RECEPCION DESC, NUMERO_RECEPCION DESC'
        : 'FECHA_RECEPCION DESC, NUMERO_RECEPCION DESC, FOLIO_DRECEPCION ASC';

    $sql = "SELECT *
            FROM {$vista}
            WHERE ID_EMPRESA = ?
              AND ID_PLANTA = ?
              AND ID_TEMPORADA = ?
            ORDER BY {$orden}";
    $consulta = $conexion->prepare($sql);
    $consulta->execute(array($empresa, $planta, $temporada));
    $resultado = $consulta->fetchAll(PDO::FETCH_ASSOC);
    $consulta = null;
    $conexion = null;

    return $resultado;
}

function textoReporteGranel($valor)
{
    return htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
}
