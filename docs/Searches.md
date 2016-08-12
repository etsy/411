Searches
========

Searches generate Alerts. You can configure multiple Searches for each data source and assign a different schedule to each.


Usage
-----

To create a new Search, click on the 'Create' button on the Search list page. Select a type from the dropdown and hit create.

![Search config](/docs/imgs/search_config.png?raw=true)

Searches have a large number of configuration options. These are broken down into three tabs: Basic, Notifications and Advanced. For specifics, see the types listing below. This section will only cover the configurations options that available to all Search types.


### Basic ###

![Search basic config](/docs/imgs/search_basic_config.png?raw=true)

The 'Basic' tab contains all the configuration for what and when the Search runs:

- Description: A helpful description of what the Search does.
- Category: A predefined category to group generated Alerts under.
- Tags: A series of tags to categorize this Search.
- Priority: A priority for generated Alerts.
- Frequency: How often to execute this Search. (You can alternatively specify a cron expression)
- Status: Whether this Search is enabled.


### Notifications ###

![Search notifications config](/docs/imgs/search_notifications_config.png?raw=true)

The 'Notifications' tab contains all the configuration for when and who emails are sent to.

- Notification Type: Whether to send out alert emails.
    - On demand: As Alerts come in.
    - Hourly: A rollup every hour.
    - Daily: A rollup every day.
- Notification Format: Format of Alerts in emails
    - Full: Show action buttons in addition to the contents of the Alert.
    - Content only: Only show the contents of the Alert.
- Assignee: The user or group responsible for the Search.
- Owner: The user responsible for maintaining the Search.
- Source Link: A [SEL](https://symfony.com/doc/current/components/expression_language/syntax.html) expression to specify a custom 'Source' link for generated Alerts.


### Advanced ###

![Search advanced config](/docs/imgs/search_advanced_config.png?raw=true)

The 'Advanced' tab contains more complex functionality, like Filters and Targets.

- Autoclose: Whether to automatically resolve Alerts that don't see any activity for some time.
- [Filters](/docs/Filters.md): A list of Filters to execute on Alerts.
- [Targets](/docs/Targets.md): A list of Targets to send Alerts to.



Types
-----

### Null ###

![Null Search](/docs/imgs/search_null.png?raw=true)

Generates a dummy Alert with the content `{null: "null"}`.


### Elasticsearch (Logstash & Alerts) ###

![Logstash Search](/docs/imgs/search_logstash.png?raw=true)

Queries an Elasticsearch cluster. Each document returned by ES generates an Alert. Check [here](/docs/ESQuery.md) for information on the syntax.

- The Logstash type allows you to query a logstash index.
- The Alert type allows you to query the 411 alerts index. (Generating alerts on your alerts)

#### Additional Fields ####

- Result Type: The type of data to return.
    - Fields: Return the individual fields from ES.
    - Count: Return a count of how many results were received.
    - No results: Return an Alert if __NO__ results where received.
- Result Filter: A basic filter on the results that are return. Only valid for the `Fields` and `Count` result types.
- Fields: The list of fields to return from ES. Only valid for the `Fields` result type.
- Time Range: How far back to query.


### ThreatExchange ###

![ThreatExchange Search](/docs/imgs/search_threatexchange.png?raw=true)

Queries [ThreatExchange](https://developers.facebook.com/products/threat-exchange/). Searches can be run for malware or threats on a specific timeframe. To do an exact match, specify the ID of the resource to retrieve.

#### Additional Fields ####

- Search Type: The type of result to return.
    - Malware: Return malware entries.
    - Threat: Return threat indicator entries.
- Query: Free form text to do a fuzzy search on.


### HTTP ###

![HTTP Search](/docs/imgs/search_http.png?raw=true)

Executes a HTTP `GET` request against a URL. If the response code is unexpected, generates an Alert.

#### Additional Fields ####

- URL: The URL to test.
- Code: The expected HTTP response code.


### Ping ###

![Ping Search](/docs/imgs/search_ping.png?raw=true)

Fires off an ICMP ping against a host. If the ping fails, generates an Alert.

#### Additional Fields ####

- Host: The host to test.

