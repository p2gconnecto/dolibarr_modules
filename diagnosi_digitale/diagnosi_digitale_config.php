<?php
// filepath: /home/dolibarr/.volumes/dolibarr/custom/diagnosi_digitale/diagnosi_digitale_config.php

if (!isset($conf)) {
    $conf = new stdClass();
}

if (!isset($conf->diagnosi_digitale)) {
    $conf->diagnosi_digitale = new stdClass();
}

$conf->diagnosi_digitale->dir_output = $dolibarr_main_data_root . '/diagnosi_digitale';