CREATE OR REPLACE VIEW view_liquidacion_exportacion AS
SELECT
    base.ID_EMPRESA,
    base.ID_TEMPORADA,
    base.NOMBRE_TEMPORADA                                     AS TEMPORADA,
    base.ID_ICARGA,
    base.ID_VALOR,
    CASE
        WHEN IFNULL(base.TIENE_VALORES, 0) = 1 THEN 'Liquidado'
        WHEN base.ID_VALOR IS NOT NULL         THEN 'En proceso'
        WHEN IFNULL(base.FOB_REFERENCIA_CAJA, 0) <> 0 THEN 'Estimado'
        ELSE 'Pendiente'
    END                                                       AS ESTADO_LIQUIDACION,
    base.NUMERO_VALOR,
    base.FECHA_VALOR,
    base.NUMERO_ICARGA,
    base.NREFERENCIA_ICARGA,
    base.NCONTENEDOR_ICARGA,
    base.ID_EXIEXPORTACION,
    base.FOLIO_EXIEXPORTACION,
    base.FOLIO_AUXILIAR_EXIEXPORTACION,
    base.FOLIO_MANUAL,
    base.FECHA_EMBALADO_EXIEXPORTACION,
    base.FECHA_DESPACHOEX,
    base.ID_PRODUCTOR,
    base.CSG_PRODUCTOR,
    base.NOMBRE_PRODUCTOR,
    base.ID_VESPECIES,
    base.NOMBRE_VESPECIES,
    base.ID_ESTANDAR,
    base.NOMBRE_ESTANDAR,
    base.ID_TCALIBRE,
    base.NOMBRE_TCALIBRE,
    base.ID_TEMBALAJE,
    base.ID_TMANEJO,
    base.ID_TCOLOR,
    base.ID_TCATEGORIA,
    base.CANTIDAD_ENVASE,
    base.KILOS_NETO,
    base.KILOS_BRUTO,
    base.FOB_REFERENCIA_CAJA,
    CASE WHEN base.KILOS_CAJA > 0
         THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
         ELSE 0 END                                           AS FOB_REFERENCIA_KG,
    CASE WHEN base.FOB_REAL > 0
         THEN base.FOB_REAL * base.KILOS_CAJA
         ELSE base.FOB_REFERENCIA_CAJA END                    AS FOB_VENTA_CAJA,
    CASE WHEN base.FOB_REAL > 0         THEN base.FOB_REAL
         WHEN base.KILOS_CAJA > 0       THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
         ELSE 0 END                                           AS FOB_VENTA_KG,
    base.KILOS_NETO *
        CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
             WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
             ELSE 0 END                                       AS VENTA_USD,
    CASE WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
         THEN IFNULL(gasto.TOTAL_COMISION, 0) *
              (base.KILOS_NETO *
               CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                    WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                    ELSE 0 END)
              / tot.TOTAL_VENTA
         ELSE 0 END                                           AS COMISION_PRORRATEADA,
    CASE WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
         THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
         ELSE 0 END                                           AS GASTOS_PRORRATEADOS,
    base.KILOS_NETO *
        CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
             WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
             ELSE 0 END
        - CASE WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
               THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                    (base.KILOS_NETO *
                     CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                          WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                          ELSE 0 END)
                    / tot.TOTAL_VENTA
               ELSE 0 END
        - CASE WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
               THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
               ELSE 0 END                                     AS RETORNO_NETO,
    CASE WHEN base.KILOS_NETO > 0 THEN
        (base.KILOS_NETO *
             CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                  WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                  ELSE 0 END
             - CASE WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
                    THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                         (base.KILOS_NETO *
                          CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                               WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                               ELSE 0 END)
                         / tot.TOTAL_VENTA
                    ELSE 0 END
             - CASE WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
                    THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
                    ELSE 0 END)
        / base.KILOS_NETO
    ELSE 0 END                                                AS FOB_FINAL_KG,
    CASE WHEN base.KILOS_NETO > 0 THEN
        (base.KILOS_NETO *
             CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                  WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                  ELSE 0 END
             - CASE WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
                    THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                         (base.KILOS_NETO *
                          CASE WHEN base.FOB_REAL > 0   THEN base.FOB_REAL
                               WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                               ELSE 0 END)
                         / tot.TOTAL_VENTA
                    ELSE 0 END
             - CASE WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
                    THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
                    ELSE 0 END)
        / base.KILOS_NETO * base.KILOS_CAJA
    ELSE 0 END                                                AS FOB_FINAL_CAJA,
    base.OBSERVACION,
    /* nuevas columnas de moneda */
    base.MONEDA_ORIGEN,
    base.TIPO_CAMBIO_USD,
    base.FOB_ORIGEN_KG

