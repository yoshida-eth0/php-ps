<?php

require_once dirname(__FILE__).'/../lib/PS.php';

$ps = new PS();
foreach ($ps->procs() as $proc) {
    echo sprintf("% 6d %s\n", $proc->pid, $proc->command);
}
