<?php
namespace App;

  use DB;

  class User {

    public function findUser($id){
      return null;
    }

    public function findUserWithPassword($username, $password){
      $res = DB::select("SELECT * FROM sim_usuarios WHERE usuario = '$username' AND password = '$password'");
      return $res;
    }


    public function getDashboardData($userid){
      $id_usuario = $userid;
      //$id_usuario = 1;
      
      //******** algunas validaciones 
      if(!is_numeric($id_usuario)){
        exit; //no es usuario
      }
      else{
        $datosUsuario = DB::select( 'SELECT * FROM sim_usuarios WHERE id = "'.$id_usuario.'" ');
        if(!is_array($datosUsuario)){
          exit; //no es usuario
        }
      }
      
      
      //********** Obtiene los datos de puntaje, nivel, etc en que va el usuario y en caso que no existan los registros se crean con valor 0 (cero)
      $tipoPuntaje = array("nivel_dialogo","puntaje","nivel_audio");
      $arrRes = array();
      foreach($tipoPuntaje as $tipoPuntajeAux){
        $res = DB::select( 'SELECT * FROM sim_data_usuario WHERE sim_usuarios_id = '.$id_usuario.' and tipo = "'.$tipoPuntajeAux.'"' );
        if(!is_array($res)){
          DB::insert( 'INSERT INTO sim_data_usuario SET  `tipo` = "'.$tipoPuntajeAux.'", `valor`= "0", `sim_usuarios_id`= "'.$id_usuario.'"' );
          $res = DB::select( 'SELECT * FROM sim_data_usuario WHERE sim_usuarios_id = '.$id_usuario.' and tipo = "'.$tipoPuntajeAux.'"' );
          $arrRes[] = $res;
          //$id = DB::getInsertID();       
        }
        else{
          $arrRes[] = $res;
        }
      }
      
      $datos["data_usuario"] = $arrRes; 
      
      //********** preguntamos si ya tiene las rondas creadas, en caso que no las tenga las creamos
      $rondas = DB::select('SELECT * FROM sim_ronda_usuario WHERE sim_usuarios_id = '.$id_usuario.' order by sim_rondas_id desc limit 0,1');
      if(!is_array($rondas)){//si no es arreglo, es primera vez que entró. creamos las rondas
        
        $resGeneraRonda = generaRonda($datosUsuario); 
        foreach($resGeneraRonda as $indice=>$resGeneraRondaAux){
          DB::insert('INSERT INTO `sim_ronda_usuario` (`sim_rondas_id`, `sim_usuarios_id`, `sim_preguntas_id`, `completada`) VALUES ('.($indice+1).', '.$id_usuario.', "'.$resGeneraRondaAux.'", "no");' );
        }
        $datos["ronda_actual"] = 1; //la ronda actual sería la 1
      }
      else{
        //ronda actual
        $actual = DB::select( 'SELECT * FROM sim_ronda_usuario WHERE completada = "no" AND sim_usuarios_id = '.$id_usuario.' ORDER BY sim_rondas_id ASC LIMIT 0,1' );
        $datos["ronda_actual"] = $actual["sim_rondas_id"];  
      }
      
      //entrgegamos los ejercicios de la ronda desordenados
      $rondas = DB::select('SELECT * FROM sim_ronda_usuario WHERE sim_usuarios_id = '.$id_usuario.' order by sim_rondas_id');
      //arreglo($rondas);
      
      $dialogos = DB::select('SELECT * FROM sim_dialogos where disponible = 1');
      foreach($dialogos as $dialogosAux){
        $arrDialogosImg[$dialogosAux["id"]] = $dialogosAux["imagen"];
        $arrDialogosTD[$dialogosAux["id"]] = $dialogosAux["sim_tipo_dialogo_id"];   
      }
      
      //foreach($rondas as $rondasAux){
        $rondas = generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 4);
        $rondas = generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 7);
        $rondas = generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 10);
        $rondas = generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 11);
      //}
      //arreglo($rondas);
      

      $nuevaRonda = desordenaRonda($rondas);
      //arreglo($nuevaRonda);
      
      //obtenemos las imagenes correspondientes
      //$arrLetras = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","NN","O","P","Q","R","S","T");
      
      //$dialogos = DB::select('SELECT * FROM sim_dialogos where disponible = 1');
      // foreach($dialogos as $dialogosAux){
        // $arrDialogosImg[$dialogosAux["id"]] = $dialogosAux["imagen"];
      // }
      //arreglo($arrDialogosImg);
      //foreach($dialogos as $dialogosAux){
        //$imgN = "/app/uploads/fondos/ejercitacion/".$arrLetras[$dialogosAux["sim_tipo_dialogo_id"]-1]."/".$dialogosAux["imagen"];
        //DB::exec("update sim_dialogos set imagen ='".$imgN."' where id = ".$dialogosAux["id"]);
        //$arrDialogosImg[$dialogosAux["id"]] = "/app/uploads/fondos/ejercitacion/".$arrLetras[$dialogosAux["sim_tipo_dialogo_id"]-1]."/".$dialogosAux["imagen"];
      //}
      
      foreach($nuevaRonda as $i => $nuevaRondaAux){
        $rondaImgAux = "";
        if($nuevaRondaAux[0]!=""){
          foreach($nuevaRondaAux as $indice){
            //$rondaImgAux[$indice] = $arrDialogosImg[$indice];
            $rondaImgAux[] = $arrDialogosImg[$indice];
          }
          $rondaImg[$i] = $rondaImgAux;
        }
        else{
          $rondaImg[$i] = array();
        }
        
      }

      $datos["rondas"] = $rondaImg;   
      //arreglo($datos);
      return $datos;
    }


  }