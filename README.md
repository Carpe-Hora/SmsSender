chSmsSender
========

**chSmsSender** is a library which helps you send SMS through your web applications.
It provides an abstraction layer for sms manipulations.
The library is splitted in two parts: `HttpAdapter` and `Provider` and is
really extensible.

[![Build
Status](https://secure.travis-ci.org/Carpe-Hora/chSmsSender.png)](http://travis-ci.org/Carpe-Hora/chSmsSender)


### HttpAdapters ###

_HttpAdapters_ are responsible to get data from remote APIs.

Currently, there are the following adapters:

* `BuzzHttpAdapter` for [Buzz](https://github.com/kriswallsmith/Buzz), a
  lightweight PHP 5.3 library for issuing HTTP requests;
* `CurlHttpAdapter` for [cURL](http://php.net/manual/book.curl.php);


### Providers ###

_Providers_ contain the logic to extract useful information.

Currently, there is only one provider:

* [Esendex](http://www.esendex.fr/)

Installation
------------

If you don't use a _ClassLoader_ in your application, just require the provided
autoloader:

``` php
<?php

require_once 'src/autoload.php';
```

You're done.


Usage
-----

First, you need an `adapter` to query an API:

``` php
<?php

$adapter  = new \chSmsSender\HttpAdapter\BuzzHttpAdapter();
```

The `BuzzHttpAdapter` is tweakable, actually you can pass a `Browser` object
to this adapter:

``` php
<?php

$buzz    = new \Buzz\Browser(new \Buzz\Client\Curl());
$adapter = new \chSmsSender\HttpAdapter\BuzzHttpAdapter($buzz);
```

Now, you have to choose your `provider`.

You can use one of the builtin providers or write your own. You can also
register all providers and decide later.
That's we'll do:

``` php
<?php

$sender = new \chSmsSender\chSmsSender();
$sender->registerProviders(array(
    new \chSmsSender\Provider\EsendexProvider(
        $adapter, '<ESENDEX_USER>', '<ESENDEX_PASS>', '<ESENDEX_ACCOUNT>'
    ),
    new \chSmsSender\Provider\OtherProvider($adapter)
));
```

Everything is ok, enjoy!

API
---

The main method is called `send()` which receives a phone number, a message and
the name of the originator.

``` php
<?php

$result = $sender->send('0642424242', 'It\'s the answer.', 'Kévin');
// Result is:
// "id"       => string(7) "some Id"
// "sent"     => bool "true"
```

The `send()` method returns a `Sms` result object with the following API, this
object also implements the `ArrayAccess` interface:

* `getId()` will return the `id`;
* `isSent()` boolean indicating if the sms was sent;

The chSmsSender's API is fluent, you can write:

``` php
<?php

$result = $sender
    ->registerProvider(new \My\Provider\Custom($adapter))
    ->using('custom')
    ->send('0642424242', 'It\'s the answer.', 'Kévin');
```

The `using()` method allows you to choose the `adapter` to use. When you deal
with multiple adapters, you may want to choose one of them. The default
behavior is to use the first one but it can be annoying.


Extending Things
----------------

You can provide your own `adapter`, you just need to create a new class which
implements `HttpAdapterInterface`.

You can also write your own `provider` by implementing the `ProviderInterface`.

Note, the `AbstractProvider` class can help you by providing useful features.


Unit Tests
----------

To run unit tests, you'll need a set of dependencies you can install by
running the `install_vendors.sh` script:

```
./bin/install_vendors.sh
```

Once installed, just launch the following command:

```
phpunit
```

You'll obtain some _skipped_ unit tests due to the need of API keys.

Rename the `phpunit.xml.dist` file to `phpunit.xml`, then uncomment the
following lines and add your own API keys:

``` xml
<php>
    <!-- <server name="ESENDEX_API_USER" value="Your esendex user" /> -->
    <!-- <server name="ESENDEX_API_PASS" value="Your esendex password" /> -->
    <!-- <server name="ESENDEX_API_ACCOUNT" value="Your esendex account reference" /> -->
</php>
```

You're done.


Thanks
------

As this library is heavily inspired from
[willdurand](https://github.com/willdurand)'s
[Geocoder](https://github.com/willdurand/Geocoder), he deserves a
special mention in this README ;)


Credits
-------

* Kévin Gomez <kevin_gomez@carpe-hora.com>
* [All contributors](https://github.com/willdurand/chSmsSender/contributors)


License
-------

chSmsSender is released under the MIT License. See the bundled LICENSE file for details.
