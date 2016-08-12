Alerts
======

Alerts are the reason you're using 411! Each generated Alert should hopefully represent some event that you are interested in and need to respond to. 411 contains a workflow for reviewing Alerts, pulling up additional information and resolving Alerts.


States
------

Alerts can be in one of three states:

1. New
2. In progress
3. Resolved

Generally, when you're investigating an Alert, you'll want to move through the stages sequentially. 411 doesn't enforce so you can immediately resolve Alerts without first marking them as in progress.


Reviewing Alerts
----------------

![Alerts page](/docs/imgs/alerts.png?raw=true)

The Alerts management page displays Alerts in the following order:

1. Assignee (Unassigned / Users / Groups)
2. Priority (High / Medium / Low)
3. Search
4. Status (New / In Progress / Resolved)

The search bar at the top allows you find specific Alerts.

![Alerts search](/docs/imgs/alerts_search.png?raw=true)

It's backed by Elasticsearch, so you can make use of standard lucene shorthand. Here's a list of valid fields you can query on:

- alert_date: The date the Alert occurred.
- assignee_type: The type of assignee of the Alert.
- assignee: The assignee of the Alert.
- search_id: The id of the Search.
- state: The state of the Alert.
    - 0: New
    - 1: In progress
    - 2: Resolved
- resolution: The resolution of the Alert (if resolved).
    - 0: No action
    - 1: Action taken
    - 2: Too old
- content.*: Any data within the Alert.
- tags: Any tags from the Search.
- priority: The priority of the Search.
    - 0: Low
    - 1: Medium
    - 2: High
- category: The category of the Search.
- owner: The owner of the Search.
- notes: Any notes that have been left on the Alert.

Additionally, the date pickers support Elasticsearch's date math, so you can specify expressions like `now-1d`.

Any matching Alerts will show up on the center of the page, grouped as described above.

![Alert group](/docs/imgs/alerts_group.png?raw=true)

Each Alert has a 'Source' button, that will take you to the actual data that generated the Alert. You can configure this link via the Search configuration page. Additionally, there's 'View' button, which will allow you to inspect just that one Alert.

You can select multiple alerts and then take action on them via the action bar at the bottom of the screen.

![Alerts actions](/docs/imgs/alerts_actions.png?raw=true)

Here's a comprehensive list of actions that can be taken on an Alert.

- Send: Send any selected Alerts to a Target.
- Whitelist: Add a Filter to the Search to ignore any Alerts that match this one exactly.
- Compare: Only display the selected Alerts.
- Assign to *: Assign the selected Alerts to a specific User (or unassign it).
- Unresolve: Mark the selected Alerts as new.
- Acknowledge: Mark the selected Alerts as being investigated.
- Resolve: Mark the selected Alerts as resolved.
- Add Note: Leave a comment about the selected Alerts.

Most of the actions you can take will bring up a modal where you can write leave a note about the action you're taking.

![Alerts action](/docs/imgs/alerts_action.png?raw=true)


Reviewing a single Alert
------------------------

![Alert](/docs/imgs/alert.png?raw=true)

The Alert management page contains much of the same functionality albeit just for that one Alert you're viewing. The entire first half of the page is dedicated to showing the contents of the Alert.

The biggest difference between the two pages is the changelog, which shows all actions that have been taken on this particular Alert.

![Alert changelog](/docs/imgs/alert_changelog.png?raw=true)

The action bar is also available here, with the same actions.

![Alert actions](/docs/imgs/alert_actions.png?raw=true)

You can pull up additional information about each of the fields in the Alert. To find out how, check out the documentation on field [Renderers](/docs/Renderers.md).
