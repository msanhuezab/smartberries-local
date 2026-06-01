DROP VIEW IF EXISTS view_liquidacion_exportacion;

CREATE VIEW view_liquidacion_exportacion AS
SELECT
    base.ID_EMPRESA,
    base.ID_TEMPORADA,
    base.NOMBRE_TEMPORADA AS TEMPORADA,
    base.ID_ICARGA,
    base.ID_VALOR,
    base.CONDICION_FLETE,
    CASE
        WHEN IFNULL(base.TIENE_VALORES, 0) = 1 THEN 'Liquidado'
        WHEN base.ID_VALOR IS NOT NULL THEN 'En proceso'
        WHEN IFNULL(base.FOB_REFERENCIA_CAJA, 0) <> 0 THEN 'Estimado'
        ELSE 'Pendiente'
    END AS ESTADO_LIQUIDACION,
    base.NUMERO_VALOR,
    base.FECHA_VALOR,
    base.NUMERO_ICARGA,
    base.NREFERENCIA_ICARGA,
    base.NCONTENEDOR_ICARGA,
    base.ID_MERCADO,
    base.NOMBRE_MERCADO,
    base.ID_CONSIGNATARIO,
    base.NOMBRE_CONSIGNATARIO,
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
    base.CANTIDAD_ENVASE AS CAJAS,
    base.CANTIDAD_ENVASE,
    base.KILOS_NETO,
    base.KILOS_BRUTO,
    base.FOB_REFERENCIA_CAJA,
    CASE
        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
        ELSE 0
    END AS FOB_REFERENCIA_KG,
    CASE
        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL * base.KILOS_CAJA
        ELSE base.FOB_REFERENCIA_CAJA
    END AS FOB_VENTA_CAJA,
    CASE
        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
        ELSE 0
    END AS FOB_VENTA_KG,
    base.KILOS_NETO *
        CASE
            WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
            WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
            ELSE 0
        END AS VENTA_USD,
    base.KILOS_NETO *
        CASE
            WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
            WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
            ELSE 0
        END AS VENTA_USD_BRUTO,
    CASE
        WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
            THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                (
                    base.KILOS_NETO *
                    CASE
                        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                        ELSE 0
                    END
                ) / tot.TOTAL_VENTA
        ELSE 0
    END AS COMISION_PRORRATEADA,
    CASE
        WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
            THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
        ELSE 0
    END AS GASTOS_PRORRATEADOS,
    (
        base.KILOS_NETO *
        CASE
            WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
            WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
            ELSE 0
        END
    )
    -
    CASE
        WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
            THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                (
                    base.KILOS_NETO *
                    CASE
                        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                        ELSE 0
                    END
                ) / tot.TOTAL_VENTA
        ELSE 0
    END
    -
    CASE
        WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
            THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
        ELSE 0
    END AS VENTA_USD_NETO,
    (
        base.KILOS_NETO *
        CASE
            WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
            WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
            ELSE 0
        END
    )
    -
    CASE
        WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
            THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                (
                    base.KILOS_NETO *
                    CASE
                        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                        ELSE 0
                    END
                ) / tot.TOTAL_VENTA
        ELSE 0
    END
    -
    CASE
        WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
            THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
        ELSE 0
    END AS RETORNO_NETO,
    CASE
        WHEN base.KILOS_NETO > 0 THEN
            (
                (
                    base.KILOS_NETO *
                    CASE
                        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                        ELSE 0
                    END
                )
                -
                CASE
                    WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
                        THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                            (
                                base.KILOS_NETO *
                                CASE
                                    WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                                    WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                                    ELSE 0
                                END
                            ) / tot.TOTAL_VENTA
                    ELSE 0
                END
                -
                CASE
                    WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
                        THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
                    ELSE 0
                END
            ) / base.KILOS_NETO
        ELSE 0
    END AS FOB_FINAL_KG,
    CASE
        WHEN base.KILOS_NETO > 0 THEN
            (
                (
                    (
                        base.KILOS_NETO *
                        CASE
                            WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                            WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                            ELSE 0
                        END
                    )
                    -
                    CASE
                        WHEN IFNULL(tot.TOTAL_VENTA, 0) > 0
                            THEN IFNULL(gasto.TOTAL_COMISION, 0) *
                                (
                                    base.KILOS_NETO *
                                    CASE
                                        WHEN base.FOB_REAL > 0 THEN base.FOB_REAL
                                        WHEN base.KILOS_CAJA > 0 THEN base.FOB_REFERENCIA_CAJA / base.KILOS_CAJA
                                        ELSE 0
                                    END
                                ) / tot.TOTAL_VENTA
                        ELSE 0
                    END
                    -
                    CASE
                        WHEN IFNULL(tot.TOTAL_NETO, 0) > 0
                            THEN IFNULL(gasto.TOTAL_GASTOS, 0) * base.KILOS_NETO / tot.TOTAL_NETO
                        ELSE 0
                    END
                ) / base.KILOS_NETO
            ) * base.KILOS_CAJA
        ELSE 0
    END AS FOB_FINAL_CAJA,
    base.OBSERVACION
