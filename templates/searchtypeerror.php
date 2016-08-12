<?php namespace FOO; ?>

<div style="<?= $font ?>">
<p>
  The <a style="<?= $link_style ?>" href="<?= $base_url ?>/health"><?= Util::escape($type) ?></a> Search type is currently unavailable.
  Any Searchs of this type <b>will not generate Alerts!</b><br>
  <br>
  A followup email will be sent once this Search type is available.
</p>
</div>