FROM (
    /* ── subquery base: una fila por exiexportacion ─────────────────────── */
    SELECT
        i.ID_EMPRESA,
        i.ID_TEMPORADA,
        IFNULL(temp.NOMBRE_TEMPORADA, '')                     AS NOMBRE_TEMPORADA,
        i.ID_ICARGA,
        liq.ID_VALOR,
        liq.NUMERO_VALOR,
        liq.FECHA_VALOR,
        liq.TIENE_VALORES,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
        exi.ID_EXIEXPORTACION,
        exi.FOLIO_EXIEXPORTACION,
        exi.FOLIO_AUXILIAR_EXIEXPORTACION,
        exi.FOLIO_MANUAL,
        exi.FECHA_EMBALADO_EXIEXPORTACION,
        exi.FECHA_DESPACHOEX,
        exi.ID_PRODUCTOR,
        IFNULL(prod.CSG_PRODUCTOR, '')                        AS CSG_PRODUCTOR,
        IFNULL(prod.NOMBRE_PRODUCTOR, 'Sin productor')        AS NOMBRE_PRODUCTOR,
        exi.ID_VESPECIES,
        IFNULL(ves.NOMBRE_VESPECIES, 'Sin variedad')          AS NOMBRE_VESPECIES,
        exi.ID_ESTANDAR,
        IFNULL(est.NOMBRE_ESTANDAR, 'Sin estandar')           AS NOMBRE_ESTANDAR,
        exi.ID_TCALIBRE,
        IFNULL(cal.NOMBRE_TCALIBRE, 'Sin calibre')            AS NOMBRE_TCALIBRE,
        exi.ID_TEMBALAJE,
        exi.ID_TMANEJO,
        exi.ID_TCOLOR,
        exi.ID_TCATEGORIA,
        IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)         AS CANTIDAD_ENVASE,
        IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)              AS KILOS_NETO,
        IFNULL(exi.KILOS_BRUTO_EXIEXPORTACION, 0)             AS KILOS_BRUTO,
        CASE WHEN IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0) > 0
             THEN IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) / IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)
             ELSE 0 END                                       AS KILOS_CAJA,
        IFNULL((
            SELECT MAX(dic.PRECIO_US_DICARGA)
            FROM   fruta_dicarga dic
            WHERE  dic.ID_ICARGA   = i.ID_ICARGA
              AND  dic.ID_ESTANDAR = exi.ID_ESTANDAR
              AND  dic.ID_TCALIBRE = exi.ID_TCALIBRE
              AND  (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES = exi.ID_VESPECIES)
              AND  dic.ESTADO_REGISTRO = 1
        ), 0)                                                 AS FOB_REFERENCIA_CAJA,
        /* FOB_REAL: 9 niveles de especificidad, siempre en USD/kg */
        IFNULL(COALESCE(
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=exi.ID_EXIEXPORTACION
               AND px.ID_PRODUCTOR=exi.ID_PRODUCTOR AND px.ID_ESTANDAR=exi.ID_ESTANDAR
               AND px.ID_VESPECIES=exi.ID_VESPECIES AND px.ID_TCALIBRE=exi.ID_TCALIBRE
               AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1)
        ), 0)                                                 AS FOB_REAL,
        /* OBSERVACION: misma cascada de 3 niveles */
        IFNULL(COALESCE(
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=exi.ID_EXIEXPORTACION
               AND px.ID_PRODUCTOR=exi.ID_PRODUCTOR AND px.ID_ESTANDAR=exi.ID_ESTANDAR
               AND px.ID_VESPECIES=exi.ID_VESPECIES AND px.ID_TCALIBRE=exi.ID_TCALIBRE
               AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1)
        ), '')                                                AS OBSERVACION,
        /* moneda origen y tipo de cambio del header */
        IFNULL(liq.MONEDA_ORIGEN, 'USD')                      AS MONEDA_ORIGEN,
        IFNULL(liq.TIPO_CAMBIO_USD, 1.000000)                 AS TIPO_CAMBIO_USD,
        /* FOB_ORIGEN_KG: valor en moneda original (mismos 9 niveles que FOB_REAL) */
        IFNULL(COALESCE(
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=exi.ID_EXIEXPORTACION
               AND px.ID_PRODUCTOR=exi.ID_PRODUCTOR AND px.ID_ESTANDAR=exi.ID_ESTANDAR
               AND px.ID_VESPECIES=exi.ID_VESPECIES AND px.ID_TCALIBRE=exi.ID_TCALIBRE
               AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
            (SELECT MAX(px.FOB_ORIGEN_KG) FROM liquidacion_detalle_exp px
             WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
               AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=0
               AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1)
        ), 0)                                                 AS FOB_ORIGEN_KG

    FROM fruta_icarga i
    JOIN fruta_despachoex dex
        ON dex.ID_ICARGA = i.ID_ICARGA AND dex.ESTADO_REGISTRO = 1
    JOIN fruta_exiexportacion exi
        ON exi.ID_DESPACHOEX = dex.ID_DESPACHOEX AND exi.ESTADO_REGISTRO = 1
    LEFT JOIN (
        SELECT d.ID_ICARGA,
               MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
        FROM   fruta_despachoex d
        WHERE  d.ESTADO_REGISTRO = 1
        GROUP BY d.ID_ICARGA
    ) dx ON dx.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT val.ID_ICARGA,
               MAX(val.ID_VALOR)       AS ID_VALOR,
               MAX(val.NUMERO_VALOR)   AS NUMERO_VALOR,
               MAX(val.FECHA_VALOR)    AS FECHA_VALOR,
               MAX(val.MONEDA_ORIGEN)  AS MONEDA_ORIGEN,
               MAX(val.TIPO_CAMBIO_USD) AS TIPO_CAMBIO_USD,
               MAX(CASE
                   WHEN IFNULL(det.FOB_REAL, 0) <> 0
                     OR IFNULL(det.COSTO_COMISION, 0) <> 0
                     OR IFNULL(det.COSTO_FLETE, 0) <> 0
                     OR IFNULL(det.COSTO_OTROS, 0) <> 0
                     OR IFNULL(gst.VALOR_DVALOR, 0) <> 0
                   THEN 1 ELSE 0 END) AS TIENE_VALORES
        FROM liquidacion_valor val
        LEFT JOIN liquidacion_detalle_exp det
            ON det.ID_VALOR = val.ID_VALOR AND det.ESTADO_REGISTRO = 1
        LEFT JOIN liquidacion_dvalor gst
            ON gst.ID_VALOR = val.ID_VALOR AND gst.ESTADO_REGISTRO = 1
        WHERE val.ESTADO_REGISTRO = 1
        GROUP BY val.ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN fruta_productor    prod ON prod.ID_PRODUCTOR = exi.ID_PRODUCTOR
    LEFT JOIN fruta_vespecies    ves  ON ves.ID_VESPECIES  = exi.ID_VESPECIES
    LEFT JOIN estandar_eexportacion est ON est.ID_ESTANDAR = exi.ID_ESTANDAR
    LEFT JOIN fruta_tcalibre     cal  ON cal.ID_TCALIBRE   = exi.ID_TCALIBRE
    LEFT JOIN principal_temporada temp ON temp.ID_TEMPORADA = i.ID_TEMPORADA
    WHERE i.ESTADO_REGISTRO = 1
) base

