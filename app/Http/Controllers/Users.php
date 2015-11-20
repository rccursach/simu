<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\User;

class Users extends BaseController
{

    public function login(Request $req){
        if($req->has('usuario') && $req->has('password')) {
            $u = $req->input('usuario');
            $p = $req->input('password');
            $res = (new User())->findUserWithPassword($u, $p);
            
            if (count($res))
                return response()->json(["data" => $res[0]], 200);

            return response()->json(["error" => "usuario o password incorrectos"], 404);
        }
        else{
            return response()->json(["error" => "Debe proveer un usuario y password"], 403);
        }
    }
}
