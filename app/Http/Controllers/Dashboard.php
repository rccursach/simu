<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

use App\Dashboard as Dash;

class Dashboard extends BaseController
{

    public function getData(Request $req){
        $uid = $req->input('userid');
        $data = (new Dash())->getDashboardData($uid);
        $out = null;

        if(!$data){
            $out = [
                "data" => null,
                "error" => "Usuario no encontrado"
            ];
            return json_encode($out);
        }

        $out = [
            "data" => $data
        ];
    }
}