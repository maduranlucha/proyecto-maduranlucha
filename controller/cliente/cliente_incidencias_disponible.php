<?php
require '../../php/Consulta.php';
session_start();
//var_dump($_POST);
//var_dump($_SESSION);

if(!isset($_SESSION['usuario'])){
    header('Location: ../../index.php');
}elseif($_SESSION['rol'] != '0' and ($_SESSION['rol'] != '1')){ //posibilidad de quitar al comercial de aqui
    $datos = new Consulta();
    $datos->set_noautorizado();
    header('Location: ../login/no_autorizado.php');
}else{

    $mensaje = null;
    $rol = $_SESSION['rol'];
    $usuario  = $_SESSION['usuario'];
    $idIncidencia =  $_GET['Id'];

    if(isset($_SESSION['tipo'])){
        $tipo = $_SESSION['tipo'];
    }

    if(isset($_SESSION['Id'])){
        $id= $_SESSION['Id'];
    }

    if(isset($_POST['btnModificar'])){

        $fecha = $_POST['disponible'];

        $consulta = "UPDATE incidencia SET disponible = :fecha WHERE id_incidencia = :incidencia";
        $parametros = array(":fecha"=>$fecha,":incidencia"=> $idIncidencia);
        $datos = new Consulta();
        $datosIncidencia = $datos->get_sinDatos($consulta,$parametros);

        if($datosIncidencia > 0){
            $mensaje = 'ok';
            if(isset($_SESSION['Id']) and $_SESSION['Id'] != ""){
                header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo."&Id=".$id);
            }else{
                header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo);
            }
        }else{
            $mensaje = 'error';
        }
    }

    if(isset($_POST['btnCancelar'])){

        if(isset($_SESSION['Id']) and $_SESSION['Id'] != ""){
            header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo."&Id=".$id);
        }else{
            header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo);
        }

    }

    ////////////////////////Renderizado//////////////////////////
    require_once '../../vendor/autoload.php';
    $loader = new Twig_Loader_Filesystem('../../views');
    $twig = new Twig_Environment($loader, []);

    try{
        echo $twig->render('cliente/cliente_incidencias_disponible.twig', compact(
            'mensaje',
            'usuario',
            'rol'
        ));
    }catch (Exception $e){
        echo  'Excepción: ', $e->getMessage(), "\n";
    }
}