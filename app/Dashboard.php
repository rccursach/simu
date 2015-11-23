<?php
namespace App;
  use DB;

  class Dashboard {

    public function getDashboardData($userid) {
      $id_usuario = $userid;
      $datosUsuario = DB::select( 'SELECT * FROM sim_usuarios WHERE id = "'.$id_usuario.'" ');
      
      if(!is_array($datosUsuario) || !is_numeric($id_usuario) ){
        return null;
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
      else {
        //ronda actual
        $actual = DB::select( 'SELECT * FROM sim_ronda_usuario WHERE completada = "no" AND sim_usuarios_id = '.$id_usuario.' ORDER BY sim_rondas_id ASC LIMIT 0,1' )[0];
        $datos["ronda_actual"] = $actual->sim_rondas_id;
      }
      
      //entrgegamos los ejercicios de la ronda desordenados
      $rondas = DB::select('SELECT * FROM sim_ronda_usuario WHERE sim_usuarios_id = '.$id_usuario.' order by sim_rondas_id');
      //arreglo($rondas);
      
      $dialogos = DB::select('SELECT * FROM sim_dialogos where disponible = 1');

      foreach($dialogos as $dialogosAux) {
        $arrDialogosImg[$dialogosAux->id] = $dialogosAux->imagen;
        $arrDialogosTD[$dialogosAux->id] = $dialogosAux->sim_tipo_dialogo_id;
      }
      

      $rondas = $this->generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 4);
      $rondas = $this->generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 7);
      $rondas = $this->generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 10);
      $rondas = $this->generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda = 11);

      $nuevaRonda = $this->desordenaRonda($rondas);

      
      foreach($nuevaRonda as $i => $nuevaRondaAux) {
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
      return $datos;
    }

    private function desordenaRonda($rondas){
      if(is_array($rondas)){
        foreach($rondas as $rondasAux){
          $nuevaRondaAux[$rondasAux->sim_rondas_id] = explode(",",$rondasAux->sim_preguntas_id);
        }
        
        foreach($nuevaRondaAux as $index=>$rondasAux){
          shuffle($rondasAux);
          $nuevaRonda[$index] = $rondasAux;
        }
        
        return $nuevaRonda;
      }
    }   
        
    
    private function generaRonda($datosUsuario){

      //if($tipo == 1){
        //seleccionamos los dialogos correspondientes a la asignatura del usuario. como resultado obtenemos 21 dialogos de la misma carrera
        $dialogos = DB::select('SELECT * FROM sim_dialogos WHERE sim_grupos_id = '.$datosUsuario["sim_grupos_id"].' and disponible = 1');
        
        //ordenamos el arreglo de manera aleaorea
        shuffle($dialogos);
        
        //generamos 3 arreglos de 7 ejercicios c/u
        $arregloFinal = array_chunk($dialogos, 7);
        
        foreach($arregloFinal as $arregloFinalAux)  {
          $cadenaAux = "";
          foreach($arregloFinalAux as $arregloRonda)  {
            $cadenaAux.=$arregloRonda["id"].",";
          }
          $cadenaAux = substr($cadenaAux,0,-1);
          $cadenaRondas[] = $cadenaAux;
        }
        
        //seleccionamos de los que quedan para refuerzo
        $dialogos = DB::select('SELECT * FROM sim_dialogos WHERE sim_grupos_id != '.$datosUsuario["sim_grupos_id"].' and disponible = 1');
        shuffle($dialogos);

        //los agrupamos por tipo de dialogo
        foreach($dialogos as $dialogosAux){
          $porTipoDialogo[$dialogosAux["sim_tipo_dialogo_id"]][] = $dialogosAux;
        }
        
        $cadenaRefuerzo4 = ""; 
        
        foreach($porTipoDialogo as $porTipoDialogoAux){
          shuffle($porTipoDialogoAux);
          $primero = array_shift($porTipoDialogoAux);
          $cadenaRefuerzo4.=$primero["id"].",";     
        }
        //echo $cadenaRefuerzo4;
        //arreglo($porTipoDialogo);
        
      //}
      
      //dejamos una ronda para refuerzo (ronda 4)
      $cadenaRondas[] = substr($cadenaRefuerzo4,0,-1);; 
      
      
      //if($tipo == 2){ 
      
        //seleccionamos los tipos de dialogos
        $tipos_dialogo = DB::select('SELECT * FROM sim_tipo_dialogo');
        
        //desordenamos los 21 tipos
        shuffle($tipos_dialogo);
        
        
        //******** INICIO RONDA 5 Y 6*********/
        //************************************/
        
        //obtenemos un arreglo de 10 elementos para la ronda 5 y 6
        $ronda56 = array_slice($tipos_dialogo, 0, 10);
        
         
        $cadenaAux5="";
        $cadenaAux6="";
        $cadenaRefuerzo7="";
        foreach($ronda56 as $ronda56Aux){
          
          //para la ronda 5 obtenemos 10 ejercicios de la carrera
          $dialogo = DB::select('SELECT * FROM sim_dialogos where sim_grupos_id = "'.$datosUsuario["sim_grupos_id"].'" and disponible = 1 and sim_tipo_dialogo_id='.$ronda56Aux['id']);
          $cadenaAux5.=$dialogo["id"].",";
          
          //para ronda 6 obtenemos 10 ejercicios del mismo tipo pero de las otras carreras, son tres posibles diálogos
          $dialogo = DB::select('SELECT * FROM sim_dialogos where sim_grupos_id != "'.$datosUsuario["sim_grupos_id"].'" and disponible = 1 and sim_tipo_dialogo_id='.$ronda56Aux['id']);
          
          //desordenamos el arreglo y tomamos el primero
          shuffle($dialogo);
          $primero = array_shift($dialogo);
          $cadenaAux6.=$primero["id"].",";      

          //en $dialogo, me quedan los dos diálogos no usados. Se usarán para refuerzo. Desordenamos el arreglo y tomamos el primero
          $primero = array_shift($dialogo);
          $cadenaRefuerzo7.=$primero["id"].",";   
          
          //en $dialogo me queda un dialogo no usado, que me servirá para el refuerzo11
          $dialogo9[] = $dialogo[0];      
          
        }
        $cadenaAux5 = substr($cadenaAux5,0,-1);
        $cadenaAux6 = substr($cadenaAux6,0,-1);
        $cadenaRefuerzo7 = substr($cadenaRefuerzo7,0,-1);
        $cadenaRondas[] = $cadenaAux5;
        $cadenaRondas[] = $cadenaAux6;
        $cadenaRondas[] = $cadenaRefuerzo7;
        
        
        //******** INICIO RONDA 8 Y 9 ********/
        //************************************/
        
        //obtenemos un arreglo de 11 elementos para la ronda 8 y 9
        $ronda89 = array_slice($tipos_dialogo, 10, 11);
         
        $cadenaAux8="";
        $cadenaAux9="";
        $cadenaRefuerzo10="";
        foreach($ronda89 as $ronda89Aux){
          
          //para la ronda 8 obtenemos 10 ejercicios de la carrera
          $dialogo = DB::select('SELECT * FROM sim_dialogos where sim_grupos_id = "'.$datosUsuario["sim_grupos_id"].'" and disponible = 1 and sim_tipo_dialogo_id='.$ronda89Aux['id']);
          $cadenaAux8.=$dialogo["id"].",";
          
          //para ronda 9 obtenemos 10 ejercicios del mismo tipo pero de las otras carreras, son tres posibles diálogos
          $dialogo = DB::select('SELECT * FROM sim_dialogos where sim_grupos_id != "'.$datosUsuario["sim_grupos_id"].'" and disponible = 1 and sim_tipo_dialogo_id='.$ronda89Aux['id']);
          
          //desordenamos el arreglo y tomamos el primero
          shuffle($dialogo);
          $primero = array_shift($dialogo);
          $cadenaAux9.=$primero["id"].",";      
          
          //en $dialogo, me quedan los dos diálogos no usados. Se usarán para refuerzo. Desordenamos el arreglo y tomamos el primero
          shuffle($dialogo);
          $primero = array_shift($dialogo);
          $cadenaRefuerzo10.=$primero["id"].",";  

          //en $dialogo me queda un dialogo no usado, que me servirá para el refuerzo11
          $dialogo10[] = $dialogo[0];     
        }
        $cadenaAux8 = substr($cadenaAux8,0,-1);
        $cadenaAux9 = substr($cadenaAux9,0,-1);
        $cadenaRefuerzo10 = substr($cadenaRefuerzo10,0,-1);
        $cadenaRondas[] = $cadenaAux8;
        $cadenaRondas[] = $cadenaAux9;    
        $cadenaRondas[] = $cadenaRefuerzo10;  
        
        //arreglo($dialogo9);
        //arreglo($dialogo10);
        $refuerzo11 = array_merge($dialogo9,$dialogo10);
        $cadenaRefuerzo11 = "";
        foreach($refuerzo11 as $refuerzo11Aux){
          $cadenaRefuerzo11.=$refuerzo11Aux["id"].",";  
        }
        $cadenaRondas[] = substr($cadenaRefuerzo11,0,-1);;  
        //exit;
      
      //}
      
      return $cadenaRondas;
    }

    private function generaRondaRefuerzo($id_usuario, $rondas, $arrDialogosTD, $idRonda){
    //generamos la ronda de refuerzo 4
      $cantidadDialogos = 7;
      if($idRonda==4){
        $between = "between 1 and 3";
      }
      if($idRonda==7){
        $between = "between 5 and 6";
      }
      if($idRonda==10){
        $between = "between 8 and 9";
      }
      if($idRonda==11){
        $between = "between 1 and 10";
        $cantidadDialogos = 10;
      }

      
      $historial = DB::select("select td.id as tipo_dialogo, sum(correcta) as correctas,count(*) as total from sim_tipo_dialogo as td 
                  join sim_dialogos as d 
                  on d.sim_tipo_dialogo_id=td.id
                  join sim_historial as h 
                  on h.sim_dialogos_id = d.id 
                  where sim_usuarios_id=".$id_usuario." and h.ronda ".$between." group by td.id");
      
      if(count($historial)>1){
        foreach($historial as $key=>$hist){
          $hist->porcentaje = $hist->correctas/$hist->total;
          $historialAux[] = $hist;
        }
        
        //ordenamos por las mas erradas
        foreach ($historialAux as $clave => $fila) {
          $porcent[$clave] = $fila->porcentaje;
        }     
        array_multisort($porcent, SORT_ASC, $historialAux);
        
        //tomo los primeros 7, que serían los puntajes más bajos
        $arregloFinal = array_chunk($historialAux, $cantidadDialogos);
        
        //ahora de esos 7 más bajo, debo ver que ejercicio me queda disponible
        foreach($arregloFinal[0] as $arregloFinalAux){
          $arrTP[] = $arregloFinalAux->tipo_dialogo;
        }
        
        $arrDialogos = explode(",",$rondas[$idRonda-1]->sim_preguntas_id);
        $cadenaRef = "";
        foreach($arrDialogos as $arrDialogosAux){
          if(in_array($arrDialogosTD[$arrDialogosAux],$arrTP)){
            $cadenaRef.=$arrDialogosAux.",";
          } 
        }
        $rondas[$idRonda-1]->sim_preguntas_id = substr($cadenaRef,0,-1);
      }   
      else{
        $rondas[$idRonda-1]->sim_preguntas_id = "";
      }
      
      return $rondas;
    }

  }