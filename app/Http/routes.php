<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', function () use ($app) {
    //return $app->welcome();
    echo "hola";
});

//User Routes

$app->post('user/login', 'Users@login');


// Dashboard routes

$app->get('dashboard/getdata', 'Dashboard@getData');


//$app->group(['prefix' => 'tracking'], function ($app) {
    $app->post('tracking/juego', 'Tracking@juego');
    $app->post('tracking/setdata', 'Tracking@setData');
//});