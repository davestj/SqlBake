<?php
/**
 * SqlBake – Database management tool
 *
 * Utility to take stored procs and tables and save to sql files
 * Ability to load sql alter and patch scripts for deployment.
 * @author davestj@gmail.com
 *
 */

/**
 * Class SqlBakeUtils
 * @uses SqlBakeDb
*/
class SqlBakeUtils extends SqlBakeDb
{
    use SqlBakeDBConfig;
    use SqlBakeConfig;

    public $base_path = "./databases";

    public function __construct()
    {
        parent::__construct(self::theDSN("mysql"), self::theUser(), self::thePass());
    }

    public function cmd__sync($fromdb, $todb, $databases)
    {
        if (!in_array($fromdb, $databases)) {
            echo "syncing from hotfix by default to SqlBake\n";
            return;
        }

        if (!$this->check_local_db_exists($todb) || !$this->check_remote_db_exists($fromdb)) {
            echo "\nUnable to sync database $fromdb to $todb\n";
            return;
        }

        echo "checked remote db $fromdb\nDumping schema first\n";
        try {
            echo $this->dump_remote_db_schema($fromdb);
            echo "\nDumping all data, routines and triggers\n";
            echo $this->dump_remote_db_full($fromdb);
            echo "\nBoth local and remote databases verified and exported, syncing...stand by...\n";
            echo $this->db_import_schema($fromdb, $todb) . $this->db_import_full($fromdb, $todb);
        } catch (Exception $e) {
            echo "\nError syncing databases: " . $e->getMessage() . "\n";
        }
    }

    public function cmd__deploy($value)
    {
        try {
            switch ($value) {
                case 'stage':
                    echo "deploying to staging environment\n";
                    break;

                case 'production':
                case 'dev':
                    echo "deploying to $value\n";
                    $this->run_patch_sql();
                    $this->run_alter_sql();
                    break;

                case 'custom':
                    $path = $this->get_answer("What is the path you wish to install to?: ");
                    echo "answer was $path\n";
                    break;

                default:
                    if ($this->get_env() == ENV_LOCAL) {
                        echo "deploying to portal.SqlBake.int\n";
                    }
            }
        } catch (Exception $e) {
            echo "\nError deploying: " . $e->getMessage() . "\n";
        }
    }

    public function get_answer($question)
    {
        try {
            print($question);
            $h      = fopen("php://stdin", 'r');
            if (!$h) {
                throw new Exception("Failed to open stdin");
            }
            $line   = fgets($h);
            if ($line === false) {
                throw new Exception("Failed to read input from stdin");
            }
            $l      = trim($line);
            fclose($h);
            if (!empty($l)) {
                return ($l);
            } else {
                throw new Exception("No answer provided");
            }
        } catch (Exception $e) {
            echo "\nError getting answer: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function run_patch_sql()
    {
        try {
            $basepath = $this->base_path . '/' . DB_DB . '/patches';
            if (!is_dir($basepath)) {
                throw new Exception("Patches directory not found");
            }
            $dir = new DirectoryIterator($basepath);
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    $patchfile = $fileinfo->getFilename();
                    $patch_sql = file_get_contents("$basepath/$patchfile");
                    $exec      = $this->queryIt($patch_sql);
                    return ($exec);
                }
            }
        } catch (Exception $e) {
            echo "\nError running patch SQL: " . $e->getMessage() . "\n";
        }
    }

