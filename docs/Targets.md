Targets
=======

Targets allow you to send generate Alerts to other services. Targets (like Filters) are registered under Searches, with each Search having its own set of Targets.

All Targets have the following two fields:

- Description: A description of the Target.
- Lifetime: How long the Target will live. This allows for Targets which can be used to temporarily send Alerts to a destination (like an email address).


Usage
-----

![Targets config](/docs/imgs/targets_config.png?raw=true)

To create a new Target, edit a Search and click on the 'Advanced' tab. Select an entry from the dropdown to configure a Target of that type.

![Target config](/docs/imgs/target_config.png?raw=true)

All Targets have the following fields:

- Description: A description of the Target.
- Lifetime: How long the Target will live. This allows for Targets which can be used for temporarily whitelisting or blacklisting certain Alerts.

When you've finished configuring the (Filters and) Targets for a Search, make sure to save them by clicking the 'Save Filters and Targets' button.

![Filter & Targets save](/docs/imgs/filterstargets_save.png?raw=true)


Types
-----

### Null ###

No-op.


### WebHook ###

Send a HTTP POST with the Alerts. The Alerts are encoded in JSON format and batched into chunks of 1000 per request.

#### Parameters ####

- URL: The endpoint to send the Alerts to.


### Jira ###

Generate a JIRA ticket off of an Alert. The contents of the Alert and a link back to 411 will be included in the ticket description.

#### Parameters ####

- Project: The project to create the ticket under.
- Type: The type of ticket to create.
- Assignee: The user to assign the ticket to.
