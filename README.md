SmsSender
========

**SmsSender** is a library which helps you send SMS through your web applications.
It provides an abstraction layer for sms manipulations.
The library is splitted in two parts: `HttpAdapter` and `Provider` and is
really extensible.

[![Build Status](https://secure.travis-ci.org/Carpe-Hora/SmsSender.png)](http://travis-ci.org/Carpe-Hora/SmsSender)


### HttpAdapters ###

_HttpAdapters_ are responsible to get data from remote APIs.

Currently, there are the following adapters:

* `BuzzHttpAdapter` for [Buzz](https://github.com/kriswallsmith/Buzz), a
  lightweight PHP 5.3 library for issuing HTTP requests;
* `CurlHttpAdapter` for [cURL](http://php.net/manual/book.curl.php);


### Providers ###

_Providers_ contain the logic to extract useful information.

Currently, there are only two providers:

* [Esendex](http://www.esendex.fr/)
* [Nexmo](http://www.nexmo.com/)

Installation
------------

The recommended way to install SmsSender is through composer.

Just create a `composer.json` file for your project:

```json
{
    "require": {
        "Carpe-Hora/SmsSender": "dev-master"
    }
}
```

And run these two commands to install it:

```bash
$ wget http://getcomposer.org/composer.phar
$ php composer.phar install
```


Now you can add the autoloader, and you will have access to the library:

```php
require 'vendor/autoload.php';
```

If you don't use neither **Composer** nor a _ClassLoader_ in your application,
just clone the repository and require the provided autoloader:

```php
require_once 'src/autoload.php';
```


Usage
-----

First, you need an `adapter` to query an API:

``` php
<?php

$adapter  = new \SmsSender\HttpAdapter\BuzzHttpAdapter();
```

The `BuzzHttpAdapter` is tweakable, actually you can pass a `Browser` object
to this adapter:

``` php
<?php

$buzz    = new \Buzz\Browser(new \Buzz\Client\Curl());
$adapter = new \SmsSender\HttpAdapter\BuzzHttpAdapter($buzz);
```

Now, you have to choose your `provider`.

You can use one of the builtin providers or write your own. You can also
register all providers and decide later.
That's we'll do:

``` php
<?php

$sender = new \SmsSender\SmsSender();
$sender->registerProviders(array(
    new \SmsSender\Provider\EsendexProvider(
        $adapter, '<ESENDEX_USER>', '<ESENDEX_PASS>', '<ESENDEX_ACCOUNT>'
    ),
    new \SmsSender\Provider\OtherProvider($adapter)
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
// "id"        => string(7) "some Id"
// "sent"      => bool "true"
// "status"    => string(9) "delivered"
// "recipient" => string(10) "0642424242"
// "body"      => string(17) "It's the answer."
```

The `send()` method returns a `Sms` result object with the following API, this
object also implements the `ArrayAccess` interface:

* `getId()` will return the `id`;
* `isSent()` boolean indicating if the sms was sent;
* `getStatus()` boolean indicating the sms' status (see the ResultInterface
  interface for the full statuses list);
* `getRecipient()` string representing the recipient's phone number;
* `getBody()` the message, as sent by the provider;

The SmsSender's API is fluent, you can write:

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


Single Recipient Strategy
-------------------------

Sometimes you want to configure a single recipient strategy in the development environment
to avoid sending SMS to real users, but still allow the developer to check the rendered message
in an SMS reader.

By using the `SingleRecipientSender`, you'll be able to send your SMS without any other changes
thanks to the decorator pattern. Just pass your in-use sender (`chSmsSender` for instance) and
a recipient phonenumber, and you're done.

``` php
<?php

$sender = new \SmsSender\SmsSender();
$sender->registerProviders(array(
    new \SmsSender\Provider\EsendexProvider(
        $adapter, '<ESENDEX_USER>', '<ESENDEX_PASS>', '<ESENDEX_ACCOUNT>'
    ),
    new \SmsSender\Provider\OtherProvider($adapter)
));

$singleRecipientSender = new \SmsSender\SingleRecipientSender($sender, '0601010101');
```

All SMS now will be sent to `0601010101`, but in a transparent way:

``` php
<?php

$result = $singleRecipientSender>send('0642424242', 'It\'s the answer.', 'Kévin');
// Result is:
// "id"        => string(7) "some Id"
// "sent"      => bool "true"
// "status"    => string(9) "delivered"
// "recipient" => string(10) "0642424242" <== The recipient phonenumber is not the single recipient one :)
// "body"      => string(17) "It's the answer."
```


Extending Things
----------------

You can provide your own `adapter`, you just need to create a new class which
implements `HttpAdapterInterface`.

You can also write your own `provider` by implementing the `ProviderInterface`.

Note, the `AbstractProvider` class can help you by providing useful features.


Unit Tests
----------

To run unit tests, you'll need a set of dependencies you can install using
composer:

``` bash
php composer.phar install --dev
```

Once installed, just launch the following command:

``` bash
./vendor/bin/phpunit
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
* [All contributors](https://github.com/Carpe-Hora/SmsSender/contributors)


License
-------

SmsSender is released under the MIT License. See the bundled LICENSE file for details.
