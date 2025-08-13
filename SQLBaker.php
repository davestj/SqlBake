#!/usr/bin/php
<?php
/**
 * SqlBake
 *
 * Utility to take stored procs and tables and save to SQL files
 * Ability to load SQL alter and patch scripts for deployment.
 * @author davestj@gmail.com
 */

require_once(dirname(__FILE__) . '/etc/config.common.php');

// Check if composer autoload file exists
$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoloadFile)) {
    require $autoloadFile;
}

use SqlBake\SqlBakeUtils;

$utility = new SqlBakeUtils();

// Command-line argument handling
$shortOptions = "p:t:r:d:s:";
$longOptions = ["proc:", "table:", "run:", "deploy:", "sync:"];

$options = getopt($shortOptions, $longOptions);

if (empty($options)) {
    $utility->printUsage();
} else {
    foreach ($options as $option => $value) {
        switch ($option) {
            case 'proc':
            case 'p':
                $utility->cmdProc($value);
                break;
            case 'table':
            case 't':
                $utility->cmdTable($value);
                break;
            case 'run':
            case 'r':
                $utility->cmdRun($value);
                break;
            case 'deploy':
            case 'd':
                $utility->cmdDeploy($value);
                break;
            case 'sync':
            case 's':
                [$fromDb, $toDb] = explode(",", $value);
                $utility->cmdSync($fromDb, $toDb);
                break;
            default:
                echo "Unknown option: -$option or --$option\n";
                $utility->printUsage();
                break;
        }
    }
}
