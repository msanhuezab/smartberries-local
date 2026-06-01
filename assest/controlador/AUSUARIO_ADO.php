<?php
//LLAMADA DE LOS ARCHIVOS NECESAROP PARA LA OPERACION DEL CONTROLADOR
//LLAMADA AL MODELO DE CLASE
include_once '../../assest/modelo/AUSUARIO.php';
//LLAMADA AL CONFIGURACION DE LA BASE DE DATOS
include_once '../../assest/config/BDCONFIG.php';


//INICIALIZAR VARIABLES
$HOST="";
$DBNAME="";
$USER="";
$PASS="";

//ESTRUCTURA DEL CONTROLADOR
class AUSUARIO_ADO {
    
    
    //ATRIBUTO
    private $conexion;
    
    //LLAMADO A LA BD Y CONFIGURAR PARAMETROS
    public function __CONSTRUCT()
    {
        try
        {
            $BDCONFIG = new BDCONFIG();
            $HOST = $BDCONFIG->__GET('HOST');
            $DBNAME = $BDCONFIG->__GET('DBNAME');
            $USER = $BDCONFIG->__GET('USER');
            $PASS = $BDCONFIG->__GET('PASS');

            
            $this->conexion = new PDO('mysql:host='.$HOST.';dbname='.$DBNAME, $USER ,$PASS);
            $this->conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
        }
        catch(Exception $e)
        {
            die($e->getMessage());
        }
    }
    
    
    
