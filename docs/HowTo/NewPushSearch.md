Setting up Push Alerts
======================

To push Alerts into 411 (from another alert system), you'll need to configure a "Push" Search. Once you've saved the new Search, you should be provided with a push url.

![Push url](/docs/imgs/search_push_url.png)

This is the endpoint you'll have to push Alerts to. You'll also need your API key, which you can grab via your user page.

![User API Key](/docs/imgs/user_api.png)

With these two pieces of information, you're ready to start shipping alerts into 411. The `/api/alert/push` endpoint accepts a JSON array of objects with the following fields:

- `alert_date`: Unix timestamp indicating when the event occurred.
- `content`: The data associated with this Alert.

See the [API documentation](/docs/API.md) for additional details on generating API queries.

Example:

```
$ curl -H 'X-API-KEY: API_KEY_HERE' 'https://HOSTNAME/api/alert/push?search_id=1' -d '[{"alert_date": 1476057600, "content": {"a": "b"}}]'
```
