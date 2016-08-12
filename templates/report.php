<?php namespace FOO; ?>

<div style="<?= $font ?>">
<p>
  <a style="<?= $link_style ?>" href="<?= $base_url ?>/report/<?= $report['id'] ?>"><?= Util::escape($report['name']) ?></a> was successfully generated.
  <br>
  <br>
  The report is attached as a PDF.
</p>
</div>
