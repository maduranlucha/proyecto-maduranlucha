<?php
require '../../php/Consulta.php';
session_start();
//var_dump($_POST);
//var_dump($_SESSION);

if(!isset($_SESSION['usuario'])){
    header('Location: ../../index.php');
}elseif($_SESSION['rol'] != '0' and ($_SESSION['rol'] != '1')){
    $datos = new Consulta();
    $datos->set_noautorizado();
    header('Location: ../login/no_autorizado.php');
}else{


    $rol = $_SESSION['rol'];
    $usuario  = $_SESSION['usuario'];
    $datos = new Consulta();
    $idUsuario = $datos->get_id();
    $_SESSION['dniCliente'] ='';

    $clientes = [];
    $mensaje = null;


    if($rol == '0'){
        //consulta para listar todos los clientes
        $consulta = "SELECT cliente.*, usuario.usuario as comercial, usuario.nombre as nombreComercial FROM cliente LEFT OUTER JOIN usuario ON  cliente.id_usuario = usuario.dni";
        $datos = new Consulta();
        $parametros = array();
        $clientes = $datos->get_conDatos($consulta,$parametros);
        if($clientes){
            $mensaje = 'Ok';
        }else{
            $mensaje = 'error';
        }
    }elseif($rol == '1'){
        //Consulta para obtener los datos de los clientes del comerial
        $consulta = "SELECT dni,nombre,direccion,ciudad,telefono FROM cliente WHERE id_usuario= :usuario AND eliminado = :eliminado";
        $parametros = array(":usuario"=>$idUsuario,":eliminado"=> 'No');
        $datos = new Consulta();
        $clientes = $datos->get_conDatos($consulta,$parametros);

        if($clientes){
            $mensaje = 'Ok';
        }else{
            $mensaje = 'error';
        }
    }

    if(isset($_GET['addCliente'])){ /***/
        header('Location: cliente_buscar.php');
    }

    ////////////////////////Renderizado//////////////////////////
    require_once '../../vendor/autoload.php';
    $loader = new Twig_Loader_Filesystem('../../views');
    $twig = new Twig_Environment($loader, []);

    try{
        echo $twig->render('cliente/cliente_listar.twig', compact(
            'usuario',
            'clientes',
            'mensaje',
            'rol'
        ));
    }catch (Exception $e){
        echo  'Excepción: ', $e->getMessage(), "\n";
    }
}