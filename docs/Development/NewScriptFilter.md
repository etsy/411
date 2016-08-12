Creating a new Script Filter
============================

This (short) code lab teaches you how to write a new 411 Script Filter. To recap, Filters allow you to filter out or insert additional Alerts into the Search pipeline. However, you might not always want to write this code in PHP. Script Filters are simply executables that 411 executes to filter Alerts. They can be written in any language (you just need to mark the file as executable). For this lab, we'll be implementing a script that removes any fields that look like passwords. We could use any language, but we'll be sticking with PHP.


Setup
-----

Create the directory `phplib/Filter/Script/password` and create a file called `init` in that directory. Make sure to mark it as executable.
```
chmod +x phplib/Filter/Script/password/init
```

At this point, you have a valid Script! It's rather useless, so let's continue.


Implementing the logic
----------------------

Scripts get passed a single Alert as a JSON blob on `STDIN`. First, we'll decode it.
```
<?php

$data = json_decode(file_get_contents('php://stdin'), true);
```

Next we'll iterate over all the keys and delete any that look like a password.
```
foreach(array_keys($data) as $key) {
    if(in_array(strtolower($key), ['pwd', 'passwd', 'password', 'pw'])) {
        unset($data[$key]);
    }
}
```

Finally, we'll spit out the modified data on `STDOUT`.
```
print json_encode($data);
```


Update the list of Script Filter
--------------------------------

Open up `phplib/Filter/Script.php`. There should be a mapping of available scripts in the file. The last step is to add our new Script to the mapping.
```
    public static $SCRIPTS = [..., 'password' => 'Password'];
```

We're all done! To test, add a Script Filter to a Search and pick our new `Password` type.
