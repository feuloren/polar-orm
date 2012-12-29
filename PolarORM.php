<?php
spl_autoload_register(function ($class) {
    include_once $class . '.class.php';
  });

$db = new PolarDB('localhost', 'polar', 'root', 'root');
?>