    public function check_local_db_exists($dbname)
    {
        try {
            $dbcon  = new legacyDb();
            $dblook = $dbcon->does_local_db_exist($dbname);
            return !!$dblook;
        } catch (Exception $e) {
            echo "\nError checking local database: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function check_remote_db_exists($dbname)
    {
        try {
            $connect = new legacyDb();
            $connect->remote_connect(REMOTE_DB_HOST, REMOTE_DB_USER, REMOTE_DB_PASS, $dbname, REMOTE_DB_PORT);
            return $connect->legacy_query("SHOW TABLES FROM $dbname");
        } catch (Exception $e) {
            echo "\nError checking remote database: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function dump_remote_db_schema($dbname)
    {
        try {
            $bkupdir = "./databases/$dbname";
            if (!is_dir($bkupdir)) {
                mkdir($bkupdir, 0775, true);
            }
            $bkupfile = "$bkupdir/{$dbname}_schema.sql";
            $options  = " -v --no-data --routines --triggers --quick --single-transaction --opt";
            self::mydb_dump_all(REMOTE_DB_HOST, REMOTE_DB_PORT, REMOTE_DB_USER, REMOTE_DB_PASS, $dbname, $options, $bkupfile);

            if (file_exists($bkupfile)) {
                $fsz = filesize($bkupfile);
                return "\ndump size $fsz\n";
            } else {
                throw new Exception("\nUnable to dump to $bkupfile\n");
            }
        } catch (Exception $e) {
            throw new Exception("Error dumping remote database schema: " . $e->getMessage());
        }
    }

    public function dump_remote_db_full($dbname)
    {
        try {
            $bkupdir = "./databases/$dbname";
            if (!is_dir($bkupdir)) {
                mkdir($bkupdir, 0775, true);
            }
            $bkupfile = "$bkupdir/{$dbname}_full.sql";
            $options  = " -v --routines --triggers --quick --single-transaction --opt";
            self::mydb_dump_all(REMOTE_DB_HOST, REMOTE_DB_PORT, REMOTE_DB_USER, REMOTE_DB_PASS, $dbname, $options, $bkupfile);

            if (file_exists($bkupfile)) {
                $fsz = filesize($bkupfile);
                return "\nfull db dump size $fsz\n";
            } else {
                throw new Exception("\nUnable to perform a full dump to $bkupfile\n");
            }
        } catch (Exception $e) {
            throw new Exception("Error dumping full remote database: " . $e->getMessage());
        }
    }

    public function db_import_full($fromdb, $todb)
    {
        try {
            $importdir = "./databases/$fromdb";
            $importile = "$importdir/{$fromdb}_full.sql";
            self::mydb_import(self::theHost(), self::theUser(), self::thePass(), $todb, $importile);
        } catch (Exception $e) {
            throw new Exception("Error importing full database: " . $e->getMessage());
        }
    }

    public function db_import_schema($fromdb, $todb)
    {
        try {
            $importdir = "./databases/$fromdb";
            $importile = "$importdir/{$fromdb}_schema.sql";
            self::mydb_import(self::theHost(), self::theUser(), self::thePass(), $todb, $importile);
        } catch (Exception $e) {
            throw new Exception("Error importing database schema: " . $e->getMessage());
        }
    }

    public function print_empty_usage($value)
    {
        try {
            $msgv       = empty($value) ? "Argument option was left empty" : "Caught unexpected $value";
            $scriptname = $_SERVER["SCRIPT_NAME"];

            echo "\e[1;32m\n#################################################################################################################\n\e[0m#";
            echo "#\e[1;5;31m\tCommand line argument encountered an error, error was $msgv\n\e[0m#";
            echo "#\e[1;4;96m\tCommand line usage\n\e[0m#";
            echo "\e[15m#\t $scriptname --proc=list,save,clean,load            # stored procedures operations \n#\e[0m";
            echo "\e[15m#\t $scriptname --table=list,save,clean,load,show      # table operations \n#\e[0m";
            echo "\e[15m#\t $scriptname --run=test,statuscheck,env             # run diagnostics\n#\e[0m";
            echo "\e[15m#\t $scriptname --deploy=stage,production,dev          # run deployment steps \n#\e[0m";
            echo "\e[15m#\t $scriptname --sync=[fromdb,todb]                   # run db sync \n#\e[0m";
            echo "\e[1;32m##################################################################################################################\n\e[0m";
            return true;
        } catch (Exception $e) {
            echo "\nError printing empty usage: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function print_usage()
    {
        try {
            $scriptname = $_SERVER["SCRIPT_NAME"];

            echo "\e[1;32m\n#################################################################################################################\n\e[0m#";
            echo "#\e[1;4;96m\tCommand line usage\n\e[0m#";
            echo "\e[15m#\t $scriptname --proc=list,save,clean,load            # stored procedures operations \n#\e[0m";
            echo "\e[15m#\t $scriptname --table=list,save,clean,load,show      # table operations \n#\e[0m";
            echo "\e[15m#\t $scriptname --run=test,statuscheck,env             # run diagnostics\n#\e[0m";
            echo "\e[15m#\t $scriptname --deploy=stage,production,dev          # run deployment steps \n#\e[0m";
            echo "\e[15m#\t $scriptname --sync=[fromdb,todb]                   # run db sync \n#\e[0m";
            echo "\e[1;32m##################################################################################################################\n\e[0m";
            return true;
        } catch (Exception $e) {
            echo "\nError printing usage: " . $e->getMessage() . "\n";
            return false;
        }
    }

    public function run_alter_sql()
    {
        try {
            $basepath = $this->base_path . '/' . DB_DB . '/alters';
            if (!is_dir($basepath)) {
                throw new Exception("Alters directory not found");
            }
            $dir = new DirectoryIterator($basepath);
            foreach ($dir as $fileinfo) {
                if (!$fileinfo->isDot()) {
                    $alterfile = $fileinfo->getFilename();
                    $alter_sql = file_get_contents("$basepath/$alterfile");
                    $exec      = $this->alterQuery($alter_sql);
                    return ($exec);
                }
            }
        } catch (Exception $e) {
            echo "\nError running alter SQL: " . $e->getMessage() . "\n";
        }
    }

    public function show_db_drivers()
    {
        try {
            $drvr = $this->getDrivers();
            return ($drvr);
        } catch (Exception $e) {
            throw new Exception("Error getting database drivers: " . $e->getMessage());
        }
    }

    public function show_db_status()
    {
        try {
            $status = $this->query("SHOW STATUS");
            $exec   = $status->fetchAll(PDO::FETCH_ASSOC);
            return ($exec);
        } catch (Exception $e) {
            throw new Exception("Error getting database status: " . $e->getMessage());
        }
    }

    public function show_db_uptime()
    {
        try {
            $status = $this->query("SHOW GLOBAL STATUS LIKE 'Uptime'");
            $today  = getdate();
            $exec   = $status->fetchAll(PDO::FETCH_ASSOC);
            $fmt    = $exec[0]["Value"];
            $dated  = date("F j, Y, g:i a", $fmt);
            $sysd   = '' . $today["weekday"] . ' the ' . $today["mday"] . ' day of ' . $today["month"] . ' in the year ' . $today["year"] . ' ';
            $msg    = "System time: $sysd \nMySQL server up since $dated \n";
            return ($msg);
        } catch (Exception $e) {
            throw new Exception("Error getting database uptime: " . $e->getMessage());
        }
    }

    public function show_db_vars()
    {
        try {
            $dbvars = $this->query("SHOW VARIABLES");
            $res    = $dbvars->fetchAll(PDO::FETCH_ASSOC);
            return ($res);
        } catch (Exception $e) {
            throw new Exception("Error getting database variables: " . $e->getMessage());
        }
    }

    public static function check_alive($host)
    {
        try {
            $starttime = microtime(true);
            $mysqlfp   = fsockopen($host, 3306, $errno, $errstr, 10);
            $stoptime  = microtime(true);

            if (!$mysqlfp) {
                $status = -1;
            } else {
                fclose($mysqlfp);
                $status = ($stoptime - $starttime) * 1000;
                $status = floor($status);
            }
            return $status;
        } catch (Exception $e) {
            throw new Exception("Error checking server status: " . $e->getMessage());
        }
    }

    public function cmd__run($value)
    {
        try {
            switch ($value) {
                case 'test':
                    echo "running test\n";
                    print_r($this->queryItAll("SHOW STATUS"));
                    echo "test 2";
                    $row = $this->queryObj("SELECT * FROM systems ORDER BY id DESC");
                    print_r($row);
                    foreach ($row as $objitem) {
                        echo "[" . $objitem->id . "] the name " . $objitem->name . "\n";
                    }
                    break;

                case 'statuscheck':
                    echo "showing loaded db drivers\n";
                    print_r($this->show_db_drivers());
                    echo "showing Db status\n";
                    print_r($this->show_db_status());
                    echo "showing configured runtime db variables\n";
                    print_r($this->show_db_vars());
                    echo "showing db uptime\n";
                    echo $this->show_db_uptime();
                    break;

                case 'env':
                    echo $this->get_env() . "\n";
                    break;

                default:
                    $this->print_empty_usage($value);
            }
        } catch (Exception $e) {
            echo "\nError running command: " . $e->getMessage() . "\n";
        }
    }

    public function cmd__proc($value)
    {
        try {
            switch ($value) {
                case 'list':
                    echo "dumping proc list\n";
                    $p       = new OpsSmithdbProcManager();
                    $proclist = $p->get_proc_list();
                    foreach ($proclist as $theproc) {
                        $procsql = $p->get_proc_by_name($theproc);
                        echo "\e[1;32m\nprocedure name: $theproc\nprocedure sql: \e[0m\n";
                        echo "\n $procsql \n";
                    }
                    break;

                case  'save':
                    echo "saving all procs to disk\n";
                    $p       = new OpsSmithdbProcManager();
                    $proclist = $p->get_proc_list();
                    foreach ($proclist as $theproc) {
                        $procsql = $p->get_proc_by_name($theproc);
                        echo "saving procedure: $theproc\n$procsql\n";
                        $p->save_proc($theproc);
                    }
                    break;

                case  'clean':
                    echo "cleaning all procs on disk\n";
                    $p       = new OpsSmithdbProcManager();
                    $cleanit = $p->clean_procs();
                    break;

                case  'load':
                    $p            = new OpsSmithdbProcManager();
                    $proclistfs  = $p->get_proc_list_from_fs();
                    echo "loading all procs.sql files on disk to mysql server\n";
                    foreach ($proclistfs as $theproc) {
                        $loadit = $p->load_proc($theproc);
                        echo "$theproc is $loadit\n\n\n";
                    }
                    break;

                default:
                    $this->print_empty_usage($value);
            }
        } catch (Exception $e) {
            echo "\nError running procedure command: " . $e->getMessage() . "\n";
        }
    }

    public function cmd__table($value)
    {
        try {
            switch ($value) {
                case 'show':
                    echo "dumping table list name and raw sql\n";
                    $t          = new OpsSmithdbTableManager();
                    $tablelist = $t->get_tables_list();
                    foreach ($tablelist as $thetable) {
                        $tablesql = $t->get_table_by_name($thetable, false);
                        echo "\e[1;32m\ntable name: $thetable\ntable sql: \e[0m\n";
                        echo "$tablesql \n";
                    }
                    break;

                case 'list':
                    echo "showing table list from filesystem\n";
                    $t         = new OpsSmithdbTableManager();
                    $tablelist = $t->get_table_list_from_fs();
                    foreach ($tablelist as $thetable) {
                        echo "\e[1;32m\ntable name: $thetable \e[0m\n";
                    }
                    break;

                case  'save':
                    $t          = new OpsSmithdbTableManager();
                    $tablelist = $t->get_tables_list();
                    echo "saving all tables to disk\n";
                    foreach ($tablelist as $thetable) {
                        $tablesql = $t->get_table_by_name($thetable);
                        echo "saving table: $thetable\n$tablesql\n";
                        $t->save_table($thetable);
                    }
                    break;

                case  'clean':
                    echo "cleaning all tables on disk\n";
                    $t       = new OpsSmithdbTableManager();
                    $cleanit = $t->clean_tables();
                    break;

                case  'load':
                    $t            = new OpsSmithdbTableManager();
                    $tablelistfs = $t->get_table_list_from_fs();
                    echo "loading all table.sql files on disk to mysql server\n";
                    foreach ($tablelistfs as $thetable) {
                        $loadit = $t->load_table($thetable);
                        echo "$thetable is $loadit\n\n\n";
                    }
                    break;

                default:
                    $this->print_empty_usage($value);
            }
        } catch (Exception $e) {
            echo "\nError running table command: " . $e->getMessage() . "\n";
        }
    }
}

?>
