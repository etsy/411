Creating a new Search type
==========================

This code lab teaches you how to write a new 411 Search type. Why would you want to do this? Say you want to query a data source that 411 doesn't support. In that case, you'll need to write a new Search type to integrate with that source! For this lab, we'll be re-implementing `Ping_Search`.


Setup
-----

Copy the contents of `phplib/Search/Null.php` into a new file named `MyPing.php`. Open it up and replace all instances of `Null` with `MyPing` and `null` with `myping`. When implementing a new Search type, you have to implement the following methods: `isWorking`, `validateData`, `constructQuery`, `_execute`, `isTimeBased` and `getLink`. Check out `phplib/Search.php` for detailed information on these, and other methods in the Search class.


Implementing `isWorking`
------------------------

The `isWorking` method reports whether this Search type is currently available. This is used on `/api/health` endpoint, which reports information on the overall state of 411. In our case, we'll consider the Search to be working if the `ping` executable exists.
```
    public function isWorking() {
        return file_exists('/bin/ping');
    }
```


Implementing `validateData`
------------------------

The `validateData` method verifies that the parameters passed to the Search are valid. For our Search, we need to verify that the `host` we are given is valid. To accomplish this, we can attempt to resolve the string we're given. We should also make sure to call the parent `validateData` method.
```
    public function validateData($data) {
        parent::validateData($data);

        if(gethostbynamel(Util::get($data['query_data'], 'host')) === false) {
            throw new ValidationException('Invalid host');
        }
    }
```


Implementing `constructQuery`
-----------------------------

The `constructQuery` method processes the `query_data` of the Search and returns a formatted query. This is useful if the query needs some manipulation before it can be used. This method is automatically executed when you call `execute` (and the results are passed to `_execute`). We don't have to make any modifications for this Search, so we'll just return everything.
```
    protected function constructQuery() {
        return $this->obj['query_data'];
    }
```


Implementing `_execute`
------------------------

The `_execute` method contains the actual logic for the Search and returns an array of Alerts. For our Search, we want to execute the `ping` command and check the return value. A non-zero return value indicates an error, so we'll generate an Alert, fill it with relevant data, and return it. Otherwise, the ping succeeded, and no Alerts are generated. Again, note that `_execute` returns an array of Alerts. This isn't particularly useful for this Search, but other Search types could return multiple Alerts per execution.
```
    protected function _execute($time, $constructed_qdata) {
        // Run ping.
        $host = Util::get($constructed_qdata, 'host');
        $output = exec('/bin/ping -w 1 -c 1 ' . escapeshellarg($host), $rdata, $rcode);
        $this->obj['last_status'] = $output;

        // If success, there's no problem. Just return.
        if($rcode == 0) {
            return [];
        }

        // Otherwise, something is wrong. Return an Alert.
        $alert = new Alert;
        $alert['alert_date'] = $time;
        $alert['content'] = ['host' => $host];

        // Remember to return an array!
        return [$alert];
    }
```


Implementing `isTimeBased`
--------------------------

The `isTimeBased` method returns a boolean indicating whether the Search runs on a specific time range. (In other words, you can specify what time range to return results for). If so, 411 will reschedule the Search on failure (it does not otherwise). In the case of pinging a host, it doesn't make any sense to ping a host in the past. Thus, we can just return false.
```
    public function isTimeBased() {
        return false;
    }
```

Implementing `getLink`
----------------------

The `getLink` method is responsible for returning a URL to additional information about the Alert. This is what generates the 'Source' link of an Alert. We'll generate a link to `isup.me` with the hostname in the Alert.
```
    public function getLink() {
        $constructed_qdata = $this->constructQuery();
        return 'http://www.isup.me/' . Util::get($constructed_qdata, 'host');
    }
```


Update the list of Search types
-------------------------------

Finally, we need to register the Search type. Open up `phplib/Search.php` and you should see a list of Search types near the top. Add our new Search type to the list.
```
    public static $TYPES = [..., 'MyPing_Search'];
```

We're all done - with the backend changes, that is. If we try to create a `MyPing` Search on the frontend, it won't actually work correctly. This is because we haven't populated the `host` field within `query_data', which is what `_execute` is expecting. We'll need to make some frontend changes to fix this.


Adding a renderer for our new Search type
-----------------------------------------

On the frontend, `SearchView` is responsible for rendering the UI for the Search page. If a Search type has custom UI, it has to subclass `SearchView` and register itself. Similarly to the `$TYPES` array, there's a list of custom Search renderers on the frontend. You can see the list in `htdocs/assets/js/views/searches/search/load.js`. As before, we'll be using the `Null` Search as a template. Copy `htdocs/assets/js/views/searches/search/null.js` into a file called `myping.js`. Again, replace all instances of `Null` with `MyPing` and `null` with `myping`. The class should have two attributes already defined: `no_query` and `no_range`. These determine, respectively, whether the query field and range field will be displayed. In our case, we don't need either of these fields, so we can keep the current definitions.
```
        no_query: true,
        no_range: true,
```

We're going to need some custom UI, so we'll load in a custom template. The `SearchView` class looks for the following attributes: `addnFieldsATpl`, `addnFieldsBTpl`, `addnFieldsCTpl` ... `addnFieldsFTpl`. If any of these are found, it'll render those templates and insert them into the appropriate place in the main Search template. Check out `htdocs/assets/templates/searches/search.html` to see exactly where these insertion points are. In this case, we want our `host` field to show up at the top, so we'll define `addnFieldsATpl`.
```
        addnFieldsATpl: Templates['searches/search/ping/a'],
```

Lastly, we need to extend the `readForm` method. This is responsible for collecting data from all the fields on the page. First, we call the base `readForm` method to get all the fields. All query parameters need to be a sub-key of `query_data`, but `readForm` doesn't do that for us. Thus, we have to move the `host` key, into `query_data` and remove the original. This will allow the backend to correctly read the `host` field!
```
        readForm: function() {
            var data = SearchView.SearchView.prototype.readForm.call(this);

            if('host' in data) {
                data.query_data.host = data.host;
                delete data.host;
            }

            return data;
        }
```


Adding the necessary template
-----------------------------

Next, we need to create the template that we referenced above. We need an input field for the `host` param along with appropriate labeling and sizing. We'll save this file as `htdocs/assets/templates/searches/search/myping/a.html`.
```
  <div class="col-xs-12 form-group">
    <label for="host">Host</label>
    <input class="form-control" type="text" name="host" value="{{ query_data.host }}" />
  </div>
```


Registering the new template
----------------------------

In `htdocs/assets/js/templates.js`, there is a list of all the templates used in 411. We'll need to add our new template to this list.
```
        'searches/search/myping/a': Handlebars.compile(require('text!templatefiles/searches/search/myping/a.html')),
```


Register the new `MyPing` class
-------------------------------

Again, the list of custom Search renderers is in `htdocs/assets/js/views/searches/search/load.js`. We'll first have to load in our new class.
```
        MyPingSearchView = require('views/searches/search/elasticsearch'),
```

And then we can register it.
```
    SearchView.registerSubclass('myping', MyPingSearchView);
```

Congrats! This time we're done for real. If you log into 411, you should see your new `MyPing` Search.
