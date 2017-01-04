# v1.2.0 #

- Added Search execution options on the configuration page
- Added support for index patterns
- Added options for parsing date fields
- Refactored Search code to support multiple 'sources' per Search type.
- More UX tweaks

## Breaking changes ##

- The `Logstash` Search type has been replaced by the `ES` Search type.
- The config syntax for specifying indices has changed.
- The config syntax for defining Search sources has changed.

See the [upgrade guide](/docs/Upgrading.md) for details.


# v1.1.0 #

- Added support for API keys
- Added Push Search for pushing Alerts into 411
- Added support for MySQL
- Added Pagerduty target (#71)
- UX tweaks
- Misc bug fixes


# v1.0.1 #

First release
