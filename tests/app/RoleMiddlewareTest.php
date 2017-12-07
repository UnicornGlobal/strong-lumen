<?php
/**
 * Created by PhpStorm.
 * User: fergus
 * Date: 2017/12/07
 * Time: 3:02 PM
 */

class RoleMiddlewareTest extends TestCase
{
    public function testRole()
    {
        $this->get('???');

        $this->assertEquals(
            '{"error":"Incorrect Role"}', $this->response->getContent()
        );
    }
}