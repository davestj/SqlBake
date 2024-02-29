<?php
/**
 * DevOpsToolSmith Database management tool - SqlBake();
 *
 * Utility to take stored procs and tables and save to SQL files
 * Ability to load SQL alter and patch scripts for deployment.
 * @author davestj@gmail.com
 */

// Error reporting
error_reporting(E_ALL & ~E_NOTICE);

// Constants
define("REMOTE_DB_HOST", "127.0.0.1");
define("REMOTE_DB_PORT", "3306");
define("REMOTE_DB_USER", "root");
define("REMOTE_DB_PASS", "");

// Require files
require_once(__DIR__ . '/config.traits.php');
require_once(__DIR__ . '/../lib/db.base.class.php');
require_once(__DIR__ . '/../lib/db.utils.class.php');
require_once(__DIR__ . '/../lib/common.utils.class.php');

use DevOpsToolSmith\ConfigTraits;
use DevOpsToolSmith\DBBase;
use DevOpsToolSmith\DBUtils;
use DevOpsToolSmith\CommonUtils;


