Creating a new Enricher & Renderer type
=======================================

This is a two part code lab that teaches you how to write a new 411 Enricher & Renderer type. Why would you want to do either of these things? Sometimes, Alerts aren't very useful with out additional context. Enrichers and Renderers can retrieve this information and display it. Enrichers run on the backend and are responsible for pulling this data. Renderers are in the frontend, and are responsible for taking that data and displaying it. For this lab, we'll be re-implementing `IP_Enricher` and `IP_Renderer`.


Setup (Enricher)
----------------

First, copy the code below into a file called `MyIP.php` in `phplib/Enricher`. Enrichers are very simple -- `process` is the only method that has to be implemented.
```
<?php

namespace FOO;

class MyIP_Enricher extends Enricher {
    public static $TYPE = 'myip';

    public static function process($data) {
        // TODO
    }
}
```


Implementing `process`
------------------------

The `process` method takes some input data (the contents of an Alert field) and returns data as output. For this Enricher, we want to run a geoIP lookup on the input. To do so, we'll just query one of the many web services that provide this information.
```
    public static function process($data) {
        $curl = new \Curl\Curl;
        $ret = $curl->get(sprintf('https://www.telize.com/geoip/%s', $data));
        if($curl->httpStatusCode != 200) {
            throw new EnricherException('Error retrieving data');
        }

        return $ret;
    }
```


Update the list of Enricher types
-------------------------------

Next, we have to register the Enricher. Open up `phplib/Enricher.php` and you should see a line with a list of Enricher types. The final step is to add our new Enricher type to the list.
```
    public static $TYPES = [..., 'MyIP_Enricher'];
```

That's all (on the Enricher side)! We still need to create a new Renderer type so we can access this data on the frontend.


Setup (Renderer)
----------------

Again, copy the code below into a file called `myip.js` in `htdocs/assets/js/views/renderer`.
```
"use strict";
define(function(require) {
    var TableRenderer = require('views/renderer/table');


    var MyIPRenderer = TableRenderer.extend({
        auto: /* TODO */,
        remote: /* TODO */,
        match: function(key, val) {
            // TODO
        },
        render: function(key, val, data) {
            // TODO
        }
    });

    return MyIPRenderer;
});
```

Note that we're subclassing `TableRenderer` here. It offers a convenient method, `tabulate`, which takes an object and returns an HTML table. We can use this functionality instead of subclassing `Renderer` and displaying this information manually.


Defining `auto`
---------------

The `auto` field tells the frontend whether this Renderer should look for fields to process. If so, it will automatically add itself to any fields that it matches on. Let's enable it.
```
        auto: true,
```


Defining `remote`
-----------------

The `remote` field indicates which Enricher this Renderer pulls data from. The Renderer framework will automatically query that Enricher and provide the data. We'll be using the `MyIP_Enricher`, so put `'myip'` goes in this field. For Renderers that don't use an Enricher, just leave it as `null`.
```
        remote: 'myip',
```


Implementing `match`
--------------------

The `match` method determines whether a given field should be automatically processed. In our case, we need to look for fields that look like IP addresses. First, we define a regex.
```
    var IP_RE = /^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;
```

Then we can just test if this regex matches.
```
        match: function(key, val) {
            return IP_RE.test(val);
        }
```


Implementing `render`
---------------------

The `render` method takes the input data and outputs HTML to be rendered. The method accepts three arguments: `key`, `value` and `data`. The argument we're interested in is `data`, which contains the output from the Enricher. We can just pass that to `TableRenderer.tabulate`, which generates the HTML for us!
```
        render: function(key, value, data) {
            return TableRenderer.tabulate(data);
        }
```


Register the new `MyIPRenderer` class
-------------------------------------

The list of Renderers is in `htdocs/assets/js/views/renderer.js`. We'll first have to load in our new class.
```
        MyIPRenderer = require('views/renderer/myip'),
```

And then we can register it.
```
    Renderer.registerSubclass('myip', MyIPRenderer);
```

We're finished! You can test out the new Renderer by opening up an Alert and selecting the `myip` Renderer for a field.
