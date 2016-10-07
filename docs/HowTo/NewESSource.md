Adding a new Elasticsearch source
=================================

To add a new Elasticsearch source to 411, add a new block to `$config['es']` in `config.php`.

Example block:
```
$config['es'] = [
    ...
    'things' => [
        'hosts' => ['http://localhost:9200'],
        'index_hosts' => [],
        'ssl_cert' => null,
        'index' => null,
        'date_based' => true,
        'date_field' => '@timestamp',
        'src_url' => null,
    ],
];
```


Fields
------

- `hosts`: A list of hosts in your ES cluster to query.
- `index_hosts`: A list of hosts in your ES cluster to create documents on. If empty, defaults to `hosts`.
- `ssl_cert`: The full path to the ssl certificate of the server (if using self signed certs).
- `index`: The index to query. If `null`, will query all indices.
- `date_based`: Whether the indices are date based.
- `date_field`: The field to use for date based queries. If `null`, this is ignored.
- `src_url`: A link to display the data (Kibana, as an example).
