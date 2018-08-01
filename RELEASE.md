# v1.5.0 #

- Merged all development branches into master


# v1.4.2 #

- No new features

## Bugfixes ##

- Fixed issues with previous release


# v1.4.1 #

- Merged #136 for content matching in HTTP searches
- Delete associated alerts when a search is deleted
- Finish up custom timezone implementation

## Bugfixes ##

- Fix exact match queries
- Fix ES source link generation
- Set auth cookie in proxy-auth mode
- Escape content in Slack target
- Misc perf/UI fixes


# v1.4.0 #

- Per-user timezones
- Email renderers

## Bugfixes ##

- Show original value if a renderer throws an error
- Correctly update index for alerts associated with deleted searches
- Parse dates as UTC on the frontend
- Performance fixes


# v1.3.3 #

- No new features

## Bugfixes ##

- Fix PHP error on executing `bin/es_sync.php`


# v1.3.2 #

- Announcement support on the index page

## Bugfixes ##

- Fixed error when running scheduler
- Fix ES ssl connection initialization
- Fix error in Slack target #118


# v1.3.1 #

- No new features

## Bugfixes ##

- Don't specify a domain when setting cookies. This allows 411 to work when the hostname doesn't match the site configuration


# v1.3.0 #

- Support for ES5.0
- Merged in support for running 411 behind an auth proxy #95 #79
- Filter/Target errors are no longer considered a failure (Search jobs will not be rescheduled as a result)
- Render long alerts vertically in emails
- Update dependencies

## Bugfixes ##

- Fixed changelog modal not appearing
- Fixed undefined `setPassword` call in `create_user` script


# v1.2.0 #

- Added Search execution options on the configuration page
- Added support for index patterns
- Added options for parsing date fields
- Refactored Search code to support multiple 'sources' per Search type
- More UX tweaks

## Breaking changes ##

- The `Logstash` Search type has been replaced by the `ES` Search type
- The config syntax for specifying indices has changed
- The config syntax for defining Search sources has changed

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
