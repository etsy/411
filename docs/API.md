API
===

Each user in 411 has an API key they can use for programmatic access to the application. You can grab the key from your user page (as well as regenerate it there).

To submit an API request, send the key via the `X-API-KEY` header. You can test that you're sending requests correctly via the ping endpoint.

Example:
```
$ curl -v -H 'X-API-KEY: API_KEY_HERE' https://HOSTNAME/api/ping
{"data":"pong","success":true,"message":"","authenticated":true}
```

This documentation is a stub. Check out the [source](https://github.com/etsy/411/tree/master/phplib/REST) for additional information on each endpoint.


Endpoints
---------

### General ###

#### `GET /api/ping` ####

Ping endpoint.


#### `POST /api/login` ####

Login endpoint.


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


#### `* /api/alertlog` ####

CRUD methods for Alert change logs.


#### `POST /api/enrich` ####

Apply enrichers to field data.


### Searches ###

#### `* /api/search` ####

CRUD methods for Searches.


#### `* /api/search/SEARCH_ID/log` ####

CRUD methods for Search change logs.


#### `* /api/search/SEARCH_ID/filter` ####

CRUD methods for Search filters.


#### `* /api/search/SEARCH_ID/target` ####

CRUD methods for Search targets.


### Groups ###

#### `* /api/group` ####

CRUD methods for Groups.


#### `* /api/group/GROUP_ID/target` ####

CRUD methods for Group targets.


### Jobs ###

#### `* /api/job` ####

CRUD methods for Jobs.


### Reports ###

#### `* /api/report` ####

CRUD methods for Reports.


#### `* /api/report/REPORT_ID/target` ####

CRUD methods for Report target.


### Lists ###

#### `* /api/list` ####

CRUD methods for Lists.


### Users ###

#### `* /api/user` ####

CRUD methods for Users.
