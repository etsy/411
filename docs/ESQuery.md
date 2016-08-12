ESQuery
========

ESQuery is the query parser bundled with 411 for querying Elasticsearch. Each ESQuery query consists of one or more "commands", separated by the pipe (`|`) character. During execution, each command is translated into an Elasticsearch DSL query and sent to the server. Any results that are returned get passed off to the next command in the chain.


Structure
---------

Each query is structured as follows:
```
    Options* SearchCommand ('|' [AggCommand | JoinCommand])* ('|' [TransactionCommand])?
```

In English:

- A series of 0 or more options
- A SearchCommand
- A series of 0 or more AggCommands or JoinCommands
- An optional TransactionCommand

with each command separated from the next by a pipe (`|`) character.


Syntax
------

### Options ###

Options that can be enabled/disabled within each query. Each option is prefixed by a `$`.

`allow_leading_wildcard`

- Allows `*` or `?` as the first character. Makes for slow queries!
- Type: bool
- Example: `$allow_leading_wildcard:true`

`sort`

- A list of fields to sort on.
- Type: List
- Example: `$sort:[time:ASC, type:DESC, duration:DESC]`

`map`

- A mapping of fields and what to rename them to.
- Type: Map
- Example: `$map:[request_ip:ip, geoip_location:loc]`


#### Unused ####

These options are managed by 411.

- `date_field`: The name of the field used as the timestamp.
- `to`: The latest date to pull results from. (Unix timestamp)
- `from`: The earliest date to pull results from. (Unix timestamp)
- `size`: The maximum number of results to return.
- `flatten`: Flatten any nested structures into a flat key-value map.
- `fields`: The list of fields to return.


### Commands ###

#### Search ####

A standard Lucene QueryParser [query](http://lucene.apache.org/core/5_4_1/queryparser/org/apache/lucene/queryparser/classic/package-summary.html). Almost all of the features are available in ESQuery. The ones that aren't primarily relate to scoring, and are listed here:

- Fuzzy searches
- Proximity searches
- Boosting a term

Must always be the first command.

- Syntax: `QUERY_PARSER_QUERY`
- Example: `level:99 type:normal`


##### Lists #####

Inserts an array of values into a query. These arrays can be defined in 411 via the Lists functionality. ESQuery runs a terms filter on the values that are provided.

- Syntax: `KEY:@LIST_NAME`
- Example: `src_ip:@bad_ip_list`


#### Join ####

Extracts the values from the previous command and makes them available to the following query.

- Syntax: `'join' 'source:'SOURCE_FIELD 'target:'DEST_FIELD QUERY_PARSER_QUERY`
- Example: `type:tcp | join source:src_ip target:dst_ip flags:0`


#### Transaction ####

Combines documents with matching values for a given field. Must always be the final command (if used).

- Syntax: `'trans' 'field:'FIELD_NAME`
- Example: `trans field:request_uuid`


### Aggregation ###

Standard Elasticsearch aggregations. Multiple aggregations can be chained to nest them. ESQuery will return the bucketed data in a table. Any parameters that an aggregation takes can be passed in after setting the field.

Supported aggs: `terms`, `sig_terms`, `card`, `max`, `avg`, `sum`

- Syntax: `'agg:'AGG_TYPE 'field:'FIELD_NAME (AGG_OPT':'AGG_OPT_VAL)*`
- Example: `agg:terms field:user_id min_doc_count:50`


Examples
--------

Match all documents.
```
*
```

Get a count of requests to `abc.com` bucketed by `ip_addr`.
```
host:abc.com | agg:terms field:ip_addr
```

Find all requests to `abc.com` sorted by `ip_addr` and `date`.
```
$sort:[ip_addr:ASC, date:ASC] host:abc.com
```

Find all users with the same email as `bob`.
```
user:bob | join source:email target:email
```

Find and group all log lines associated with requests that came from `10.0.0.5`.
```
ip_addr:10.0.0.5 | trans field:request_uuid
```

Find any documents that have a value from `@include` but not a value from `@exclude`.
```
tag:@include -tag:@exclude
```


Find the most common useragent.
```
* | agg:terms field:user_agent size:1
```
