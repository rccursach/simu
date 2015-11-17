<?php
namespace App;
  use DB;

  class Tracking {

	//juego($id_usuario, $id_dialogo, $id_respuesta, $nivel)
	public juego($usuario, $iddial, $respuesta, $nivel) {

		//consulto dialogo para comprobar si las respuesta es la correcta
		$dialogo = DB::select("select from sim_dialogos where id=?",$iddial);

		$dialogo = $dialogo != null ? $dialogo[0] : null;

		//guardar registro (fecha por defecto en BD)
		$correcta=($dialogo->sim_tipo_dialogo_id==$respuesta)? true : false;
		$sim_usuarios_id = $usuario;
		$id_respuesta_elegido = $respuesta;
		$sim_dialogos_id = $iddial;
		$ronda = $nivel;
		try{
			DB::insert("insert into sim_historial (correcta, sim_usuarios_id, id_respuesta_elegido, sim_dialogos, ronda) values(?,?,?,?,?)", [$correcta, $sim_usuarios_id, $id_respuesta_elegido, $sim_dialogos, $ronda]);
			return true;
		}catch(Exception $e){
			return null;
		}
	}

	public setData($id_usuario, $clave, $valor, $sim_dialogos_id, $sim_ronda_id){

		$cadenaSQL = "";

		if($sim_dialogos_id!=""){
			$cadenaSQL.= " and sim_dialogos_id = ".$sim_dialogos_id;
		}
		if($sim_ronda_id!=""){
			$cadenaSQL.= " and sim_ronda_id = ".$sim_ronda_id;
		}
		
		$data = DB::select("select id from sim_data_usuario where sim_usuarios_id=".$usuario." and tipo='".$clave."' ".$cadenaSQL);
		
		try{
			if(count($data)>0){
				$dataus = DB::update('update sim_data_usuario set valor=? where id=?',[$valor, $data[0]["id"] ]);
			}
			else{
				$dataus = DB::insert("insert into sim_data_usuario(sim_usuarios_id,tipo,valor,sim_dialogos_id,sim_ronda_id,) values(?,?,?,?,?)", [$usuario, $clave, $valor, $sim_dialogos_id, $sim_ronda_id]);
			}
			return true;
		}catch(Exception $e){
			return null;
		}
	}


  }
