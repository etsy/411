Development
===========

411 consists of a frontend single-page Javascript application, and a backend written in PHP. The two components communicate via the REST API.


Frontend
--------

The frontend allows you to view and process data within 411. It makes use of Bootstrap, Backbone.js, Handlebars, and jQuery. Before starting any work, you should execute `grunt dev` to enable source assets.

### Interesting Files and Directories ###

- `htdocs/index.html`: Entrypoint. References and loads in all of the assets.
- `htdocs/assets/css/main.css`: CSS.
- `htdocs/assets/js`: Javascript code.
    - `main.js`: Entrypoint.
    - `libs`: External libraries.
    - `templates.js`: References all templates. If you add a new template, make sure to add a reference to it here.
    - `app.js`: Application object. Contains all global functionality. (Ajax, view management, etc)
    - `views`: View controllers for each page of the frontend.
    - `router.js`: Route definitions for the frontend.


Backend
-------

The backend implements all the REST endpoints that the frontend communicates with. Additionally, it's responsible for scheduling jobs (like Searches), generating Alerts and sending email notifications. There are several options for developing new features:

- You can create a `extlib` directory in the root of the repo. The autoloader will search this before the `phplib` directory, effectively allowing you to override classes.
- You can add a `hook.php` file, which gets called during initialization. Here, you can hook certain calls. To get a list of available hooks, search the codebase for calls to `Hook::call`.


### Interesting Files and Directories ###

- `bin`: Various executable scripts.
- `htdocs/api`: All the entrypoints for the API endpoints.
- `phplib`: Application logic.
    - `411bootstrap.php`: Initializes the environment.
    - `Controller/Data.php`: Generates bootstrap data for the frontend.
    - `Notification.php`: Email logic.
    - `REST`: Logic for all the API endpoints.
    - `Scheduler.php`: Generates jobs for worker processes to execute. This includes Searches, Rollups, Summary emails, etc.
- `templates`: Email templates.


#### Scheduler ####

The scheduler runs every minute and generates jobs for execution and then exits. This separates the creation and execution of jobs, making re-executing and scheduling jobs much easier.


#### Worker ####

A worker runs scheduled jobs. The job system will automatically retry failed jobs up to a limit. 411 supports running multiple workers simultaneously, but running one is usually sufficient.


#### Notifications ####

Email notifications are sent out for all important events within 411. This includes:

- New Alerts
- Actions taken on existing Alerts
- Search failure
- Rollups

Templating is done is regular PHP, and the templates can be found in the `templates` directory. Check out the `render` method for details on how rendering is done.


#### Models ####

411 comes with a simple ORM for managing objects. It's used throughout the codebase, and implements most standard ORM features.

- Object management (Create/Modify/Delete)
- Soft deletion of Objects
- Array lookup of Object attributes
- Validation of fields
- Basic Finder for finding specific Models.

Note: The ORM assumes that 0 is an invalid pkid.

Creating a new Model is simple and requires defining a few attributes and methods.
```
class Thing extends Model {
    public static $TABLE = 'things';
    public static $PKEY = 'thing_id';

    public function doThing() {
        return $this->obj['name'];
    }

    protected static function generateSchema() {
        return [
            'name' => [self::T_STR, null, '']
        ];
    }
}

class ThingFinder extends ModelFinder {
    public static $MODEL = 'Thing';
}
```

For more information, check out the `phplib/Model.php` file. Some usage examples:

Creating a new Model
```
$thing = new Thing;
$thing['name'] = 'bob';
$thing->store();
```

Retrieving a Model
```
$thing = ThingFinder::getById($id);
```

Deleting a Model
```
$thing->delete();
```


Setup
-----

### Additional dependencies ###

- NPM
- Bower
- Grunt

Ubuntu Packages:
```
$ sudo apt-get install nodejs-legacy npm
```

Fedora Packages:
```
$ sudo dnf install nodejs
```

Install Grunt & Bower:
```
$ sudo npm install -g grunt-cli bower
```

Install dependencies:
```
$ npm install
$ bower install
$ composer install
```

Setup assets:
```
$ grunt dev
```


Building a release
------------------

To build a release, run:
```
$ bin/generate_release.sh
```


PHPDocs
-------

To build the docs, run:
```
$ grunt docs
```


Tests
-----

To execute the tests, run:
```
$ grunt tests
```


Codelabs
--------

- [Creating a new Search type](/docs/Development/NewSearchType.md)
- [Creating a new Filter type](/docs/Development/NewFilterType.md)
- [Creating a new Target type](/docs/Development/NewTargetType.md)
- [Creating a new Enricher & Renderer type](/docs/Development/NewEnricherRendererType.md)
- [Creating a new Script Filter](/docs/Development/NewScriptFilter.md)
- [Adding List support to a Search](/docs/Development/ListSupport.md)
