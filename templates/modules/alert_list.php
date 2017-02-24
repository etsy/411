<?php namespace FOO; ?>

<table style="<?= $table_style ?>">

  <tbody>
  <?php $count = count($alerts); foreach($alerts as $i=>$alert): ?>
    <tr>
      <th style="<?= $h_cell_style ?>">
        <a name="<?= $alert['id'] ?>"></a>
        <input type="checkbox" name="alerts[]" value="<?= $alert['id'] ?>" />
      </th>
      <td style="<?= $h_cell_style ?> text-align: right;">
      <?php if(!$content_only): ?>
        <?php if($alert['id']): ?>
        <a style="<?= $button_style ?>" href="<?= $base_url ?>/alert/<?= $alert['id'] ?>">View</a>
        <?php endif ?>
        <?php $source = $search->getLink($alert); if(!is_null($source)): ?>
          <a style="<?= $button_style ?>" href="<?= Util::escape($source) ?>">Source</a>
        <?php endif ?>
      <?php endif ?>
      </td>
    </tr>
    <tr>
      <th style="<?= $h_cell_style ?>">Date</th>
      <td style="<?= $cell_style ?>">
        <span style="white-space: nowrap;"><?= strftime('%G-%m-%d', $alert['alert_date']) ?></span>
        <span style="white-space: nowrap;"><?= strftime('%T', $alert['alert_date']) ?></span>
      </td>
    </tr>
    <?php foreach($alertkeys as $alertkey): ?>
    <tr>
      <th style="<?= $h_cell_style ?>"><?= Util::escape($alertkey) ?></th>
      <td style="<?= $cell_style ?>"><?= nl2br(Util::escape(Util::get($alert['content'], $alertkey, ''))) ?></td>
    </tr>
    <?php endforeach ?>

    <?php if($i + 1 < $count): ?>
    <tr>
      <td style="<?= $sp_cell_style ?>"></td>
      <td style="<?= $sp_cell_style ?>"></td>
    </tr>
    <?php endif ?>

  <?php endforeach ?>
  </tbody>

</table>