/* ── totales por carga (para prorrateo) ─────────────────────────────────── */
LEFT JOIN (
    SELECT i.ID_ICARGA,
           SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)) AS TOTAL_NETO,
           SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) *
               IFNULL(COALESCE(
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=exi.ID_EXIEXPORTACION
                      AND px.ID_PRODUCTOR=exi.ID_PRODUCTOR AND px.ID_ESTANDAR=exi.ID_ESTANDAR
                      AND px.ID_VESPECIES=exi.ID_VESPECIES AND px.ID_TCALIBRE=exi.ID_TCALIBRE
                      AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
                      AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=exi.ID_VESPECIES
                      AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
                      AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
                      AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=exi.ID_ESTANDAR AND px.ID_VESPECIES=0
                      AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=exi.ID_VESPECIES
                      AND px.ID_TCALIBRE=0 AND px.ESTADO_REGISTRO=1),
                   (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px
                    WHERE px.ID_VALOR=liq.ID_VALOR AND px.ID_EXIEXPORTACION=0 AND px.ID_PRODUCTOR=0
                      AND px.ID_ESTANDAR=0 AND px.ID_VESPECIES=0
                      AND px.ID_TCALIBRE=exi.ID_TCALIBRE AND px.ESTADO_REGISTRO=1),
                   CASE WHEN IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0) > 0
                        THEN IFNULL((
                            SELECT MAX(dic.PRECIO_US_DICARGA)
                            FROM fruta_dicarga dic
                            WHERE dic.ID_ICARGA=i.ID_ICARGA AND dic.ID_ESTANDAR=exi.ID_ESTANDAR
                              AND dic.ID_TCALIBRE=exi.ID_TCALIBRE
                              AND (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES=exi.ID_VESPECIES)
                              AND dic.ESTADO_REGISTRO=1
                        ), 0) /
                        (IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) /
                         IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0))
                        ELSE 0 END
               ), 0)) AS TOTAL_VENTA
    FROM fruta_icarga i
    JOIN fruta_despachoex dex
        ON dex.ID_ICARGA = i.ID_ICARGA AND dex.ESTADO_REGISTRO = 1
    JOIN fruta_exiexportacion exi
        ON exi.ID_DESPACHOEX = dex.ID_DESPACHOEX AND exi.ESTADO_REGISTRO = 1
    LEFT JOIN (
        SELECT val.ID_ICARGA, MAX(val.ID_VALOR) AS ID_VALOR
        FROM liquidacion_valor val
        WHERE val.ESTADO_REGISTRO = 1
        GROUP BY val.ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    WHERE i.ESTADO_REGISTRO = 1
    GROUP BY i.ID_ICARGA
) tot ON tot.ID_ICARGA = base.ID_ICARGA

/* ── totales de gastos/comisiones por carga ─────────────────────────────── */
LEFT JOIN (
    SELECT val.ID_ICARGA,
           SUM(CASE WHEN UCASE(ti.NOMBRE_TITEM) LIKE '%COMISI%'
                    THEN IFNULL(dv.VALOR_DVALOR, 0) ELSE 0 END) AS TOTAL_COMISION,
           SUM(CASE WHEN UCASE(ti.NOMBRE_TITEM) LIKE '%COMISI%'
                    THEN 0 ELSE IFNULL(dv.VALOR_DVALOR, 0) END) AS TOTAL_GASTOS
    FROM liquidacion_valor val
    JOIN liquidacion_dvalor dv
        ON dv.ID_VALOR = val.ID_VALOR AND dv.ESTADO_REGISTRO = 1
    JOIN liquidacion_titem ti
        ON ti.ID_TITEM = dv.ID_TITEM AND ti.ESTADO_REGISTRO = 1 AND ti.TAITEM = 1
    WHERE val.ESTADO_REGISTRO = 1
    GROUP BY val.ID_ICARGA
) gasto ON gasto.ID_ICARGA = base.ID_ICARGA;
