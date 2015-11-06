<?php

class UserTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicExample()
    {
        $this->post('/user/login', ['usuario' => 'test', 'password' => 'test1'])
            ->seeJson(["nombre" => "test","apellido" => "test1"]);
    }
}
