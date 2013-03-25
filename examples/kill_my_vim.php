<?php

require_once dirname(__FILE__).'/../lib/PS.php';

$ps = new PS();
$ps = $ps->equalFilter("user", trim(`whoami`))->progFilter("vim");
foreach ($ps->procs() as $proc) {
    $status = $proc->kill()===0 ? "kill success" : "kill failure";
    echo sprintf("%s: pid=%d cmd=%s\n", $status, $proc->pid, $proc->command);
}
