<?php
require '../../php/Consulta.php';
require '../../php/funciones.php';
session_start();

if(!isset($_SESSION['usuario'])){
    header('Location: ../../index.php');
}elseif(($_SESSION['rol'] != '4') and ($_SESSION['rol'] != '0')){
    $datos = new Consulta();
    $datos->set_noautorizado();
    header('Location: ../../index.php');
}else{
    comprobarSesion();
    $rol = $_SESSION['rol'];
    $usuario  = $_SESSION['usuario'];
    $datos = new Consulta();
    $idUsuario = $datos->get_id();
    $idIncidencia = $_GET['Id'];
    $arraySolucion = [];

    if(isset($_SESSION['tipo'])){
        $tipo = $_SESSION['tipo'];
    }

    if(isset($_SESSION['dni'])){
        $dni= $_SESSION['dni'];
    }

    //Accion al pulsar el boton aceptar
    if(isset($_POST['confirmarFinalizar'])){

        $comentario = trim($_POST['comentario']);
        $solucion = $_POST['solucion'];

        if($solucion == 'otros'){
            array_push($arraySolucion, $comentario);
        }else{
            array_push($arraySolucion, $solucion);
        }

        $listaSolucion = json_encode($arraySolucion,JSON_UNESCAPED_UNICODE);

        //comprobamos el estado de la solucion
        $consulta = "SELECT estado,tecnico FROM incidencia WHERE id_incidencia = :idIncidencia";
        $parametros = array(":idIncidencia"=>$idIncidencia);
        $datos = new Consulta();
        $r= $datos->get_conDatosUnica($consulta,$parametros);
        $tecnicoI= $r['tecnico'];

        var_dump($listaSolucion);

        try {
            $datos = new Consulta();
            //Usamos una transaccion para que en caso de error no ejecute ninguna sentencia.
            $datos->conexionDB->beginTransaction();

            if($tecnicoI){
                //Consulta para modificar el estado de la indicencia
                $sentencia = "UPDATE incidencia SET estado= :estado,  fecha_resolucion = :fechaRes,llamada_obligatoria = :llamada, disponible = NULL, urgente =:urgente WHERE id_incidencia = :incidencia";
                $parametros = (array(":estado"=>'3',":fechaRes"=> date("Y-m-d H:i:s"),":llamada"=>'Si',":incidencia"=>$idIncidencia,":urgente"=>'No'));
                $datos->get_sinDatos($sentencia,$parametros);
            }else{
                //Consulta para modificar el estado de la indicencia
                $sentencia = "UPDATE incidencia SET estado= :estado, tecnico =:tecnico,fecha_inicio = :fechaInicio, fecha_resolucion = :fechaRes,llamada_obligatoria = :llamada, disponible = NULL, urgente =:urgente WHERE id_incidencia = :incidencia";
                $parametros = (array(":estado"=>'3',":tecnico"=>$idUsuario,":fechaRes"=> date("Y-m-d H:i:s"),":fechaInicio"=>date("Y-m-d H:i:s"),":llamada"=>'Si',":incidencia"=>$idIncidencia,":urgente"=>'No'));
                $datos->get_sinDatos($sentencia,$parametros);
            }

            //consulta para insertar la solucion
            $sentencia = "INSERT INTO solucion (id_incidencia, solucion,tecnico) VALUES (:incidencia, :solucion, :tecnico)";
            $parametros = array(":incidencia" => $idIncidencia, ":solucion" => $listaSolucion, ":tecnico" => $idUsuario);
            $datos->get_sinDatos($sentencia, $parametros);

            $datos->conexionDB->commit();

            if($rol == '4'){
                //Para el controlador le reridigimos a la pagina de las incidencias
                header("Location: ../cliente/cliente_incidencias.php");

            }elseif($rol == '0'){
                if(isset($_SESSION['dni']) and $_SESSION['dni'] != ""){
                    header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo."&dni=".$dni);
                }else{
                    header("Location: ../cliente/cliente_incidencias.php?tipo=".$tipo);
                }
            }
        } catch (PDOException $e) {
            $datos->conexionDB->rollBack();
            die('Error: ' . $e->getMessage());
        } finally {
            $datos->conexionDB = null;
        }
    }

    ////////////////////////Renderizado//////////////////////////
    require_once '../../vendor/autoload.php';
    $loader = new Twig_Loader_Filesystem('../../views');
    $twig = new Twig_Environment($loader, []);

    try{
        echo $twig->render('controlador/controlador_confirmar_aceptar.twig', compact(
            'mensaje',
            'error',
            'usuario',
            'rol'
        ));
    }catch (Exception $e){
        echo  'Excepción: ', $e->getMessage(), "\n";
    }
}