FROM (
    SELECT
        i.ID_EMPRESA,
        i.ID_TEMPORADA,
        IFNULL(temp.NOMBRE_TEMPORADA, '') AS NOMBRE_TEMPORADA,
        i.ID_ICARGA,
        liq.ID_VALOR,
        liq.NUMERO_VALOR,
        liq.FECHA_VALOR,
        CASE WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' THEN 'PREPAID' ELSE 'COLLECT' END AS CONDICION_FLETE,
        liq.TIENE_VALORES,
        i.NUMERO_ICARGA,
        i.NREFERENCIA_ICARGA,
        COALESCE(NULLIF(i.NCONTENEDOR_ICARGA, ''), dx.CONTENEDOR_DESPACHO, '') AS NCONTENEDOR_ICARGA,
        i.ID_MERCADO,
        IFNULL(mer.NOMBRE_MERCADO, 'Sin mercado') AS NOMBRE_MERCADO,
        i.ID_CONSIGNATARIO,
        IFNULL(cons.NOMBRE_CONSIGNATARIO, 'Sin cliente') AS NOMBRE_CONSIGNATARIO,
        exi.ID_EXIEXPORTACION,
        exi.FOLIO_EXIEXPORTACION,
        exi.FOLIO_AUXILIAR_EXIEXPORTACION,
        exi.FOLIO_MANUAL,
        exi.FECHA_EMBALADO_EXIEXPORTACION,
        exi.FECHA_DESPACHOEX,
        exi.ID_PRODUCTOR,
        IFNULL(prod.CSG_PRODUCTOR, '') AS CSG_PRODUCTOR,
        IFNULL(prod.NOMBRE_PRODUCTOR, 'Sin productor') AS NOMBRE_PRODUCTOR,
        exi.ID_VESPECIES,
        IFNULL(ves.NOMBRE_VESPECIES, 'Sin variedad') AS NOMBRE_VESPECIES,
        exi.ID_ESTANDAR,
        IFNULL(est.NOMBRE_ESTANDAR, 'Sin estandar') AS NOMBRE_ESTANDAR,
        exi.ID_TCALIBRE,
        IFNULL(cal.NOMBRE_TCALIBRE, 'Sin calibre') AS NOMBRE_TCALIBRE,
        exi.ID_TEMBALAJE,
        exi.ID_TMANEJO,
        exi.ID_TCOLOR,
        exi.ID_TCATEGORIA,
        IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0) AS CANTIDAD_ENVASE,
        IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) AS KILOS_NETO,
        IFNULL(exi.KILOS_BRUTO_EXIEXPORTACION, 0) AS KILOS_BRUTO,
        CASE
            WHEN IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0) > 0
                THEN IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) / IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0)
            ELSE 0
        END AS KILOS_CAJA,
        IFNULL(COALESCE(
            (
                SELECT MAX(idet.PRECIO_CAJA)
                FROM exportadora_invoice inv
                INNER JOIN exportadora_invoice_detalle idet ON idet.ID_INVOICE = inv.ID_INVOICE
                    AND idet.ESTADO_REGISTRO = 1
                WHERE inv.ID_ICARGA = i.ID_ICARGA
                AND inv.ESTADO_INVOICE = 'CONFIRMADA'
                AND inv.ESTADO_REGISTRO = 1
                AND idet.ID_ESTANDAR = exi.ID_ESTANDAR
                AND idet.ID_TCALIBRE = exi.ID_TCALIBRE
                AND (idet.ID_VESPECIES = 0 OR idet.ID_VESPECIES = exi.ID_VESPECIES)
            ),
            (
                SELECT MAX(dic.PRECIO_US_DICARGA)
                FROM fruta_dicarga dic
                WHERE dic.ID_ICARGA = i.ID_ICARGA
                AND dic.ID_ESTANDAR = exi.ID_ESTANDAR
                AND dic.ID_TCALIBRE = exi.ID_TCALIBRE
                AND (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES = exi.ID_VESPECIES)
                AND dic.ESTADO_REGISTRO = 1
            )
        ), 0) AS FOB_REFERENCIA_CAJA,
        IFNULL(COALESCE(
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION AND px.ID_PRODUCTOR = exi.ID_PRODUCTOR AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1)
        ), 0) AS FOB_REAL,
        IFNULL(COALESCE(
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION AND px.ID_PRODUCTOR = exi.ID_PRODUCTOR AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
            (SELECT MAX(px.OBSERVACION) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1)
        ), '') AS OBSERVACION
    FROM fruta_icarga i
    INNER JOIN fruta_despachoex dex ON dex.ID_ICARGA = i.ID_ICARGA
        AND dex.ESTADO_REGISTRO = 1
    INNER JOIN fruta_exiexportacion exi ON exi.ID_DESPACHOEX = dex.ID_DESPACHOEX
        AND exi.ESTADO_REGISTRO = 1
    LEFT JOIN (
        SELECT
            d.ID_ICARGA,
            MIN(NULLIF(NULLIF(d.NUMERO_CONTENEDOR_DESPACHOEX, ''), '0')) AS CONTENEDOR_DESPACHO
        FROM fruta_despachoex d
        WHERE d.ESTADO_REGISTRO = 1
        GROUP BY d.ID_ICARGA
    ) dx ON dx.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN (
        SELECT
            val.ID_ICARGA,
            MAX(val.ID_VALOR) AS ID_VALOR,
            MAX(val.NUMERO_VALOR) AS NUMERO_VALOR,
            MAX(val.FECHA_VALOR) AS FECHA_VALOR,
            MAX(CASE
                WHEN IFNULL(val.ESTADO_LIQUIDACION, 'LIQUIDADA') = 'REABIERTA' THEN 0
                WHEN IFNULL(det.FOB_REAL, 0) <> 0
                    OR IFNULL(det.COSTO_COMISION, 0) <> 0
                    OR IFNULL(det.COSTO_FLETE, 0) <> 0
                    OR IFNULL(det.COSTO_OTROS, 0) <> 0
                    OR IFNULL(gasto.VALOR_DVALOR, 0) <> 0
                THEN 1 ELSE 0
            END) AS TIENE_VALORES
        FROM liquidacion_valor val
        LEFT JOIN liquidacion_detalle_exp det ON det.ID_VALOR = val.ID_VALOR
            AND det.ESTADO_REGISTRO = 1
        LEFT JOIN liquidacion_dvalor gasto ON gasto.ID_VALOR = val.ID_VALOR
            AND gasto.ESTADO_REGISTRO = 1
        WHERE val.ESTADO_REGISTRO = 1
        GROUP BY val.ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    LEFT JOIN fruta_productor prod ON prod.ID_PRODUCTOR = exi.ID_PRODUCTOR
    LEFT JOIN fruta_mercado mer ON mer.ID_MERCADO = i.ID_MERCADO
    LEFT JOIN fruta_consignatario cons ON cons.ID_CONSIGNATARIO = i.ID_CONSIGNATARIO
    LEFT JOIN fruta_vespecies ves ON ves.ID_VESPECIES = exi.ID_VESPECIES
    LEFT JOIN estandar_eexportacion est ON est.ID_ESTANDAR = exi.ID_ESTANDAR
    LEFT JOIN fruta_tcalibre cal ON cal.ID_TCALIBRE = exi.ID_TCALIBRE
    LEFT JOIN principal_temporada temp ON temp.ID_TEMPORADA = i.ID_TEMPORADA
    LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
    WHERE i.ESTADO_REGISTRO = 1
) base
LEFT JOIN (
    SELECT
        i.ID_ICARGA,
        SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0)) AS TOTAL_NETO,
        SUM(IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) *
            IFNULL(COALESCE(
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = exi.ID_EXIEXPORTACION AND px.ID_PRODUCTOR = exi.ID_PRODUCTOR AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = exi.ID_ESTANDAR AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = exi.ID_VESPECIES AND px.ID_TCALIBRE = 0 AND px.ESTADO_REGISTRO = 1),
                (SELECT MAX(px.FOB_REAL) FROM liquidacion_detalle_exp px WHERE px.ID_VALOR = liq.ID_VALOR AND px.ID_EXIEXPORTACION = 0 AND px.ID_PRODUCTOR = 0 AND px.ID_ESTANDAR = 0 AND px.ID_VESPECIES = 0 AND px.ID_TCALIBRE = exi.ID_TCALIBRE AND px.ESTADO_REGISTRO = 1),
                CASE
                    WHEN IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0) > 0 THEN IFNULL(COALESCE(
                        (
                            SELECT MAX(idet.PRECIO_CAJA)
                            FROM exportadora_invoice inv
                            INNER JOIN exportadora_invoice_detalle idet ON idet.ID_INVOICE = inv.ID_INVOICE
                                AND idet.ESTADO_REGISTRO = 1
                            WHERE inv.ID_ICARGA = i.ID_ICARGA
                            AND inv.ESTADO_INVOICE = 'CONFIRMADA'
                            AND inv.ESTADO_REGISTRO = 1
                            AND idet.ID_ESTANDAR = exi.ID_ESTANDAR
                            AND idet.ID_TCALIBRE = exi.ID_TCALIBRE
                            AND (idet.ID_VESPECIES = 0 OR idet.ID_VESPECIES = exi.ID_VESPECIES)
                        ),
                        (
                            SELECT MAX(dic.PRECIO_US_DICARGA)
                            FROM fruta_dicarga dic
                            WHERE dic.ID_ICARGA = i.ID_ICARGA
                            AND dic.ID_ESTANDAR = exi.ID_ESTANDAR
                            AND dic.ID_TCALIBRE = exi.ID_TCALIBRE
                            AND (dic.ID_VESPECIES IS NULL OR dic.ID_VESPECIES = exi.ID_VESPECIES)
                            AND dic.ESTADO_REGISTRO = 1
                        )
                    ), 0) / (IFNULL(exi.KILOS_NETO_EXIEXPORTACION, 0) / IFNULL(exi.CANTIDAD_ENVASE_EXIEXPORTACION, 0))
                    ELSE 0
                END
            ), 0)
        ) AS TOTAL_VENTA
    FROM fruta_icarga i
    INNER JOIN fruta_despachoex dex ON dex.ID_ICARGA = i.ID_ICARGA
        AND dex.ESTADO_REGISTRO = 1
    INNER JOIN fruta_exiexportacion exi ON exi.ID_DESPACHOEX = dex.ID_DESPACHOEX
        AND exi.ESTADO_REGISTRO = 1
    LEFT JOIN (
        SELECT val.ID_ICARGA, MAX(val.ID_VALOR) AS ID_VALOR
        FROM liquidacion_valor val
        WHERE val.ESTADO_REGISTRO = 1
        GROUP BY val.ID_ICARGA
    ) liq ON liq.ID_ICARGA = i.ID_ICARGA
    WHERE i.ESTADO_REGISTRO = 1
    GROUP BY i.ID_ICARGA
) tot ON tot.ID_ICARGA = base.ID_ICARGA
LEFT JOIN (
    SELECT
        val.ID_ICARGA,
        SUM(CASE WHEN IFNULL(ti.TIPO_GASTO, 'GASTO') = 'COMISION' OR UPPER(ti.NOMBRE_TITEM) LIKE '%COMISI%' THEN IFNULL(dv.VALOR_DVALOR, 0) ELSE 0 END) AS TOTAL_COMISION,
        SUM(CASE
            WHEN IFNULL(ti.TIPO_GASTO, 'GASTO') = 'COMISION' OR UPPER(ti.NOMBRE_TITEM) LIKE '%COMISI%' THEN 0
            WHEN UPPER(IFNULL(tf.NOMBRE_TFLETE, '')) LIKE '%PREPAID%' AND IFNULL(ti.TIPO_GASTO, 'GASTO') = 'FLETE' THEN 0
            ELSE IFNULL(dv.VALOR_DVALOR, 0)
        END) AS TOTAL_GASTOS
    FROM liquidacion_valor val
    INNER JOIN fruta_icarga i ON i.ID_ICARGA = val.ID_ICARGA
    LEFT JOIN fruta_tflete tf ON tf.ID_TFLETE = i.ID_TFLETE
    INNER JOIN liquidacion_dvalor dv ON dv.ID_VALOR = val.ID_VALOR
        AND dv.ESTADO_REGISTRO = 1
    INNER JOIN liquidacion_titem ti ON ti.ID_TITEM = dv.ID_TITEM
        AND ti.ESTADO_REGISTRO = 1
        AND ti.TAITEM = 1
    WHERE val.ESTADO_REGISTRO = 1
    GROUP BY val.ID_ICARGA
) gasto ON gasto.ID_ICARGA = base.ID_ICARGA;
