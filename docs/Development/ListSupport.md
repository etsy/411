Adding List support to a Search
===============================

This code lab teaches you how to add support for Lists to an existing Search. Lists are useful because they allow you to make use of variables in your Searches. For this lab, we'll be adding list support to the Ping Search.


Setup
-----

At the moment, the Ping Search currently doesn't support multiple hosts. Let's fix that. Open up the Search and replace the implementation of `_execute` with the following:
```
    protected function _execute($date, $constructed_qdata) {
        $hosts = Util::get($constructed_qdata, 'hosts');
        $alerts = [];

        foreach($hosts as $host) {
            $output = exec('/bin/ping -w 1 -c 1 ' . escapeshellarg($host), $rdata, $rcode);
            $this->obj['last_status'] .= $output;

            if($rcode == 0) {
                continue;
            }

            $alert = new Alert;
            $alert['alert_date'] = $date;
            $alert['content'] = ['host' => $host];
            $alerts[] = $alert;
        }

        return $alerts;
    }
```


Implementation
--------------

When adding support for lists, you're responsible for parsing the query and extracting the name of the lists. Once you've parsed out the list names, you can pass them to `getListData` to get their contents. We'll use `@` to denote a list in the query and add this logic to the `constructQuery` method.
```
    protected function constructQuery() {
        $qdata = $this->obj['query_data'];

        if(preg_match('/^@(\w+)$/', $qdata['host'], $matches)) {
            $key = $matches[1];
            $qdata['hosts'] = $this->getListData([$key])[$key];
        } else {
            $qdata['hosts'] = [$qdata['host']];

        return $qdata;
    }

```

That's all! The Ping Search should now support lists. You can test this out by registering a new list in 411 (say, `host_list`). Create a new Ping search and specify the name of the list (`@host_list`) as the host. If everything is set up correctly, it should ping all of the hosts in the list!
