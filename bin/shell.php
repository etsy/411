#!/usr/bin/php
<?php

// Boris shell
require_once(__DIR__ . '/../phplib/411bootstrap.php');

$boris = new \Boris\Boris();

$config = new \Boris\Config();
$config->apply($boris);

$options = new \Boris\CLIOptionsHandler();
$options->handle($boris);

$boris->start();
