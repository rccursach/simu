<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Tracking as Track;

class Dashboard extends BaseController
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
}


