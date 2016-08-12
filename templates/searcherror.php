<?php namespace FOO; ?>

<div style="<?= $font ?>">
<p>
  <a style="<?= $link_style ?>" href="<?= $base_url ?>/search/<?= $search['id'] ?>"><?= Util::escape($search['name']) ?></a> is currently failing.
  This means that <b>no new Alerts will be generated</b> by this Search!<br>
  <br>
  A followup email will be sent once this Search has recovered.
  <br>
  <br>
  The following error(s) occured:
  <?php foreach($errors as $error): ?>
    <pre style="<?= $error_alert_style ?>"><?= Util::escape($error) ?></pre>
  <?php endforeach ?>
</p>
</div>
