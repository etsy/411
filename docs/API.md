API
===

Each user in 411 has an API key they can use for programmatic access to the application. You can grab the key from your user page (as well as regenerate it there).

To submit an API request, send the key via the `X-API-KEY` header. You can test that you're sending requests correctly via the ping endpoint.

Example:
```
$ curl -H 'X-API-KEY: API_KEY_HERE' https://HOSTNAME/api/ping
{"data":"pong","success":true,"message":"","authenticated":true}
```

This documentation is a stub. Check out the [source](https://github.com/etsy/411/tree/master/phplib/REST) for additional information on each endpoint.


Endpoints
---------

### General ###

#### `GET /api/ping` ####

Ping endpoint.


#### `POST /api/login` ####

Login endpoint. (You'll probably never need to hit this)

- `name`: Username.
- `password`: Password.

#### `* /api/admin` ####

Get and set global settings


### Data ###

#### `GET /api/dashboard` ####

Get dashboard data.


#### `GET /api/data` ####

Get bootstrap data.


#### `GET /api/jira` ####

Get JIRA integration data.


#### `GET /api/health` ####

Get health data.


### Alerts ###

#### `* /api/alert` ####

CRUD methods for Alerts.

#### `GET /api/alert/ids` ####

Get the ids of matching Alerts.

- `query`: A Lucene query.
- `from`: From timestamp.
- `to`: To timestamp.

#### `GET /api/alert/:id/link` ####

Get the source link for this Alert.

#### `GET /api/alert/bootstrap` ####

Get Alert bootstrapping data.

- `query`: A Lucene query.
- `from`: From timestamp.
- `to`: To timestamp.

#### `GET /api/alert/query` ####

Get matching Alerts.

- `query`: A Lucene query.
- `from`: From timestamp.
- `to`: To timestamp.

#### `POST /api/alert/send` ####

Send a list of Alerts to a Target.

- `target`: Target configuration data.
- `ids`: A list of Alerts ids.

#### `POST /api/alert/whitelist` ####

Whitelist the specified Alerts.

- `lifetime`: Lifetime of this whitelist in seconds.
- `description`: Description
- `ids`: A list of Alerts ids.

#### `PUT /api/alert/escalate` ####

Change escalation state of the specified Alerts.

- `note`: Description
- `escalated`: The new escalation state.
- `ids`: A list of Alerts ids.

#### `PUT /api/alert/switch` ####

Change state of the specified Alerts.

- `note`: Description
- `state`: The new state.
- `ids`: A list of Alerts ids.

#### `PUT /api/alert/assign` ####

Change assignee of the specified Alerts.

- `note`: Description
- `assignee_type`: The type of assignee.
- `assignee`: The assignee id.
- `ids`: A list of Alerts ids.

#### `PUT /api/alert/note` ####

Add a note to the specified Alerts.

- `note`: Description
- `ids`: A list of Alerts ids.

#### `POST /api/alert/push?search_id=:id` ####

Push Alerts into 411.

An array of alerts.


### Alert Logs ###

#### `* /api/alertlog` ####

CRUD methods for Alert change logs.


#### `POST /api/enrich` ####

Apply enrichers to field data.


### Searches ###

#### `* /api/search` ####

CRUD methods for Searches.

#### `GET /api/search/:id/stats` ####

Get stats on this Search.

#### `POST /api/search/:id/test` ####

Test this Search.

- Changed fields.

#### `POST /api/search/:id/execute` ####

Execute this Search.

- Changed fields.


### Search Logs ##

#### `* /api/search/:id/log` ####

CRUD methods for Search change logs.


### Search Filters ###

#### `* /api/search/:id/filter` ####

CRUD methods for Search filters.

#### `POST /api/search/:id/filter/validate` ####

Validate the filter.

- Modified fields.


### Search Targets ###

#### `* /api/search/:id/target` ####

CRUD methods for Search targets.

#### `POST /api/search/:id/target/validate` ####

Validate the target.

- Modified fields.


### Groups ###

#### `* /api/group` ####

CRUD methods for Groups.

#### `* /api/group/:id/target` ####

CRUD methods for Group targets.


### Jobs ###

#### `* /api/job` ####

CRUD methods for Jobs.


### Reports ###

#### `* /api/report` ####

CRUD methods for Reports.

#### `GET /api/report/:id/generate` ####

Generate this report.

- `mode`: `csv` or `pdf`.

#### `POST /api/report/:id/generate` ####

Generate this report.

- `mode`: `csv` or `pdf`.
- Modified fields.

#### `* /api/report/:id/target` ####

CRUD methods for Report target.


### Lists ###

#### `* /api/list` ####

CRUD methods for Lists.

#### `GET /api/list/:id/info` ####

Get information on a list.


### Users ###

#### `* /api/user` ####

CRUD methods for Users.
