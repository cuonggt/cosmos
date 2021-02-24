# Azure Cosmos DB REST API Wrapper For Laravel

## Installation

You can install the package via composer:

``` bash
composer require sinclair/cosmos
```

You may need to publish the config file to change the values, or you could set the environment variables in your `.env` file:
``` php
    'account_name' => env('COSMOS_ACCOUNT_NAME'),
    'access_token' => env('COSMOS_ACCESS_TOKEN'),
    'defaults'     => [
        'database' => [
            'name'       => env('COSMOS_DB_NAME'),
            'collection' => env('COSMOS_DB_COLLECTION_NAME'),
        ],
    ],
```

## Usage

You can query documents in two ways either with the model or with the documents wrapper

### Model:

__Creating__

``` php
$document = new \Sinclair\Cosmos\Models\Document($attributes);
$document->create();
```

You can also assign properties and save too:

``` php
$document = new \Sinclair\Cosmos\Models\Document();
$document->id = 'foobar';
$document->my_property = 'some-other-value';
$document->create();
```

Or you can pass an array in:

``` php
$document = new \Sinclair\Cosmos\Models\Document();
$document->create(['foo' => 'bar']);
```

The `id` can go in many places depending on your situation:

``` php
$document = new \Sinclair\Cosmos\Models\Document();

// passing it in as part of an array of attributes
$document->create(['id' => 'my-id', foo' => 'bar']);

// setting the id explicitly
$document->create('my-id', ['foo' => 'bar']);

// assigning it to the id property
$document->id = 'my-id';
$document->create();

// pass it in during instatiation
$document = new \Sinclair\Cosmos\Models\Document(['id' => 'my-id', 'foo' = 'bar']);

// or leave it blank we will set a guid as the id just before it is created in your cosmos db
```

__Updating__

It's very similar to creating:

``` php
// assign to a property
$document->foo = 'bar';
$document->update();

// pass in attributes
$document->update(['foo' => 'bar']);

```

__Saving__

Little shortcut:

``` php
$document->foo = 'bar';
$document->save();

// or
$document->save(['foo' => 'bar']);
```

__Listing__

Returns a collection of Document models.

```php
$results = \Sinclair\Cosmos\Models\Document::query()->all();
```

If you want to send a query along with the listing request:

``` php
$results = \Sinclair\Cosmos\Models\Document::query()->from('my-table')->where('foo', 'bar')->get();
```

It uses the underlying query builder to generate the sql, so you can use all the methods you are familiar with from Laravel.

__Deleting__

Pretty simple:

``` php
$document->delete(); // returns boolean
```

### Using the wrapper

__Creating__

To create, instantiate the wrapper, and use the documents class to create a document passing in an array of attributes to store.
 
``` php
$cosmos = new Cosmos();

$result = $cosmos->documents->create(['foo' => 'bar', 'id' => $id]); // return Document model

// or explicity set the id

$result = $cosmos->documents->create(['foo' => 'bar'], $id); // return Document model
```

__Updating__

To update, instantiate the wrapper, and use the documents class to update a document passing in an array of attributes to store as well as the existing id. 

``` php
$cosmos = new Cosmos();

$result = $cosmos->documents->update(['foo' => 'bar'], $id); // return Document model
```

__Listing__

``` php
$cosmos = new Cosmos();

$results = $cosmos->documents->all();

// or if you want to pass in query
$results = $cosmos->documents->from('table')->where('foo', 'bar')->get();
```

__Deleting__

Simply pass the id of the document you want to delete

``` php
$cosmos = new Cosmos();

$cosmos->documents->delete($id); // returns boolean
```

#### Raw Responses

You can get the raw responses from the cosmos wrapper, just suffix you method name with `Raw`:

``` php
$cosmos = new Cosmos();

$cosmos->documents->createRaw($attributes, $id = null);
$cosmos->documents->updateRaw($attributes, $id);
$cosmos->documents->allRaw($limit = null);
$cosmos->documents->getRaw($limit = null); // this is the method for querying
$cosmos->documents->deleteRaw($id);
```

This will return a response that implements the `\Psr\Http\Message\ResponseInterface`.

##### Modifiers

Whether using the model of the wrapper you are able to make small modifications to headers and the parameters that make up the url:

``` php
$cosmos = new Cosmos();

$cosmos->documents->setHeader('x-test', 123)->get();
```

You can set the account name:

``` php
$cosmos->setAccount('my-account');
```

You can set the database name:

``` php
$cosmos->setDatabase('my-database');
```

You can set the collection name:

``` php
$cosmos->setCollection('my-collection')
```

You can also pass in all the variables into the constructor of the cosmos wrapper:

``` php
$cosmos = new Cosmos(new \GuzzleHttp\Client, ['x-test' => 'foo'], 'my-account', 'my-database', 'my-collection');
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email tom.sinclair@twentyci.co.uk instead of using the issue tracker.

## Credits

- [Tom Sinclair](https://github.com/tom-sinclair)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
