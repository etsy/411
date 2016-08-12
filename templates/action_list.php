<?php namespace FOO; ?>
  <div style="<?= $panel_style ?>">

    <div style="<?= $table_container_style ?>">

      <table style="<?= $table_style ?>">
        <thead>
          <tr>
            <th style="<?= $h_cell_style ?>"></th>
            <th style="<?= $h_cell_style ?>"></th>
            <th style="<?= $h_cell_style ?>">Date</th>
            <th style="<?= $h_cell_style ?>">Action</th>
            <th style="<?= $h_cell_style ?>">Note</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach($actions as $action): ?>
          <tr>
            <td style="<?= $cell_style ?> width: 1px;">
              <a style="<?= $button_style ?>" href="#<?= $action['alert_id'] ?>">Preview</a>
            </td>
            <td style="<?= $cell_style ?> width: 1px;">
              <a style="<?= $button_style ?>" href="<?= $base_url ?>/alert/<?= $action['alert_id'] ?>">View</a>
            </td>
            <td style="<?= $cell_style ?>; white-space: nowrap">
              <?= strftime('%G-%m-%d %T %z', $action['create_date']) ?>
            </td>
            <td style="<?= $cell_style ?>; white-space: nowrap">
              <?= Util::escape($action->getDescription()) ?>
            </td>
            <td style="<?= $cell_style ?>; width: 100%">
              <?= Util::escape($action['note']) ?>
            </td>
          </tr>
        <?php endforeach ?>
        </tbody>
      </table>

    </div>

  </div>
