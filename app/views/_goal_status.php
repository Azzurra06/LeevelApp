<?php $cls = ['todo' => '', 'in_progress' => 'warn', 'acquired' => 'sage', 'rework' => 'err'][$g['status']] ?>
<span class="badge <?= $cls ?>"><?= e(GOAL_STATUS[$g['status']]) ?></span>
