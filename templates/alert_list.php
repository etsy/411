<?php namespace FOO; ?>
  <div style="<?= $panel_style ?>">

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

    <div style="<?= $table_container_style ?>">

      <table style="<?= $table_style ?>">
        <thead>
          <tr>
          <?php if(!$content_only): ?>
            <th style="<?= $h_cell_style ?>"></th>
            <th style="<?= $h_cell_style ?>"></th>
            <th style="<?= $h_cell_style ?>"></th>
            <th style="<?= $h_cell_style ?>">Date</th>
          <?php endif ?>
          <?php foreach($alertkeys as $alertkey): ?>
            <th style="<?= $h_cell_style ?>"><?= Util::escape($alertkey) ?></th>
          <?php endforeach ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach($alerts as $alert): ?>
          <tr>
          <?php if(!$content_only): ?>
            <td style="<?= $cell_style ?> width: 1px;">
              <a name="<?= $alert['id'] ?>"></a>
              <input type="checkbox" name="alerts[]" value="<?= $alert['id'] ?>" />
            </td>
            <td style="<?= $cell_style ?> width: 1px;">
              <?php if($alert['id']): ?>
              <a style="<?= $button_style ?>" href="<?= $base_url ?>/alert/<?= $alert['id'] ?>">View</a>
              <?php endif ?>
            </td>
            <td style="<?= $cell_style ?> width: 1px;">
            <?php $source = $search->getLink($alert); if(!is_null($source)): ?>
              <a style="<?= $button_style ?>" href="<?= Util::escape($source) ?>">Source</a>
            <?php endif ?>
            </td>
            <td style="<?= $cell_style ?> width: 1px;">
              <span style="white-space: nowrap;"><?= strftime('%G-%m-%d', $alert['alert_date']) ?></span>
              <span style="white-space: nowrap;"><?= strftime('%T %z', $alert['alert_date']) ?></span>
            </td>
          <?php endif ?>
          <?php foreach($alertkeys as $alertkey): ?>
            <td style="<?= $cell_style ?>"><?= Util::escape(Util::get($alert['content'], $alertkey, '')) ?></td>
          <?php endforeach ?>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>

    </div>

  </div>
