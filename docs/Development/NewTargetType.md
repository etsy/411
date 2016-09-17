Creating a new Target type
==========================

This code lab teaches you how to write a new 411 Target type. Why would you want to implement a Target? You might want to export certain Alerts to an external service. Writing a Target gives you a structured way to do that. For this lab, we'll be re-implementing the `WebHook` Target.


Setup
-----

Copy the contents of `phplib/Target/Null.php` into `MyWebHook.php`. Open it up and replace all instances of `Null` with `MyWebHook` and `null` with `mywebhook`. When implementing a new Target type, you have to implement the following methods: `generateDataSchema`, `validateData`, `process` and `finalize`. You might've noticed that these are the exact same methods that Filters require. As a matter of fact, Filters and Targets are structurally very similar. The main difference is how they operate on input Alerts. Check `phplib/Target.php` for detailed information on these and other methods in the Target class.


Implementing `generateDataSchema`
---------------------------------

The `generateDataSchema` method defines all the parameters that this Target type accepts. For this Target, all we want is a URL to POST the data to. Note that this method uses the same field definition syntax as `generateSchema`.
```
    protected static function generateDataSchema() {
        return [
            'url' => [static::T_STR, null, '']
        ];
    }
```


Implementing `validateData`
---------------------------

The `validateData` method verifies that the parameters passed to the Target are valid. We simply need to verify that the URL we were provided is actually valid. Additionally, We make sure to call the parent `validateData` method.
```
    public function validateData($data) {
        parent::validateData($data);

        if(!filter_var($data['data']['url'], FILTER_VALIDATE_URL)) {
            throw new ValidationException('Invalid url');
        }
    }
```


Implementing `process`
----------------------

The `process` method accepts an Alert and sends it to the external service. In our case, we don't want to send one request for every Alert that is passed it. With a 1000 Alerts, that would be 1000 requests! Thus, we'll first declare an attribute to cache Alerts.
```
    private $list = [];
```

We'll additionally define a helper function, `send`, to do the actual request. The method will serialize the current batch of Alerts and then POST it to the endpoint. Lastly, it should the cache so we can process the next batch.
```
    private function send() {
        // Only POST if we have at least 1 Alert to send.
        if(!count($this->list)) {
            return;
        }

        $curl = new \Curl\Curl;
        $curl->setHeader('Content-Type', 'application/json; charset=utf-8');
        $raw = $curl->post($this->obj['data']['url'], json_encode($this->list));
        $ret = null;
        if($curl->httpStatusCode != 200) {
            throw new TargetException(sprintf('Remote server returned %d', $curl->httpStatusCode));
        }
        $this->list = [];
    }
```

Then, we'll finally write `process`. We simply add the current Alert to the cache and call `send` if the cache gets big enough.
```
    public function process(Alert $alert) {
        $this->list[] = $alert;

        if(count($this->list) >= 100) {
            $this->send();
        }
    }

```


Implementing `finalize`
-----------------------

The `finalize` method finishes processing and returns any Alerts that may be cached inside the Target (and then clears the list). Our implementation of `finalize` is very simple, because `send` does all the work for us. We just need to call `send` in case we have any Alerts left.
```
    public function finalize() {
        $this->send();
    }
```


Update the list of Target types
-------------------------------

Open up `phplib/Target.php`. You should see a line with a list of Target types. The last step is to add our new Target type to that list.
```
    public static $TYPES = [..., 'MyWebHook_Target'];
```

We're all done! To test, open up a Search and try adding the new `MyWebHook` Target.
