<?php

require('411bootstrap.php');

$alerts = FOO\Util::get($_GET, 'alerts', []);

FOO\Util::redirect(is_array($alerts) && count($alerts) ?
    "/alerts?" . http_build_query([
        'query' => 'id:(' . implode(' ', array_map('intval', $alerts)) . ')'
    ]):'/'
);
