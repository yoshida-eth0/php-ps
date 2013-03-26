<?php

require_once dirname(__FILE__).'/../lib/PS.php';

$ps = PS::open();
foreach ($ps as $proc) {
    echo sprintf("% 6d %s\n", $proc->pid, $proc->command);
}
