<?php namespace FOO; ?>

<?php if($action['action'] != AlertLog::A_NOTE): ?>
  <div style="<?= $info_alert_style ?>"><?= ucfirst($action->getDescription(false)) ?></div>
<?php endif ?>

<?php if(strlen($action['note'])): ?>
  <p><b>Note:</b> <?= nl2br(Util::escape($action['note'])) ?></p>
<?php endif ?>

<form method="get" action="<?= $base_url ?>/alerts_redir.php">
  <h1>List</h1>

  <?php foreach($alert_groups as $alert_group): list($search, $alerts, $alertkeys) = $alert_group; ?>
    <?php require(__DIR__ . '/modules/alerts.php'); ?>
    <br>
  <?php endforeach ?>

  <div style="text-align: right">
    <button type="submit" style="<?= $action_button_style ?>">Compare alerts</button>
  </div>
</form>
