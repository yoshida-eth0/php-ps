<?php

require_once dirname(__FILE__).'/../lib/PS.php';

$ps = PS::open()->filter(function(PS_Proc $proc) {
    return strpos($proc->command, "bash")!==false;
});
foreach ($ps as $proc) {
    echo sprintf("% 6d %s\n", $proc->pid, $proc->command);
}
