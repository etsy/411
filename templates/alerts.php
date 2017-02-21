<?php namespace FOO; ?>

<div style="<?= $font ?>">
<form method="get" action="<?= $base_url ?>/alerts_redir.php">
  <?php require(__DIR__ . '/modules/alerts.php'); ?>
  <br>

  <div style="text-align: right">
    <button type="submit" style="<?= $action_button_style ?>">Compare alerts</button>
  </div>

</form>
</div>
