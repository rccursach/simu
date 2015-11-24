<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Tracking as Track;

class Tracking extends BaseController
{

    public function juego(Request $req){
        $id_usuario = $req->input('id_usuario');
        $id_dialogo = $req->input('id_dialogo');
        $id_respuesta = $req->input('id_respuesta');
        $nivel = $req->input('nivel');
        $out = null;
        if($id_usuario == null || $id_dialogo == null || $id_respuesta == null || $nivel == null){
          $out = ["data" => null, "error" => 'faltan parametros'];
          return json_encode($out);
        }
        $data = (new Track())->juego($id_usuario, $id_dialogo, $id_respuesta, $nivel);
        $out = ['data' => $data];
        return json_encode($out);
    }

    public function setData(Request $req){
        $id_usuario = $req->input('id_usuario');
        $clave = $req->input('clave');
        $valor = $req->input('valor');
        
        $sim_dialogos_id = null;
        $sim_ronda_id = null;
        
        if ($req->has('sim_dialogos_id')){
          $sim_dialogos_id = $req->input('sim_dialogos_id');
        }
        if ($req->has('sim_ronda_id')){
          $sim_ronda_id = $req->input('sim_ronda_id');
        }

        $res = (new Track())->setData($id_usuario, $clave, $valor, $sim_dialogos_id, $sim_ronda_id);
        $out = null;
        $out = $res != null ? ['data' => $res] : ['data' => null, 'error' => "Ocurrió un error al procesar la información"];

        return json_encode($out);
    }
}


