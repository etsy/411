Filters
=======

Filters allow you to add, modify or remove Alerts from the Search pipeline. Filters are registered under Searches, with each Search having its own set of Filters. You can freely rearrange the order in which Filters run.


Usage
-----

![Filters config](/docs/imgs/filters_config.png?raw=true)

To create a new Filter, edit a Search and click on the 'Advanced' tab. Select an entry from the dropdown to configure a Filter of that type.

![Filter config](/docs/imgs/filter_config.png?raw=true)

All Filters have the following fields:

- Description: A description of the Filter.
- Lifetime: How long the Filter will live. This allows for Filters which can be used for temporarily whitelisting or blacklisting certain Alerts.

When you've finished configuring the Filters (and Targets) for a Search, make sure to save them by clicking the 'Save Filters and Targets' button.

![Filter & Targets save](/docs/imgs/filterstargets_save.png?raw=true)


Types
-----

### Null ###

Passes Alerts through unchanged.


### Dedupe ###

Eliminates duplicate Alerts. This works off of the Alert's `content_hash` field.

#### Parameters ####

- Range: How many minutes back to detect matching Alerts.


### Throttle ###

Throttles the Search to a set number of Alerts over an interval of time.

#### Parameters ####

- Range: How many minutes back to throttle Alerts.
- Count: The maximum number of Alerts to allow in that range.


### Hash ###

Eliminates Alerts that match the given hashes. This works off of the Alert `content_hash` field. Used by 411 to implement the whitelisting button.

#### Parameters ####

- List: A list of hashes to filter out.


### Regex ###

Whitelist/Blacklist Alerts based on whether they match a given regular expression.

#### Parameters ####

- Include: Whether to whitelist (enabled) or blacklist (disabled) matching Alerts.
- Key: The key to filter. Specify * to filter all keys.
- Regex: The regular expression to execute.


### Script ###

Executes a script on Alerts. Scripts are executables which accept a JSON blob from STDIN and output a JSON blob to STDOUT. This allows for additional post-processing in any programming language.

#### Parameters ####

- Script: The name of the script to execute.


### Enricher ###

Executes an Enricher on a field and replaces the contents of the field with the output.

#### Parameters ####

- Key: The key to execute an Enricher on.
- Enricher: The name of the Enricher to execute.


### Expression ###

Whitelist/Blacklist Alerts based on whether they match a given [SEL](https://symfony.com/doc/current/components/expression_language/syntax.html) expression. The contents of the Alert are available via the `content` variable when writing expressions.

#### Parameters ####

- Include: Whether to whitelist (enabled) or blacklist (disabled) matching Alerts.
- Key: The key to filter. Specify * to filter all keys.
- Regex: The regular expression to execute.


### MapKey ###

Rename individual field names via a [SEL](https://symfony.com/doc/current/components/expression_language/syntax.html) expression. The fields names must match a given regular expression.

#### Parameters ####

- Key_Regex: The regular expression for determining what fields to map.
- Key_Expr: The SEL expression to map the original field name to a new one. `key` and `value` are available within this context.


### MapValue ###

Rename individual field values via a [SEL](https://symfony.com/doc/current/components/expression_language/syntax.html) expression. The fields names must match a given regular expression.

#### Parameters ####

- Key_Regex: The regular expression for determining what fields to map.
- Value_Expr: The SEL expression to map the original field value to a new one. `key` and `value` are available within this context.


`content_hash`
--------------

The `content_hash` of an Alert is the `sha256` hash of the JSON encoded `content`. Note that this does not include the date of the Alert, so Alerts that fire at different times will still be considered equivalent. However, if the Alert `content` contains any changing (like a UUID or a timestamp), any Filters that depend on the `content_hash` will not work correctly!
