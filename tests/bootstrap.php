<?php

if (file_exists($file = __DIR__.'/../vendor/autoload.php')) {
    $loader = include $file;
    $loader->add('SmsSender\Tests', __DIR__);
} else if (file_exists($file = __DIR__.'/../autoload.php')) {
    include $file;
} elseif (file_exists($file = __DIR__.'/../autoload.php.dist')) {
    include $file;
}
