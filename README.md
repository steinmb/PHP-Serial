PHP Serial
==========

Fork and rewrite of blamebutton/php-serial class. Currently not production ready. Refactored to use:

* Namespaces
* PSR4 autoloading
* Support PHP 7.3.x and newer.
* PHP unit tests.
* Prevent all methods and properties having public access.
* Trow exceptions and stop on fatal errors instead of logging to PHP error logs.

Install
-------

```bash
composer require steinmb/php-serial
```


Example
-------

```php
<?php

include_once __DIR__ . '/vendor/autoload.php';

use steinmb\phpSerial\Receive;
use steinmb\phpSerial\Send;
use steinmb\phpSerial\SerialConnection;
use steinmb\phpSerial\System;

$clientSystem = new System();
$port1 = new SerialConnection(
    new System(),
    'com1',
    38400,
    'none',
    8,
    1,
    'none'
);

$senderService = new Send($port1);
$receiveService = new Receive($port1);

$senderService->send('Foo bar');
echo $receiveService->read();
```

State of the project
--------------------

In re-write state.

### Bugs

As any software, none, ahem.

### Platform support

* **Linux and macOS**: the initially supported platform, the one I used. Probably the less
  buggy one.
* **Windows**: Untested due to lack of access to machines running Windows.