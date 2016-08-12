<?php namespace FOO; ?>

<div style="<?= $font ?>">
<p>
  <a style="<?= $link_style ?>" href="<?= $base_url ?>/report/<?= $report['id'] ?>"><?= Util::escape($report['name']) ?></a> was not correctly generated.
  <br>
  <br>
  The following error(s) occured:
  <?php foreach($errors as $error): ?>
    <pre style="<?= $error_alert_style ?>"><?= Util::escape($error) ?></pre>
  <?php endforeach ?>
</p>
</div>
