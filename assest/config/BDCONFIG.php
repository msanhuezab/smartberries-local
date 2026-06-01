<?php
class BDCONFIG {

    private $HOST;
    private $PORT;
    private $USER;
    private $PASS;
    private $DBNAME;

    public function __construct()
    {
        $this->HOST = "127.0.0.1";
        $this->PORT = "3356";
        $this->USER = "root";
        $this->PASS = "";
        $this->DBNAME = "smartberry_produccion";

    }

    public function __GET($k) {
        if ($k === 'HOST' && !empty($this->PORT)) {
            return $this->HOST . ';port=' . $this->PORT;
        }
        return $this->$k;
    }

    public function __SET($k, $v) {
        $this->$k = $v;
    }

    public static function conectar() {
        try {
            $config = new self();
            $link = new PDO("mysql:host={$config->HOST};port={$config->PORT};dbname={$config->DBNAME};charset=utf8", $config->USER, $config->PASS);
            $link->exec("set names utf8");
            $link->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            return $link;
        } catch (PDOException $e) {
            // Maneja el error de conexión aquí, si es necesario
            echo 'Error de conexión: ' . $e->getMessage();
            return null;
        }
    }
}
