Glossary
========

User
----

A user of 411. Users can be set as the owner and/or assignee of a Search. They can be added to Groups.


Group
-----

A list of Users and email addresses. Groups can be set as the assignee of a Search.


Owner
-----

A User. The owner of a Search is responsible for maintaining it.


Assignee
--------

Either a User or a Group. Each Search and Alert has an associated Assignee. Whenever 411 generates an Alert the Search's assignee is copied onto the Alert. This means the Alert's assignee can be changed independently. Additionally, email notifications for Alerts are emailed to their Assignees.


Search
------

Queries a data source and generates Alerts to be reviewed. Each Search type connects to a different data source. Ex: Elasticsearch vs HTTP. Each Search can be disabled/enabled on an individual basis.


Alert
-----

A noteworthy event. When an Alert is generated, the Assignee gets an email and the Alert is assigned to them in 411. A user can review the Alert and take actions on it within 411.


Filter
------

Executes some logic on a stream of Alerts and filters out or adds new Alerts. Think `grep` or `sed` in a UNIX pipeline.


Target
------

Saves or forwards the Alert to some data store. Think output redirecting in a UNIX pipeline.


Enricher
--------

Adds additional data to an Alert. Enrichers primarily exist to provide data for Renderers. Enrichers are applied dynamically and so are not available in the email notifications that 411 sends out. For this use case, there is the Enriher Filter, which allows you to apply an Enricher during the Search pipeline.


Renderer
--------

Takes an Alert field and renders it, usually providing additional context. This may be as simple as clickifying all urls in a field. Or, it may be as complicated as adding an entire table of data. Some Renderers are entirely frontend-based, whereas others query a backend Enricher for data before rendering it.
