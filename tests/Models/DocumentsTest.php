<?php

namespace Sinclair\Cosmos\Tests\Models;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Collection;
use Orchestra\Testbench\TestCase;
use Ramsey\Uuid\Uuid;
use Sinclair\Cosmos\Cosmos;
use Sinclair\Cosmos\CosmosException;
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
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $results = Document::query()->all();

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(2, $results);

        $this->assertInstanceOf(Document::class, $results->first());

        $results = Document::query()->all(1);

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(1, $results);

        $this->assertInstanceOf(Document::class, $results->first());
    }

    function test_it_throws_an_exception_if_listing_fails()
    {
        $mock = new MockHandler([
            new Response(422, $this->genericResponseHeaders(), 'Failure'),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));
        $this->expectException(CosmosException::class);
        $this->expectExceptionMessage('Failure');
        $this->expectExceptionCode(422);
        Document::query()->all();
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
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $results = Document::query()->where('foo', 'bar')->get();

        $this->assertInstanceOf(Collection::class, $results);

        $this->assertCount(1, $results);

        $this->assertInstanceOf(Document::class, $results->first());
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

        app()->instance('cosmos', new Cosmos($mock));

        Document::query()->from('foo')->where('foo', 'bar')->orderBy('foo', 'desc')->get();
    }

    function test_it_throws_an_exception_if_query_fails()
    {
        $mock = new MockHandler([
            new Response(422, $this->genericResponseHeaders(), 'Failure'),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));
        $this->expectException(CosmosException::class);
        $this->expectExceptionMessage('Failure');
        $this->expectExceptionCode(422);
        Document::query()->from('foo')->where('foo', 'bar')->orderBy('foo', 'desc')->get();
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
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $result1 = \Sinclair\Cosmos\Facades\Document::create(['foo' => 'bar', 'id' => $id]);

        $this->assertEquals($id, $result1->id);
        $this->assertEquals('bar', $result1->foo);

        $result2 = \Sinclair\Cosmos\Facades\Document::create(['foo' => 'bar']);

        $this->assertEquals($id, $result2->id);
        $this->assertEquals('bar', $result2->foo);

        $result3 = \Sinclair\Cosmos\Facades\Document::create($id, ['foo' => 'bar']);

        $this->assertEquals($id, $result3->id);
        $this->assertEquals('bar', $result3->foo);
    }

    function test_it_can_create_a_document_by_assigning_properties()
    {
        $id        = Uuid::uuid4()->toString();
        $time      = now()->timestamp;
        $document1 = [
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => $time,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $mock = new MockHandler([
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $model = new Document();

        $model->id = $id;

        $model->foo = 'bar';

        $this->assertEmpty($model->getOriginal());

        $this->assertFalse($model->exists());

        $model = $model->save();

        $this->assertEquals($model->getOriginal(), $document1);

        $this->assertTrue($model->exists());

        $this->assertEquals($id, $model->id);
        $this->assertEquals('bar', $model->foo);
    }

    function test_it_throws_an_exception_if_create_fails()
    {
        $mock = new MockHandler([
            new Response(422, $this->genericResponseHeaders(), 'Failure'),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));
        $this->expectException(CosmosException::class);
        $this->expectExceptionMessage('Failure');
        $this->expectExceptionCode(422);
        $id = 'baz';
        \Sinclair\Cosmos\Facades\Document::create(['foo' => 'bar', 'id' => $id]);
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

        $mock = new MockHandler([
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(200, $this->genericResponseHeaders(), json_encode($document2)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $result1 = \Sinclair\Cosmos\Facades\Document::create(['foo' => 'bar', 'id' => $id]);

        $this->assertInstanceOf(Document::class, $result1);
        $this->assertEquals($id, $result1->id);
        $this->assertEquals('bar', $result1->foo);

        $result1->update(['foo' => 'baz']);

        $this->assertEquals('baz', $result1->foo);
    }

    function test_it_can_update_a_document_by_assigning_properties()
    {
        $id        = Uuid::uuid4()->toString();
        $time      = now()->timestamp;
        $document1 = [
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => $time,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $document2 = [
            'id'          => $id,
            'foo'         => 'baz',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => $time,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ];

        $mock = new MockHandler([
            new Response(201, $this->genericResponseHeaders(), json_encode($document1)),
            new Response(200, $this->genericResponseHeaders(), json_encode($document2)),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $model = new Document();

        $model->id = $id;

        $model->foo = 'bar';

        $this->assertEmpty($model->getOriginal());

        $this->assertFalse($model->exists());

        $model = $model->save();

        $this->assertEquals($model->getOriginal(), $document1);

        $this->assertTrue($model->exists());

        $this->assertEquals($id, $model->id);
        $this->assertEquals('bar', $model->foo);

        $model->foo = 'baz';

        $model->save();

        $this->assertEquals('baz', $model->foo);
    }

    function test_it_throws_an_exception_if_update_fails()
    {
        $mock = new MockHandler([
            new Response(422, $this->genericResponseHeaders(), 'Failure'),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));
        $this->expectExceptionMessage('Failure');
        $this->expectExceptionCode(422);
        $this->expectException(CosmosException::class);
        $id = 'baz';
        \Sinclair\Cosmos\Facades\Document::update(['foo' => 'bar', 'id' => $id]);
    }

    function test_it_can_delete_a_document()
    {
        $id       = Uuid::uuid4()->toString();
        $document = new Document([
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ]);

        $mock = new MockHandler([
            new Response(204, $this->genericResponseHeaders(), ''),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));

        $document->delete();

        $this->assertFalse($document->exists());
    }

    function test_it_throws_an_exception_if_delete_fails()
    {
        $mock = new MockHandler([
            new Response(422, $this->genericResponseHeaders(), 'Failure'),
        ]);

        $handler = HandlerStack::create($mock);
        $client  = new Client(['handler' => $handler]);

        app()->instance('cosmos', new Cosmos($client));
        $this->expectException(CosmosException::class);
        $this->expectExceptionMessage('Failure');
        $this->expectExceptionCode(422);
        $id       = Uuid::uuid4()->toString();
        $document = new Document([
            'id'          => $id,
            'foo'         => 'bar',
            '_rid'        => 'd9RzAJRFKgwBAAAAAAAAAA==',
            '_ts'         => now()->timestamp,
            '_self'       => 'dbs/d9RzAA==/colls/d9RzAJRFKgw=/docs/d9RzAJRFKgwBAAAAAAAAAA==/',
            '_etag'       => '"0000d986-0000-0000-0000-56f9e25b0000"',
            'attachments' => 'attachments/',
        ]);
        $document->delete();
    }
}
