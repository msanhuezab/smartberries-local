<?php

$CRON_PT_CONFIG_PATH = dirname(__DIR__, 2) . '/data/config_cron_pt.json';

if (!function_exists('obtenerConfigCronPt')) {
    function obtenerConfigCronPt($ruta)
    {
        $config = [
            'habilitado' => true,
            'actualizado_en' => null,
            'hora' => '',
            'dias' => [],
            'correos' => '',
            'empresas' => [],
            'plantas' => [],
            'usuarios' => []
        ];

        if (file_exists($ruta)) {
            $data = json_decode(file_get_contents($ruta), true);
            if (is_array($data)) {
                $config = array_merge($config, $data);
            }
        }

        $config['habilitado'] = isset($config['habilitado']) ? (bool) $config['habilitado'] : true;
        $config['dias'] = isset($config['dias']) && is_array($config['dias']) ? array_values(array_unique($config['dias'])) : [];
        $config['empresas'] = isset($config['empresas']) && is_array($config['empresas']) ? array_values(array_unique($config['empresas'])) : [];
        $config['plantas'] = isset($config['plantas']) && is_array($config['plantas']) ? array_values(array_unique($config['plantas'])) : [];
        $config['usuarios'] = isset($config['usuarios']) && is_array($config['usuarios']) ? array_values(array_unique($config['usuarios'])) : [];

        return $config;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$CONFIG_CRON_PT = obtenerConfigCronPt($CRON_PT_CONFIG_PATH);

if (!isset($_SESSION['CRON_PT_INICIADO'])) {
    $_SESSION['CRON_PT_INICIADO'] = false;
}

if ($CONFIG_CRON_PT['habilitado']) {
    if (!$_SESSION['CRON_PT_INICIADO']) {
        $_SESSION['CRON_PT_INICIADO'] = true;
        $_SESSION['CRON_PT_INICIO'] = time();
    }
} else {
    $_SESSION['CRON_PT_INICIADO'] = false;
}

$_SESSION['CRON_PT_CONFIG'] = $CONFIG_CRON_PT;