 //FUNCIONES BASICAS 
 //LISTAR TODO CON LIMITE DE 6 FILAS    
    public function listarAusuario(){
        try{
            
            $datos=$this->conexion->prepare("SELECT * FROM  usuario_ausuario  limit 8;	");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }
    //LISTAR TODO
    public function listarAusuarioCBX(){
        try{
            
            $datos=$this->conexion->prepare("SELECT * FROM  usuario_ausuario  ;	");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }
    
    public function listarAusuarioTodo($LIMIT = 1000){
        try{
            $LIMIT = max(100, min((int)$LIMIT, 5000));
            $datos=$this->conexion->prepare("SELECT
                                                    IFNULL(NULLIF(au.NUMERO_REGISTRO, 'NULL'), 'No Aplica') AS 'NUMERO_REGISTRO',
                                                    CASE au.TMODULO
                                                        WHEN 1 THEN 'Fruta'
                                                        WHEN 2 THEN 'Material'
                                                        WHEN 3 THEN 'Exportadora'
                                                        WHEN 4 THEN 'Estadistica'
                                                        ELSE 'Sin Datos'
                                                    END AS 'TMODULO',
                                                    CASE au.TOPERACION
                                                        WHEN 0 THEN 'Inicio Session'
                                                        WHEN 1 THEN 'Registro'
                                                        WHEN 2 THEN 'Modificacion'
                                                        WHEN 3 THEN 'Cerrar'
                                                        WHEN 4 THEN 'Deshabilitar'
                                                        WHEN 5 THEN 'Habilitar'
                                                        ELSE 'Sin Datos'
                                                    END AS 'TOPERACION',
                                                    au.MENSAJE,
                                                    au.INGRESO,
                                                    IFNULL(emp.NOMBRE_EMPRESA, 'No Aplica') AS 'EMPRESA',
                                                    IFNULL(pla.NOMBRE_PLANTA, 'No Aplica') AS 'PLANTA',
                                                    IFNULL(temp.NOMBRE_TEMPORADA, 'No Aplica') AS 'TEMPORADA'
                                            FROM usuario_ausuario au
                                            LEFT JOIN principal_empresa emp ON emp.ID_EMPRESA = au.ID_EMPRESA
                                            LEFT JOIN principal_planta pla ON pla.ID_PLANTA = au.ID_PLANTA
                                            LEFT JOIN principal_temporada temp ON temp.ID_TEMPORADA = au.ID_TEMPORADA
                                            ORDER BY au.INGRESO DESC
                                            LIMIT :limite;");
            $datos->bindValue(':limite', $LIMIT, PDO::PARAM_INT);
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            return $resultado;
            
            $datos=$this->conexion->prepare("SELECT
                                                    IF(NUMERO_REGISTRO IS NOT NULL,NUMERO_REGISTRO,
                                                        'No Aplica'
                                                        ) AS 'NUMERO_REGISTRO',
                                                    IF(TMODULO = 1,'Fruta',
                                                        IF(TMODULO = 2,'Material',
                                                            IF(TMODULO = 3,'Exportadora',
                                                                IF(TMODULO = 4,'Estadistica',
                                                                    'Sin Datos')
                                                                )
                                                            )
                                                        ) AS 'TMODULO',
                                                        IF(TOPERACION = 0,'Inicio Session',
                                                            IF(TOPERACION = 1,'Registro',
                                                                IF(TOPERACION = 2,'Modificación',
                                                                    IF(TOPERACION = 3,'Cerrar',
                                                                        IF(TOPERACION = 4, 'Deshabilitar',
                                                                            IF(TOPERACION = 5,'Habilitar',
                                                                                'Sin Datos')
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            ) AS 'TOPERACION',
                                                        MENSAJE,
                                                        INGRESO,
                                                        IF(ID_EMPRESA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_EMPRESA
                                                            FROM principal_empresa
                                                            WHERE ID_EMPRESA = usuario_ausuario.ID_EMPRESA
                                                            ),'No Aplica'
                                                        ) AS 'EMPRESA',                                            
                                                        IF(ID_PLANTA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_PLANTA
                                                            FROM principal_planta
                                                            WHERE ID_PLANTA = usuario_ausuario.ID_PLANTA
                                                            ),'No Aplica'
                                                        ) AS 'PLANTA',
                                                        IF(ID_TEMPORADA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_TEMPORADA
                                                            FROM principal_temporada
                                                            WHERE ID_TEMPORADA = usuario_ausuario.ID_TEMPORADA
                                                            ),'No Aplica'
                                                        ) AS 'TEMPORADA'
                                            FROM usuario_ausuario
                                            ORDER BY ID_AUSUARIO DESC;
                                            
                                            ;");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }



    //VER LA INFORMACION RELACIONADA EN BASE AL ID INGRESADO A LA FUNCION
    public function verAusuario($ID){
        try{
            
            $datos=$this->conexion->prepare("SELECT * FROM  usuario_ausuario  WHERE  ID_AUSUARIO = '".$ID."';");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }
    
    public function verAusuarioTodo($IDUSUARIO){
        try{
            
            $datos=$this->conexion->prepare("SELECT
                                                    IF(NUMERO_REGISTRO IS NOT NULL,NUMERO_REGISTRO,
                                                        'No Aplica'
                                                        ) AS 'NUMERO_REGISTRO',
                                                    IF(TMODULO = 1,'Fruta',
                                                        IF(TMODULO = 2,'Material',
                                                            IF(TMODULO = 3,'Exportadora',
                                                                IF(TMODULO = 4,'Estadistica',
                                                                    'Sin Datos')
                                                                )
                                                            )
                                                        ) AS 'TMODULO',
                                                        IF(TOPERACION = 0,'Inicio Session',
                                                            IF(TOPERACION = 1,'Registro',
                                                                IF(TOPERACION = 2,'Modificación',
                                                                    IF(TOPERACION = 3,'Cerrar',
                                                                        IF(TOPERACION = 4, 'Deshabilitar',
                                                                            IF(TOPERACION = 5,'Habilitar',
                                                                                'Sin Datos')
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            ) AS 'TOPERACION',
                                                        MENSAJE,
                                                        INGRESO,
                                                        IF(ID_EMPRESA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_EMPRESA
                                                            FROM principal_empresa
                                                            WHERE ID_EMPRESA = usuario_ausuario.ID_EMPRESA
                                                            ),'No Aplica'
                                                        ) AS 'EMPRESA',                                            
                                                        IF(ID_PLANTA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_PLANTA
                                                            FROM principal_planta
                                                            WHERE ID_PLANTA = usuario_ausuario.ID_PLANTA
                                                            ),'No Aplica'
                                                        ) AS 'PLANTA',
                                                        IF(ID_TEMPORADA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_TEMPORADA
                                                            FROM principal_temporada
                                                            WHERE ID_TEMPORADA = usuario_ausuario.ID_TEMPORADA
                                                            ),'No Aplica'
                                                        ) AS 'TEMPORADA'
                                            FROM usuario_ausuario
                                            WHERE ID_USUARIO = '".$IDUSUARIO."' 
                                            ORDER BY ID_AUSUARIO DESC
                                            
                                            ;");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }
    
    public function verAusuarioLimit5($IDUSUARIO){
        try{
            
            $datos=$this->conexion->prepare("SELECT
                                                    IF(NUMERO_REGISTRO IS NOT NULL,NUMERO_REGISTRO,
                                                        'No Aplica'
                                                        ) AS 'NUMERO_REGISTRO',
                                                    IF(TMODULO = 1,'Fruta',
                                                        IF(TMODULO = 2,'Material',
                                                            IF(TMODULO = 3,'Exportadora',
                                                                IF(TMODULO = 4,'Estadistica',
                                                                    'Sin Datos')
                                                                )
                                                            )
                                                        ) AS 'TMODULO',
                                                        IF(TOPERACION = 0,'Inicio Session',
                                                            IF(TOPERACION = 1,'Registro',
                                                                IF(TOPERACION = 2,'Modificación',
                                                                    IF(TOPERACION = 3,'Cerrar',
                                                                        IF(TOPERACION = 4, 'Deshabilitar',
                                                                            IF(TOPERACION = 5,'Habilitar',
                                                                                'Sin Datos')
                                                                            )
                                                                        )
                                                                    )
                                                                )
                                                            ) AS 'TOPERACION',
                                                        MENSAJE,
                                                        INGRESO,
                                                        IF(ID_EMPRESA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_EMPRESA
                                                            FROM principal_empresa
                                                            WHERE ID_EMPRESA = usuario_ausuario.ID_EMPRESA
                                                            ),'No Aplica'
                                                        ) AS 'EMPRESA',                                            
                                                        IF(ID_PLANTA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_PLANTA
                                                            FROM principal_planta
                                                            WHERE ID_PLANTA = usuario_ausuario.ID_PLANTA
                                                            ),'No Aplica'
                                                        ) AS 'PLANTA',
                                                        IF(ID_TEMPORADA IS NOT NULL,
                                                            (
                                                            SELECT NOMBRE_TEMPORADA
                                                            FROM principal_temporada
                                                            WHERE ID_TEMPORADA = usuario_ausuario.ID_TEMPORADA
                                                            ),'No Aplica'
                                                        ) AS 'TEMPORADA'
                                            FROM usuario_ausuario
                                            WHERE ID_USUARIO = '".$IDUSUARIO."' 
                                            ORDER BY ID_AUSUARIO DESC
                                            LIMIT 5                                            
                                            ;");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }

  
    
    //REGISTRO DE UNA NUEVA FILA    

    public function agregarAusuario(AUSUARIO $AUSUARIO){
        try{
            
            IF ($AUSUARIO->__GET('ID_EMPRESA') == NULL) {
                $AUSUARIO->__SET('ID_EMPRESA', NULL);
            }
            IF ($AUSUARIO->__GET('ID_PLANTA') == NULL) {
                $AUSUARIO->__SET('ID_PLANTA', NULL);
            }
            IF ($AUSUARIO->__GET('ID_TEMPORADA') == NULL) {
                $AUSUARIO->__SET('ID_TEMPORADA', NULL);
            }


            
            $query=
            "INSERT INTO  usuario_ausuario  (   
                                                NUMERO_REGISTRO , 
                                                TMODULO ,
                                                TOPERACION ,

                                                MENSAJE ,
                                                TABLA ,
                                                ID_REGISTRO ,
                                                
                                                ID_USUARIO ,
                                                ID_EMPRESA ,
                                                ID_PLANTA ,
                                                ID_TEMPORADA ,

                                                INGRESO 
                                            ) VALUES
	       	( ?, ?, ?,   ?, ?, ?,   ?, ?, ?, ?, SYSDATE() );";
            $this->conexion->prepare($query)
            ->execute(
                array(                    
                    $AUSUARIO->__GET('NUMERO_REGISTRO')  ,
                    $AUSUARIO->__GET('TMODULO') ,
                    $AUSUARIO->__GET('TOPERACION') ,

                    $AUSUARIO->__GET('MENSAJE') ,
                    $AUSUARIO->__GET('TABLA') ,
                    $AUSUARIO->__GET('ID_REGISTRO') ,

                    $AUSUARIO->__GET('ID_USUARIO')  ,
                    $AUSUARIO->__GET('ID_EMPRESA') ,
                    $AUSUARIO->__GET('ID_PLANTA') ,
                    $AUSUARIO->__GET('ID_TEMPORADA')              
                )
                
                );
            
        }catch(Exception $e){
            die($e->getMessage());
        }
    } 
    

    public function agregarAusuario2($NUMERO,$TMODULO,$TOPERACION, $MENSAJE, $TABLA, $REGISTRO, $USUARIO, $EMPRESA, $PLANTA, $TEMPORADA){
        try{                   
            $query=
                                            "INSERT INTO  usuario_ausuario  
                                            (   
                                                NUMERO_REGISTRO , 
                                                TMODULO ,
                                                TOPERACION ,

                                                MENSAJE ,
                                                TABLA ,
                                                ID_REGISTRO ,

                                                ID_USUARIO ,
                                                ID_EMPRESA ,
                                                ID_PLANTA ,
                                                ID_TEMPORADA ,                                                

                                                INGRESO 
                                            ) VALUES
	       	                                (  '".$NUMERO."',  '".$TMODULO."',  '".$TOPERACION."',   '".$MENSAJE."',  '".$TABLA."', ".$REGISTRO.",  ".$USUARIO.", ".$EMPRESA.", ".$PLANTA.", ".$TEMPORADA.",    SYSDATE()  );";

                            //echo $query;

                                         
            $this->conexion->prepare($query)
            ->execute( );
            
        }catch(Exception $e){
            die($e->getMessage());
        }
    } 
    //FUNCIONES ESPECIALIZADAS 
    //BUSCADE DE LA EMPRESAS ASOACIADAS A USUARIOS

    //VER LA INFORMACION RELACIONADA EN BASE AL ID INGRESADO A LA FUNCION
    public function buscarAusuarioPorNombreUsuario($NOMBREUSUARIO){
        try{
            
            $datos=$this->conexion->prepare("SELECT * 
                                             FROM  usuario_ausuario 
                                             WHERE  ID_USUARIO = '".$NOMBREUSUARIO."';");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }
        
    }

    

    //VER LA INFORMACION RELACIONADA EN BASE AL ID INGRESADO A LA FUNCION
    public function buscarAusuarioPorNombreUsuarioUltimasCinco($NOMBREUSUARIO){
        try{

            $datos=$this->conexion->prepare("SELECT *
                                            FROM  usuario_ausuario
                                            WHERE  ID_USUARIO = '".$NOMBREUSUARIO."' 
                                            ORDER BY  FECHA_AUSUARIO  DESC LIMIT 5 ; ");
            $datos->execute();
            $resultado = $datos->fetchAll();
            $datos=null;
            
            //	print_r($resultado);
            //	var_dump($resultado);
            
            
            return $resultado;
        }catch(Exception $e){
            die($e->getMessage());
        }

    }

    public function listarUltimosCambiosFolioMp($EMPRESA, $PLANTA, $TEMPORADA, $LIMIT = 10)
    {
        try {
            $sql = "SELECT
                        au.MENSAJE,
                        au.INGRESO,
                        u.NOMBRE_USUARIO AS USUARIO,
                        CONCAT(IFNULL(u.PNOMBRE_USUARIO, ''), ' ', IFNULL(u.SNOMBRE_USUARIO, ''), ' ', IFNULL(u.PAPELLIDO_USUARIO, ''), ' ', IFNULL(u.SAPELLIDO_USUARIO, '')) AS NOMBRE_COMPLETO,
                        e.NOMBRE_EMPRESA,
                        p.NOMBRE_PLANTA,
                        t.NOMBRE_TEMPORADA
                    FROM usuario_ausuario au
                    LEFT JOIN usuario_usuario u ON u.ID_USUARIO = au.ID_USUARIO
                    LEFT JOIN principal_empresa e ON e.ID_EMPRESA = au.ID_EMPRESA
                    LEFT JOIN principal_planta p ON p.ID_PLANTA = au.ID_PLANTA
                    LEFT JOIN principal_temporada t ON t.ID_TEMPORADA = au.ID_TEMPORADA
                    WHERE au.TABLA = 'fruta_eximateriaprima'
                      AND au.ID_EMPRESA = ?
                      AND au.ID_PLANTA = ?
                      AND au.ID_TEMPORADA = ?
                      AND (au.MENSAJE LIKE '%folio de materia prima%')
                    ORDER BY au.INGRESO DESC
                    LIMIT ?;";
            $datos = $this->conexion->prepare($sql);
            $datos->bindParam(1, $EMPRESA, PDO::PARAM_INT);
            $datos->bindParam(2, $PLANTA, PDO::PARAM_INT);
            $datos->bindParam(3, $TEMPORADA, PDO::PARAM_INT);
            $datos->bindParam(4, $LIMIT, PDO::PARAM_INT);
            $datos->execute();
            $resultado = $datos->fetchAll(PDO::FETCH_ASSOC);
            $datos=null;

            return $resultado;
        } catch (Exception $e) {
            die($e->getMessage());
        }
    }
}
?>
