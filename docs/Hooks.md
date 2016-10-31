Hooks
=====

411 implements a hooking system which allows you to intercept any of the registered hooking points and modify variables.

A hooking site is defined by `Hook::call`, which will invoke any registered listeners. Each listener is passed an array of arguments which it can then inspect or optional modify.


Registering a listener
----------------------

To register a new hook, first create a file called `hook.php` in the base 411 directory.

You can then register listeners by calling `Hook::register` with a function reference in this file. Your listener should accept a single argument consisting of an array of values. It can make changes to these values but must make sure to return the modified values.


### Example ###

Say you wanted to hook `auth.init` to prevent logins to any users that are not an admin. If we look in the `Auth` class, we'll see that the `auth.init` hook is passed a single `$user` parameter. Armed with this information, we can add the following listener:

```
Hook::register('auth.init', function ($args) {
    list($user) = $args;

    // Undefine $user if not an admin.
    if(!is_null($user) && !$user['admin']) {
        $user = null;
    }

    return [$user];
}
```


List of hooks
-------------

- assignee.emails
- auth.init
- auth.login
- db.connect
- db.disconnect
- db.query
- init.post
- init.pre
- job.end
- job.start
- mail
- model.create
- model.delete
- model.update
- rest.data
- search.filters
- search.targets

This list was generated with the following command: `grep -Por 'Hook::call\(.+?\)' phplib | awk -F\' '{ print $2; }' | sort | uniq`.
