<?php namespace FOO; ?>

<div style="<?= $font ?>">
<p style="<?= $large_style ?>"><b><?= $new_count ?></b> Alerts came in this week.</p>
<p style="<?= $large_style ?>"><b><?= $close_count ?></b> were closed.</p>
<p style="<?= $large_style ?>"><b><?= $open_count ?></b> Alerts are currently open.</p>

<h1>Leaderboards</h1>
<?php if(count($leaders) > 0): ?>
<ol>
<?php foreach($leaders as $leader): list($user, $count) = $leader; ?>
    <li><?= Util::escape($user['real_name']) ?> closed <?= $count ?> Alerts.</li>
<?php endforeach ?>
    <li><?= Util::escape($leaders[0][0]['real_name']) ?> <?= Fun::accomplishment() ?></li>
</ol>
<?php else: ?>
Noone responded to any Alerts this week!
<?php endif ?>

<h1>World's most noisy Searches</h1>
<ol>
<?php foreach($noisy_searches as $noisy_search): list($search, $count) = $noisy_search; ?>
    <li><a href="<?= $base_url ?>/search/<?= $search['id'] ?>"><?= Util::escape($search['name']) ?></a> generated <?= $count ?> Alerts.</li>
<?php endforeach ?>
</ol>

<h1>World's most quiet Searches</h1>
<ol>
<?php foreach($quiet_searches as $quiet_search): list($search, $count) = $quiet_search; ?>
    <li><a href="<?= $base_url ?>/search/<?= $search['id'] ?>"><?= Util::escape($search['name']) ?></a> generated <?= $count ?> Alerts.</li>
<?php endforeach ?>
</ol>
</div>
