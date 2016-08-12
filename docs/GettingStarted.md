Getting Started
===============

This guide will show you everything you need to start using 411!

__Note:__ Before we start, make sure you've installed 411 by following the instructions in the README.


Logging in
----------

![Login page](/docs/imgs/gs_login.png?raw=true)

The first thing you'll need to do get into 411. You should've created a set of credentials while setting up 411. Login with those credentials and you should be taken to the dashboard.

![Dashboard page](/docs/imgs/gs_dashboard.png?raw=true)

This page should be pretty self explanatory. There's a breakdown of Alerts that are currently unresolved and some charts on Alert generation/response. Since this is a new instance, the dashboard won't be too interesting.


Creating a new user & adding it to a group
------------------------------------------

Before we create a Search, let's create a new user! 411 provides some basic user management functionality. Click on the 'Users' button in the header to check it out.

![Users page](/docs/imgs/gs_users.png?raw=true)

Again, not too exciting because this is a new instance. Click the 'Create' button to be taken to the user creation page. Fill in the details for this new user and save.

![User page](/docs/imgs/gs_user.png?raw=true)

With that, you should be able to log in with this user as well! Having users is nice and all, but chances are you'll want some Alerts to go out to multiple people. 411's group functionality allows you to do exactly that. Click 'Groups' in the header to check it out.

![Groups page](/docs/imgs/gs_groups.png?raw=true)

You know the drill. Hit the 'Create' button to be taken to the group creation page.

1. "Name" should be obvious enough.
2. "Type" requires some explanation. This field allows you to configure how emails are sent out to the group. 'All' specifies that all users in the group should receive emails, whereas 'Rotation' specifies that 411 will rotate between users in the group.

Pick one and save the group so that you can start adding users to it.

![Group page](/docs/imgs/gs_group.png?raw=true)

Let's add both (all 2) users to the group. To add a user, simply select it from the dropdown. Once you're all done, save the group again.

![Group page](/docs/imgs/gs_group_save.png?raw=true)


Creating a new Search
---------------------

Now we're ready to create a new Search! The 'Searches' button in the header will take you where you need to go.

![Searches page](/docs/imgs/gs_searches.png?raw=true)

There's a bit more functionality on this page than on the users/groups management pages. You can toggle between a compact listing of searches, or a more detailed card-based view. There's a search box for filtering Searches, and an 'Update' button for mass enabling/disabling Searches. Of course, none of this is useful without a Search, so click 'Create'.

![Search type](/docs/imgs/gs_searches_type.png?raw=true)

A dialog will show up with a list of available Search types. Assuming you've set up ELK, pick the "Logstash" type and click 'Create' again to be taken to the Search creation page.

![Search page](/docs/imgs/gs_search.png?raw=true)

Welcome to the search configuration page. There are a lot of settings here, but don't panic! We'll go over all the important parts. For the purposes of this guide, say we to alert on any 404s from the webserver.

1. Firstly, make sure you add a descriptive title. If you configure email notifications, this will be the actual title of the email!
2. For the query, we want to match on any log lines with response code of 404. If you've set up Logstash ingestion correctly with your apache logs, you can use the `response` field to match on the response code. Thus, we can specify a query of `response:404` here. For details on syntax, check out the documentation page on [ESQuery](/docs/ESQuery.md).
3. 'Result Type' lets us specify what sort data we want back from ES. We can leave it at 'Fields' which returns the entire log line. Similarly, we can leave 'Result Filter' as we want all instances of 404s.
4. For 'Fields', we'll specify that we only want the 'message' field in any generated Alerts.
5. Next, we can fill in some useful information for 'Description' and categorize accordingly.
6. 'Frequency' defines how often the Search will execute, and 'Time Range' specifies how big of a time window to query over. In this case (and most of the time), we want them to match.
7. Finally, we can enable this Search via the 'Enable' button.

Congrats! We're almost done configuring the Search. Your settings might look something like below. Feel free to experiment, of course.

![Search basic configuration](/docs/imgs/gs_search_basic.png?raw=true)

Next up, click on the 'Notifications' tab. This lets you configure whether 411 will generate email alerts.

1. We do want that to be the case, so we can leave 'Notification Type' as is.
2. 'Notification Format' tells 411 to keep the emails as simple as possible - they'll only show the contents of the Alerts.
3. For 'Assignee', we're going to specify the group we just created.
4. Finally, we'll set the Admin user as the 'Owner' of this Search.

And with that, the Search is fully configured! Compare your settings to the screenshot below. There are some more advanced settings you can configure, but we won't cover them here. Check out the [Search](/docs/Searches.md) documentation for more details.

![Search notif configuration](/docs/imgs/gs_search_notif.png?raw=true)

You can test out your new Search via the handy 'Test' button at the bottom.

![Search buttons](/docs/imgs/gs_search_buttons.png?raw=true)

Clicking on it should pop up a window with some results.

![Search test](/docs/imgs/gs_search_alerts.png?raw=true)

If you don't get any results, you can try generating a log line and then re-testing. Hopefully, you'll eventually get some results.

Now that you've confirmed your Search works, make sure to save it!


Waiting for Alerts
------------------

Now that you've set up a Search, you need to wait for it to fire. Or, you can force 411 to run the Search immediately by hitting the 'Execute' button. In either event, you'll eventually get an email that looks like the following.

![Alert email](/docs/imgs/gs_email.png?raw=true)

Now it's time to resolve an alert!


Responding to an Alert
----------------------

You can click on the 'View' button to view the alert directly. For the purpose of this guide, let's check out the Alerts page via the 'Alerts' button in 411's header. It should look something like this.

![Alerts page](/docs/imgs/gs_alerts.png?raw=true)

Check out the search bar at the top.

![Alerts search](/docs/imgs/gs_alerts_search.png?raw=true)

You can filter out queries using standard Lucene shorthand here. Any matching Alerts will show up at the center of the page, where you can individually select them. There's an action bar at the bottom of the screen where you can act on the selected Alerts.

![Alerts actions](/docs/imgs/gs_alerts_actions.png?raw=true)

We could just respond to the Alert on this page. However, for demonstration purposes, let's click on the 'View' button next to the new Alert to check it out.

![Alert page](/docs/imgs/gs_alert.png?raw=true)

The same information is presented here, albeit vertically. There's a changelog at the bottom with all actions that have been taken on the Alert.

![Alert changelog](/docs/imgs/gs_alert_changelog.png?raw=true)

Our next step should be to investigate the Alert. Let's pretend this particular Alert was from a web scanner and that you don't even run Wordpress on your site (wp-admin is the login page for Wordpress). We can take action on this Alert via the action bar.

![Alert actions](/docs/imgs/gs_alert_actions.png?raw=true)

Click on the 'Resolve' button to close out this Alert. That will bring up a dialog with the following.

![Alert2](/docs/imgs/gs_alert_resolve.png?raw=true)

We can resolve this as not being an issue and then leave a note explaining why. Hit the 'Resolve' button and you'll see the alert is now resolved!

![Alert3](/docs/imgs/gs_alert_resolved.png?raw=true)

That's it! You should now know enough to start using 411. If you're curious about any of 411's features, check out the [documentation](/docs/README.md).
