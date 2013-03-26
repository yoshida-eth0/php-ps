<?php

require_once dirname(__FILE__).'/../lib/PS.php';

$ps = PS::open()->equalFilter("user", trim(`whoami`))->progFilter("vim");
foreach ($ps as $proc) {
    echo "waiting: {$proc->pid}\n";
    try {
        $proc->wait(3);
        echo "exited\n";
    } catch (PS_TimeoutException $e) {
        echo $e->getMessage()."\n";
    }
}
