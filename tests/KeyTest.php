<?php

namespace Sinclair\Cosmos\Tests;

use Sinclair\Cosmos\CosmosServiceProvider;

class KeyTest extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app)
    {
        return [CosmosServiceProvider::class];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application $app
     *
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('cosmos.access_token', 'dsZQi3KtZmCv1ljt3VNWNm7sQUF1y5rJfC6kv5JiwvW0EndXdDku/dkKBp8/ufDToSxLzR4y+O/0H/t4bQtVNw==');
    }

    function test_it_can_generate_a_key()
    {
        $actual   = (new \Sinclair\Cosmos\Resources\KeyGenerator())->make('GET', 'dbs', 'dbs/ToDoList', 'Thu, 27 Apr 2017 00:51:12 GMT');
        $expected = 'type%3dmaster%26ver%3d1.0%26sig%3dc09PEVJrgp2uQRkr934kFbTqhByc7TVr3OHyqlu%2bc%2bc%3d';
        // convert lower case encodings to upper case i.e. %3d ==> %3D
        $expected = urlencode(urldecode($expected));
        $this->assertEquals($expected, $actual);
    }
}
