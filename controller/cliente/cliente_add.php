<?php
require '../../php/Consulta.php';
require_once '../../php/funciones.php';
session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../../index.php');
}elseif($_SESSION['rol'] != '0' and ($_SESSION['rol'] != '1')){
    $datos = new Consulta();
    $datos->set_noautorizado();
    header('Location: ../login/no_autorizado.php');
}else{
    comprobarSesion();
    $rol = $_SESSION['rol'];
    $usuario  = $_SESSION['usuario'];
    $datos = new Consulta();
    $idUsuario= $datos->get_id();
    $nombreUsuario = $datos->get_nombreUsuario($idUsuario);
    $mensaje = null;
    $mensajedos = null;

    if ($rol == '0'){
        //Obtemos una lista de comerciales
        $consulta = "SELECT * FROM usuario WHERE rol = :rol";
        $parametros = array(":rol"=>'1');
        $datos = new Consulta();
        $comerciales = $datos->get_conDatos($consulta,$parametros);
    }

    if(isset($_POST['btnEnviar'])){
        $dni = strtoupper(trim($_POST['dni'])); //convertimos la cadena a mayusculas y quitamos los espacios en blanco delante y detras
        $nombre = strtoupper(trim($_POST['nombre']));
        $ciudad = strtoupper(trim($_POST['ciudad']));
        $provincia = strtoupper(trim($_POST['provincia']));
        $cp = trim($_POST['cp']);
        $telefono = trim($_POST['telefono']);
        $comentario = trim($_POST['comentario']);
        if(isset($_POST['comercial'])){
            $comercial = $_POST['comercial'];
        }
        $direccionP = strtoupper(trim($_POST['direccion']));
        $direccionTipo = $_POST['tipoD'];
        if(isset($_POST['tipo'])){
            $tipo = $_POST['tipo'];
        }

        $comprobarDNI = false;
        $comprobarVacios = false;
        $comprobarTelefono = false;
        $comprobarComentario = false;

        if($direccionTipo != ""){
            $direccion = $direccionTipo . $direccionP;
        }else{
            $direccion = $direccionP;
        }

        //Comprobamos no tengamos campos vacios
        if ($nombre == "" or $direccionP == "" or $telefono == "" or $dni == ""){
            $mensajeValidacionVacios = 'vacios';
        }else{
            $comprobarVacios = true;
        }

        //Comprobamos la longitud del dni
        if (strlen($dni) != 9){
            $mensajeValidacionDni = 'dniNoValido';
        }else{
            $comprobarDNI = true;
        }

        //Comprobamos la longitud del telefono
        if (strlen($telefono) < 9 ){
            $mensajeValidacionTelefono = 'telefonoNoValido';
        }else{
            $comprobarTelefono = true;
        }

        //Comprobamos que el comentario no venga vacio

        if ($comentario == ""){
            if($rol == '0'){
                if($tipo != ""){
                    $mensajeValidacionComentario = 'comentarioNoValido';
                }else{
                    $comprobarComentario = true;
                }
            }
            if($rol == '1'){
                $mensajeValidacionComentario = 'comentarioNoValido';
            }
        }else{
            $comprobarComentario = true;
        }



        if($rol == '0'){
            //Comprobamos que los datos son correctos antes de insertar el cliente
            if($comprobarVacios  AND $comprobarDNI AND $comprobarTelefono AND $comprobarComentario){
                if($comercial == ""){
                    $comercial = null;
                }

                try {
                    //Usamos una transacción para que en caso de error no ejecute ninguna sentencia.
                    $datos = new Consulta();
                    $datos->conexionDB->beginTransaction();

                    $cadena = "INSERT INTO cliente(dni,nombre,id_usuario,direccion,provincia,cp,ciudad,telefono,fecha_alta) VALUES (:dni,:nombre,:usuario,:direccion,:cp,:provincia,:ciudad,:telefono,:fAlta)";
                    $parametros = array(":dni"=>$dni,":nombre"=>$nombre,":usuario"=>$comercial,":direccion"=>$direccion,"ciudad"=>$ciudad,":telefono"=>$telefono,"provincia"=>$provincia,"cp"=>$cp,":fAlta"=> date("Y-m-d H:i:s"));
                    $resultados = $datos->get_sinDatos($cadena,$parametros);

                    if($tipo != ""){
                        $estado = 0;
                        if($tipo != 'averia'){
                            $estado = 1;
                        }

                        $consulta = "INSERT INTO incidencia(id_usuario,id_cliente,tipo,otros, estado) values (:usuario, :cliente, :tipo, :otros, :estado)";
                        $parametros = array(":usuario"=>$idUsuario,":cliente"=>$dni,":tipo"=>$tipo,":otros"=>$comentario,":estado"=>$estado);
                        $filasAfectadas = $datos->get_sinDatos($consulta,$parametros);
                    }

                    if ($resultados > 0){
                        $mensaje = 'Ok';
                        //$correo = enviarCorreo($tipo,$nombreUsuario,$nombre,$comentario);
                        header('Location: cliente_listar.php');
                    }

                    $datos->conexionDB->commit();
                } catch (PDOException $e) {
                    $datos->conexionDB->rollBack();
                    $mensaje = 'Error';
                    die('Error: ' . $e->getMessage());
                } finally {
                    $datos->conexionDB = null;
                }
            }
        }

        if($rol == '1'){
            if($comprobarVacios  AND $comprobarDNI AND $comprobarTelefono AND $comprobarComentario){

                try {
                    //Usamos una transacción para que en caso de error no ejecute ninguna sentencia.
                    $datos = new Consulta();
                    $datos->conexionDB->beginTransaction();

                    $cadena = "INSERT INTO cliente(dni,id_usuario,nombre,direccion,provincia,cp,ciudad,telefono,fecha_alta) VALUES (:dni,:usuario,:nombre,:direccion,:cp,:provincia,:ciudad,:telefono,:fAlta)";
                    $parametros = array(":dni"=>$dni,":usuario"=>$idUsuario,":nombre"=>$nombre,":direccion"=>$direccion,":ciudad"=>$ciudad,":telefono"=>$telefono,"provincia"=>$provincia,"cp"=>$cp,":fAlta"=> date("Y-m-d H:i:s"));
                    $resultados = $datos->get_sinDatos($cadena,$parametros);
                    if ($resultados > 0){
                        $mensaje = 'Ok';

                        $cadena = "INSERT INTO incidencia(id_usuario,id_cliente,otros,tipo,estado) values (:usuario,:cliente,:comentario,:tipo,:estado)";
                        $parametros = array(":usuario"=>$idUsuario,":cliente"=>$dni,":comentario"=>$comentario,":tipo"=>'instalacion',":estado"=>'1');
                        $resultados = $datos->get_sinDatos($cadena,$parametros);
                        if ($resultados > 0) {
                            $mensaje = 'Ok';
                            //$correo = enviarCorreo($tipo,$nombreUsuario,$nombre,$comentario);
                            header('Location: cliente_listar.php');
                        }
                    }else{
                        $mensajedos = 'Error';
                    }
                    $datos->conexionDB->commit();
                } catch (PDOException $e) {
                    $datos->conexionDB->rollBack();
                    $mensaje = 'Error';
                    die('Error: ' . $e->getMessage());
                } finally {
                    $datos->conexionDB = null;
                }
            }
        }
    }
    //si pulsa cancelar redirigimos a la pagina del comercial.
    if(isset($_POST['btnCancelar'])){
        header('Location: cliente_listar.php');;
    }

    if(isset($_SESSION['dniCliente'])){
        $dniB = $_SESSION['dniCliente'];
    }

    ////////////////////////Renderizado//////////////////////////
    require_once '../../vendor/autoload.php';
    $loader = new Twig_Loader_Filesystem('../../views');
    $twig = new Twig_Environment($loader, []);

    try{
        echo $twig->render('cliente/cliente_add.twig', compact(
            'usuario',
            'clientes',
            'mensaje',
            'mensajedos',
            'nombre',
            'dniB',
            'rol',
            'comerciales',
            'mensajeValidacionVacios',
            'mensajeValidacionDni',
            'mensajeValidacionTelefono',
            'mensajeValidacionComentario',
            'nombre',
            'direccionP',
            'ciudad',
            'provincia',
            'cp',
            'telefono'
        ));
    }catch (Exception $e){
        echo  'Excepción: ', $e->getMessage(), "\n";
    }
}