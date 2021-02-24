<?php

namespace Sinclair\Cosmos\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use Ramsey\Uuid\Uuid;
use Sinclair\Cosmos\Cosmos;
use Sinclair\Cosmos\CosmosServiceProvider;
use Sinclair\Cosmos\Models\Document;

class DocumentsTest extends TestCase
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
        $app['config']->set('cosmos.account_name', 'foo');
        $app['config']->set('cosmos.access_token', 'my_access_token');
        $app['config']->set('cosmos.defaults.database.name', 'my_test_cosmos_db');
        $app['config']->set('cosmos.defaults.database.collection', 'my_test_cosmos_collection');
        $app['config']->set('database.default', 'sqlsrv');
    }

    /**
     * @return array
     */
    protected function genericResponseHeaders(): array
    {
        return [
            'x-ms-request-charge'   => 1,
            'Content-Type'          => 'application/json',
            'Date'                  => now()->toRfc1123String(),
            'etag'                  => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'x-ms-activity-id'      => str_random(),
            'x-ms-alt-content-path' => '',
            'x-ms-continuation'     => '',
            'x-ms-item-count'       => 1,
            'x-ms-resource-quota'   => 1,
            'x-ms-resource-usage'   => 1,
            'x-ms-retry-after-ms'   => 1,
            'x-ms-schemaversion'    => '',
            'x-ms-serviceversion'   => '',
            'x-ms-session-token'    => '',
        ];
    }

    function test_it_sets_default_headers()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('post')
             ->once()
             ->with(\Mockery::any(), \Mockery::on(function ($argument) {
                 return array_has(array_get($argument, 'headers', []), [
                     'Authorization',
                     'Content-Type',
                     'x-ms-session-token',
                     'x-ms-date',
                     'x-ms-version',
                 ]);
             }))
             ->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents
            ->from('foo')
            ->where('foo', 'bar')
            ->orderBy('foo', 'desc')
            ->get();
    }

    function test_it_can_set_a_header()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('get')->with(\Mockery::any(), \Mockery::on(function ($argument) {
            return array_get($argument, 'headers.x-foo-test') == 'bar';
        }))->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents->setHeader('x-foo-test', 'bar')->all();
    }

    function test_it_sets_the_url()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('post')->with(\Mockery::on(function ($argument) {
            $account    = 'foo';
            $db         = 'my_test_cosmos_db';
            $collection = 'my_test_cosmos_collection';

            return $argument == implode('', ['https://', $account, '.documents.azure.com/dbs/', $db, '/colls/', $collection]) . '/docs';
        }), \Mockery::any())->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents->from('foo')->where('foo', 'bar')->orderBy('foo', 'desc')->get();
    }

    function test_it_can_modify_the_url()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $account    = 'baz';
        $db         = 'my_other_test_cosmos_db';
        $collection = 'my_other_test_cosmos_collection';

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('get')->with(\Mockery::on(function ($argument) use ($collection, $db, $account) {
            return $argument == implode('', ['https://', $account, '.documents.azure.com/dbs/', $db, '/colls/', $collection, '/docs']);
        }), \Mockery::any())->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents->setAccount($account)->setCollection($collection)->setDatabase($db)->all();
    }

    function test_it_can_list_documents()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $document2 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'baz',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents1 = ['Documents' => [$document1, $document2], '_rid' => 'd9RzAJRFKgw=', '_count' => 2];
        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = new MockHandler([
            new Response(200, $this->genericResponseHeaders(), json_encode($documents1)),
            new Response(200, $this->genericResponseHeaders(), json_encode($documents2)),
            new Response(200, $this->genericResponseHeaders(), json_encode($documents1)),
            new Response(200, $this->genericResponseHeaders(), json_encode($documents2)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);

        $results = $cosmos->documents->all();

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(2, $results);

        $this->assertInstanceOf(Document::class, $results->first());

        $results = $cosmos->documents->all(1);

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(1, $results);

        $this->assertInstanceOf(Document::class, $results->first());

        $results = $cosmos->documents->allRaw();

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $results);

        $results = $cosmos->documents->allRaw(1);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $results);
    }

    function test_it_can_query_for_documents()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = new MockHandler([
            new Response(200, $this->genericResponseHeaders(), json_encode($documents2)),
            new Response(200, $this->genericResponseHeaders(), json_encode($documents2)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);

        $results = $cosmos->documents->from('foo')->where('foo', 'bar')->get();

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(1, $results);

        $this->assertInstanceOf(Document::class, $results->first());

        $results = $cosmos->documents->from('foo')->where('foo', 'bar')->getRaw();

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $results);
    }

    function test_it_can_query_for_the_first_document()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = new MockHandler([
            new Response(200, $this->genericResponseHeaders(), json_encode($documents2)),
        ]);

        $handler = HandlerStack::create($mock);

        $client = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);

        $results = $cosmos->documents->from('foo')->where('foo', 'bar')->first();

        $this->assertInstanceOf(Document::class, $results);
    }

    function test_it_uses_sql_generated_by_the_builder_in_first_calls()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('post')->with(\Mockery::any(), \Mockery::on(function ($argument) {
            return array_get($argument, 'form_params.query') === 'select top 1 [foo] from [foo] where [foo] = "bar" order by [foo] desc';
        }))->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents->from('foo')->where('foo', 'bar')->orderBy('foo', 'desc')->first(['foo']);
    }

    function test_it_uses_sql_generated_by_the_builder_in_query_calls()
    {
        $document1 = [
            'id'          => Uuid::uuid4()->toString(),
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $documents2 = ['Documents' => [$document1], '_rid' => 'd9RzAJRFKgw=', '_count' => 1];

        $mock = \Mockery::mock(Client::class);

        $mock->shouldReceive('post')->with(\Mockery::any(), \Mockery::on(function ($argument) {
            return array_get($argument, 'form_params.query') === 'select * from [foo] where [foo] = "bar" order by [foo] desc';
        }))->andReturn(new Response(200, $this->genericResponseHeaders(), json_encode($documents2)));

        $cosmos = new Cosmos($mock);

        $cosmos->documents->from('foo')->where('foo', 'bar')->orderBy('foo', 'desc')->get();
    }

    function test_it_can_create_a_document_by_filling_a_model_with_attributes()
    {
        $id        = Uuid::uuid4()->toString();
        $document1 = [
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $mock = new MockHandler([
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);

        $result1 = $cosmos->documents->create(['foo' => 'bar', 'id' => $id]);

        $this->assertTrue(is_array($result1));
        $this->assertEquals($id, $result1['id']);
        $this->assertEquals('bar', $result1['foo']);

        $result2 = $cosmos->documents->create(['foo' => 'bar']);

        $this->assertTrue(is_array($result2));
        $this->assertEquals($id, $result2['id']);
        $this->assertEquals('bar', $result2['foo']);

        $result3 = $cosmos->documents->create(['foo' => 'bar'], $id);

        $this->assertTrue(is_array($result3));
        $this->assertEquals($id, $result3['id']);
        $this->assertEquals('bar', $result3['foo']);

        $result4 = $cosmos->documents->createRaw(['foo' => 'bar', 'id' => $id]);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result4);

        $result5 = $cosmos->documents->createRaw(['foo' => 'bar']);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result5);

        $result6 = $cosmos->documents->createRaw(['foo' => 'bar'], $id);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result6);
    }

    function test_it_can_update_a_document_by_filling_a_model_with_attributes()
    {
        $id        = Uuid::uuid4()->toString();
        $document1 = [
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $document2 = [
            'id'          => $id,
            'foo'         => 'baz',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];
        $document3 = [
            'id'          => $id,
            'foo'         => 'bin',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $mock = new MockHandler([
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(200, $this->genericResponseHeaders(), json_encode($document2)),
            new Response(200, $this->genericResponseHeaders(), json_encode($document3)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);
        app()->instance('cosmos', $cosmos);

        $result1 = $cosmos->documents->create(['foo' => 'bar', 'id' => $id]);

        $this->assertTrue(is_array($result1));
        $this->assertEquals($id, $result1['id']);
        $this->assertEquals('bar', $result1['foo']);

        $result1 = $cosmos->documents->update(['foo' => 'baz'], $id);

        $this->assertEquals('baz', $result1['foo']);

        $result1 = $cosmos->documents->updateRaw(['foo' => 'bin'], $id);

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $result1);
    }

    function test_it_can_delete_a_document()
    {
        $id = Uuid::uuid4()->toString();

        $mock = new MockHandler([
            new Response(204, $this->genericResponseHeaders(), ''),
            new Response(204, $this->genericResponseHeaders(), ''),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        $cosmos = new Cosmos($client);

        $this->assertTrue($cosmos->documents->delete($id));

        $this->assertInstanceOf(\Psr\Http\Message\ResponseInterface::class, $cosmos->documents->deleteRaw($id));
    }
}
