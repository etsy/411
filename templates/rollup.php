<?php namespace FOO; ?>

<div style="<?= $font ?>">
<h1 style="text-align: center">Summary</h1>
<p>
  <?= $new_count ?> Alerts are New<br>
  <?= $inprog_count ?> Alerts are In Progress
</p>

<form method="get" action="<?= $base_url ?>/alerts_redir.php">

<?php if(count($new_alert_groups)): ?>
  <hr>
  <h1 style="text-align: center">Recent Alerts</h1>

  <?php foreach($new_alert_groups as $alert_group): list($search, $alerts, $alertkeys) = $alert_group; ?>
    <?php require(__DIR__ . '/modules/alerts.php'); ?>
    <br>
  <?php endforeach ?>

  <div style="text-align: right">
    <button type="submit" style="<?= $action_button_style ?>">Compare alerts</button>
  </div>
<?php endif ?>

<?php if(count($actions)): ?>
  <hr>
  <h1 style="text-align: center">Recent Actions</h1>

  <?php require(__DIR__ . '/modules/actions.php'); ?>
  <br>

  <h1 style="text-align: center">Alert List</h1>

  <?php foreach($action_alert_groups as $alert_group): list($search, $alerts, $alertkeys) = $alert_group; ?>
    <?php require(__DIR__ . '/modules/alerts.php'); ?>
    <br>
  <?php endforeach ?>

  <div style="text-align: right">
    <button type="submit" style="<?= $action_button_style ?>">Compare alerts</button>
  </div>
<?php endif ?>
</form>
</div>
