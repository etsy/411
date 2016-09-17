Creating a new Filter type
==========================

This code lab teaches you how to write a new 411 Filter type. Why would you want to implement the logic here instead of in the Search? You might want this functionality to be available to any Search. Writing a Filter lets you do exactly that. For this lab, we'll be re-implementing the `Regex` Filter.


Setup
-----

Copy the contents of `phplib/Filter/Null.php` into `MyRegex.php`. Open it up and replace all instances of `Null` with `MyRegex` and `null` with `myregex`. When implementing a new Filter type, you have to implement the following methods: `generateDataSchema`, `validateData`, `process` and `finalize`. Check `phplib/Filter.php` for detailed information on these, and other methods in the Filter class.


Implementing `generateDataSchema`
---------------------------------

The `generateDataSchema` method defines all the parameters that this Filter type accepts. For this Filter, we want a key to filter on, a regular expression and a flag indicating whether we should whitelist or blacklist matching entries. Note that this method uses the same field definition syntax as `generateSchema`.
```
    protected static function generateDataSchema() {
        return [
            'include' => [static::T_BOOL, null, true],
            'key' => [static::T_STR, null, '*'],
            'regex' => [static::T_STR, null, '']
        ];
    }
```


Implementing `validateData`
---------------------------

The `validateData` method verifies that the parameters passed to the Filter are valid. The only value that we need to verify is the `regex` itself. We can grab the string and verify that it compiles correctly. We also need to call the parent `validateData` method.
```
    public function validateData($data) {
        parent::validateData($data);

        $regex = $data['data']['regex'];
        if(@preg_match("/$regex/", null) === false) {
            throw new ValidationException('Invalid regex');
        }
    }
```


Implementing `process`
----------------------

The `process` method accepts an Alert and determines whether to forward it to the next step. In our case, we want to check if the value under the key we've specified matches the regex. If so, we whitelist or blacklist depending on the `include` flag. We also add some special processing if the `key` is `*`. In that scenario, we'll consider a match under any of the keys to be valid.
```
    public function process(Alert $alert) {
        $data = $this->obj['data'];
        $include = Util::get($data, 'include', true);
        // Generate a list of keys to check.
        $keys = [Util::get($data, 'key', '*')];
        if ($keys[0] == '*') {
            $keys = array_keys($alert['content']);
        }
        $regex = Util::get($data, 'regex', '');

        // Test against each key.
        foreach ($keys as $key) {
            $match = preg_match("/$regex/", Util::get($alert['content'], $key, ''));
            if (!$match) {
                continue;
            }
            // If there's a match, we know enough to return.
            if ($include) {
                return [$alert];
            } else {
                return [];
            }
        }

        // Reached the end without matching.
        if ($include) {
            return [];
        } else {
            return [$alert];
        }
    }
```


Implementing `finalize`
-----------------------

The `finalize` method finishes processing and returns any Alerts that may be cached inside the Filter (and then clears the list). The intention is that certain types of Filters may not be ready to determine if a given Alert should be filtered in `process`. They might need all the Alerts to make this decision. In our case, we DO have all the information we need in `process`. Thus, we can leave this method blank.


Update the list of Filter types
-------------------------------

Open up `phplib/Filter.php`. You should see a line with a list of Filter types. The last step is to add our new Filter type to that list.
```
    public static $TYPES = [..., 'MyRegex_Filter'];
```

We're all done! To test, open up a Search and try adding the new `MyRegex` Filter.
