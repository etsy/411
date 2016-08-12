Lists
========

Lists allow you to define an array of values for use in queries. At runtime, the values get insert into the query, allowing you to maintain the query and list of values separately. This is especially handy when you have a large number of changing items (like IP addresses) that you want to do a lookup against.


Usage
-----

For this example, we'll use a list of malware domains from [malwaredomainlist.com](http://malwaredomainlist.com/)

![List source](/docs/imgs/list_source.png?raw=true)

To create a new list, click on the 'Lists' button in the header of 411. Hit create to be taken to the list creation page.

![List page](/docs/imgs/list.png?raw=true)

Each list contains the following fields:

- Name: The name of list variable.
- Type: The format of the list.
    - JSON: A JSON array.
    - Comma separated: A comma separated list of values (all on one line).
    - Line separated: A list of values with one value per line.
- URL: The url to fetch the list from.


### Using lists in an Elasticsearch query ###

To use your new list in a query, simply include the name of the list prepended with a '@'.

![List search](/docs/imgs/list_query.png?raw=true)

When the query is executed, `type:info_log client.ip:(@malwareips)` gets transformed into `type:info_log client.ip:(103.14.120.121 OR 103.19.89.55 ...)` before being sent to Elasticsearch.
