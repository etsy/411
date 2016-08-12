<?php

require_once('411bootstrap.php');

header("Content-Type: application/manifest+json");

$site = FOO\SiteFinder::getCurrent();
$name = FOO\Util::get($site, 'name', '411');

print json_encode([
  "short_name" => $name,
  "name" => $name,
  "images" => [],
  "background_color" => "black",
  "start_url" => "/",
  "display" => "standalone",
  "orientation" => "portrait-primary"
]);
