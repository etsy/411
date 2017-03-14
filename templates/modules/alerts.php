<?php namespace FOO; ?>

<div style="<?= $panel_style ?>; display: table; width: 100%">

  <div style="<?= $table_container_style ?>">

    <?php if($vertical): ?>
      <?php require(__DIR__ . '/alert_list.php'); ?>
    <?php else: ?>
      <?php require(__DIR__ . '/alert_table.php'); ?>
    <?php endif ?>

  </div>

  <div style="display:table-header-group;">
  <h2 style="<?= $panel_content_style ?>">
    <a style="<?= $link_style ?>" href="<?= $base_url ?>/search/<?= $search['id'] ?>"><?= Util::escape($search['name']) ?></a>
    <small style="<?= $sub_style ?>">[<?= count($alerts) ?> Alert<?= count($alerts) != 1 ? 's':'' ?>]</small>
  </h2>

  <p style="<?= $panel_content_style ?>">
    <?= nl2br(Util::escape($search['description'])) ?>
    <?php if($search->isTimeBased()): ?>
      <br>
      <br>
      <b>Time range: </b><?= $search['range'] ?> minute(s)
    <?php endif ?>
  </p>
</div>

</div>
