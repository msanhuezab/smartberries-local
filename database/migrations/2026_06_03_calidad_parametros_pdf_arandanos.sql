CREATE TEMPORARY TABLE tmp_calidad_parametro_contexto AS
SELECT DISTINCT
    ID_EMPRESA,
    ID_TEMPORADA,
    ID_ESPECIES
FROM fruta_calidad_parametro
WHERE GRUPO_REPORTE IN ('DEFECTOS_CONDICION', 'DEFECTOS_CALIDAD')
AND ESTADO_REGISTRO = 1;

UPDATE fruta_calidad_parametro
SET ESTADO_REGISTRO = 0,
    MODIFICACION = SYSDATE()
WHERE GRUPO_REPORTE IN ('DEFECTOS_CONDICION', 'DEFECTOS_CALIDAD')
AND ESTADO_REGISTRO = 1;

INSERT INTO fruta_calidad_parametro (
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
)
SELECT
    'DEFECTO',
    'DEFECTOS_CALIDAD',
    'RECEPCION',
    CONCAT('REC-DCAL-PDF-', LPAD(item.ORDEN, 3, '0')),
    item.NOMBRE_PARAMETRO,
    item.UNIDAD_MEDIDA,
    item.VALOR_MINIMO,
    item.VALOR_MAXIMO,
    item.VALOR_REFERENCIA,
    0,
    item.ORDEN,
    ctx.ID_EMPRESA,
    ctx.ID_TEMPORADA,
    ctx.ID_ESPECIES,
    NULL,
    NULL,
    1
FROM tmp_calidad_parametro_contexto ctx
JOIN (
    SELECT 1 ORDEN, 'Open Appearance' NOMBRE_PARAMETRO, NULL UNIDAD_MEDIDA, NULL VALOR_MINIMO, NULL VALOR_MAXIMO, NULL VALOR_REFERENCIA
    UNION ALL SELECT 2, 'Dust', 'g', 0, 100, 'Score 1: 0 | 2: 0.01-2 | 3: 2.01-10 | 4: 10.01-30 | 5: 30.01-100'
    UNION ALL SELECT 3, 'Contamination', 'g', 0, 100, 'Score 1: 0 | 4: 0.01-10 | 5: 10.01-100'
    UNION ALL SELECT 4, 'Size', NULL, 1, 4, 'Score 1: 1-2 | 2: 3-4'
    UNION ALL SELECT 5, 'Consistency', NULL, 1, 2, 'Score 1: 1 | 2: 2'
    UNION ALL SELECT 6, 'Bloom', '%', 0, 100, 'Score 1: 80.01-100 | 2: 0-80'
    UNION ALL SELECT 7, 'Russet/Scars', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-15 | 4: 15.01-25 | 5: 25.01-100'
    UNION ALL SELECT 8, 'Attached Stems', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-25 | 4: 25.01-50 | 5: 50.01-100'
    UNION ALL SELECT 9, 'No Bloom', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-25 | 4: 25.01-50 | 5: 50.01-100'
    UNION ALL SELECT 10, 'Flower Remains', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-15 | 4: 15.01-25 | 5: 25.01-100'
    UNION ALL SELECT 11, 'Undersize', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-15 | 4: 15.01-25 | 5: 25.01-100'
    UNION ALL SELECT 12, 'Immature Red', 'g', 0, 100, 'Score 1: 0-4 | 2: 4.01-8 | 3: 8.01-25 | 4: 25.01-50 | 5: 50.01-100'
) item;

INSERT INTO fruta_calidad_parametro (
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
)
SELECT
    'DEFECTO',
    'DEFECTOS_CONDICION',
    'RECEPCION',
    CONCAT('REC-DCON-PDF-', LPAD(item.ORDEN, 3, '0')),
    item.NOMBRE_PARAMETRO,
    item.UNIDAD_MEDIDA,
    item.VALOR_MINIMO,
    item.VALOR_MAXIMO,
    item.VALOR_REFERENCIA,
    0,
    item.ORDEN,
    ctx.ID_EMPRESA,
    ctx.ID_TEMPORADA,
    ctx.ID_ESPECIES,
    NULL,
    NULL,
    1
FROM tmp_calidad_parametro_contexto ctx
JOIN (
    SELECT 1 ORDEN, 'Decay' NOMBRE_PARAMETRO, 'g' UNIDAD_MEDIDA, 0 VALOR_MINIMO, 100 VALOR_MAXIMO, 'Score 1: 0 | 2: 0.01-0.1 | 3: 0.11-0.6 | 4: 0.61-4 | 5: 4.01-100' VALOR_REFERENCIA
    UNION ALL SELECT 2, 'Decay Incidence', NULL, 1, 10, 'Score 2: 1 | 3: 2 | 4: 3-7 | 5: 8-10'
    UNION ALL SELECT 3, 'Mold', 'g', 0, 100, 'Score 1: 0 | 2: 0.01-1 | 3: 1.01-2 | 4: 2.01-4 | 5: 4.01-100'
    UNION ALL SELECT 4, 'Mold Incidence', NULL, 1, 10, 'Score 2: 1-2 | 3: 3-4 | 4: 5-7 | 5: 8-10'
    UNION ALL SELECT 5, 'Mold Type', NULL, NULL, NULL, NULL
    UNION ALL SELECT 6, 'Soft', 'g', 0, 100, 'Score 1: 0-2 | 2: 2.01-5 | 3: 5.01-9 | 4: 9.01-20 | 5: 20.01-100'
    UNION ALL SELECT 7, 'Sensitive', 'g', 0, 100, 'Score 1: 0-20 | 2: 20.01-35 | 3: 35.01-60 | 4: 60.01-80 | 5: 80.01-100'
    UNION ALL SELECT 8, 'Shriveling', 'g', 0, 100, 'Score 1: 0-2 | 2: 2.01-5 | 3: 5.01-10 | 4: 10.01-20 | 5: 20.01-100'
    UNION ALL SELECT 9, 'Broken Skin', 'g', 0, 100, 'Score 1: 0-1 | 2: 1.01-2 | 3: 2.01-4 | 4: 4.01-10 | 5: 10.01-100'
    UNION ALL SELECT 10, 'Wounds', 'g', 0, 100, 'Score 1: 0-1 | 2: 1.01-2 | 3: 2.01-4 | 4: 4.01-10 | 5: 10.01-100'
    UNION ALL SELECT 11, 'Crushed', 'g', 0, 100, 'Score 1: 0 | 2: 0.01-1 | 3: 1.01-2 | 4: 2.01-4 | 5: 4.01-100'
    UNION ALL SELECT 12, 'Wet Berries', 'g', 0, 100, 'Score 1: 0-1 | 2: 1.01-2 | 3: 2.01-4 | 4: 4.01-10 | 5: 10.01-100'
    UNION ALL SELECT 13, 'SO2 Damage', 'g', 0, 100, 'Score 1: 0-2 | 2: 2.01-4 | 3: 4.01-8 | 4: 8.01-20 | 5: 20.01-100'
) item;

DROP TEMPORARY TABLE tmp_calidad_parametro_contexto;
