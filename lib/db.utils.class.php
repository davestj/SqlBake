<?php
/**
 * DevOpsToolSmith Database management tool - SqlBake();
 *
 * Utility to take stored procs and tables and save to sql files
 * Ability to load sql alter and patch scripts for deployment.
 * @author davestj@gmail.com
 *
 */

/**
 * Class OpsSmithdbTableManager
 */
class OpsSmithdbTableManager extends legacyDb{
    /**
     * using trait OpsSmithDBConfig;
     */
    use OpsSmithDBConfig;

    /**
     * @var array
     */
    public $result      = array();


    /**
     * @var array
     */
    public $results     = array();


    /**
     * @var array
     */
    public $table_list  = array();


    /**
     * @var string
     */
    public $sql         = "";


    /**
     * @var array
     */
    public $row         = array();


    /**
     * @var string
     */
    public $qObj        = "";


    /**
     * @var array
     */
    public $proc_obj    = array();


    /**
     * @var string
     */
    public $base_path   = "./databases";


    /**
     * @var
     */
    public $thedb       = DB_DB;

    public function __construct(){
        parent::legacy_connect();
    }

    /**
     *  dump list of all tables
     * @return array
     */
    public function get_tables_list(){
        $indb            = "Tables_in_" . $this->thedb;
        $this->sql       = "SHOW TABLES";
        $this->qObj      = parent::legacy_query($this->sql);

        if (!$this->qObj) {
            throw new Exception("Failed to fetch tables list.");
        }

        while ($this->row = mysqli_fetch_assoc($this->qObj)) {
            $this->table_list[] = $this->row["$indb"];
        }
        return $this->table_list;
    }


    /**
     *  list of all tables sql located on filesystem
     *
     * @return array
     */
    public function get_table_list_from_fs(){
        $table_path = $this->base_path . '/'.DB_DB.'/tables';
        $dir            = new DirectoryIterator($table_path);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $thefile                    = $fileinfo->getFilename();
                $this->table_list[]          = "$thefile";
            }
        }
        return $this->table_list;
    }


    /**
     * dump each table details by name
     * @param $tablename
     * @param bool $autoincrement_reset
     * @return mixed
     */
    public function get_table_by_name($tablename,$autoincrement_reset = FALSE){
        $this->sql        = sprintf("SHOW CREATE TABLE %s", $tablename);
        $this->result     = parent::legacy_query($this->sql);

        if (!$this->result) {
            throw new Exception("Failed to fetch table details for table: $tablename");
        }

        $this->row        = mysqli_fetch_assoc($this->result);
        if(!$autoincrement_reset) {
            $table_structures[$tablename] = $this->row["Create Table"];
        }else{
            $table_structures[$tablename] = preg_replace("/ AUTO_INCREMENT=(\d+)/",' AUTO_INCREMENT=0',$this->row["Create Table"]);
        }
        return $table_structures[$tablename];
    }


    /**
     * save tables to file
     *
     * @param $tablename
     */
    public function save_table($tablename){
        $tabledetails = self::get_table_by_name($tablename);
        $table_path  = $this->base_path . '/'.DB_DB.'/tables';

        if(!is_dir($table_path)){
            mkdir($table_path, 0777, true);
        }
        $fp = fopen(''.$table_path.'/'.$tablename.'.sql','w');

        $table_header = "";

        $table_footer = "\n";

        fwrite($fp,$table_header);
        fwrite($fp, "$tabledetails");
        fwrite($fp,$table_footer);

        @fclose($fp);
    }


    /**
     * load each table into database
     *
     * @param $tablename
     * @return string
     */
    public function load_table($tablename){
        $table_path = $this->base_path . '/'.DB_DB.'/tables';
        $fulltable  = "$table_path/$tablename";
        $thetable_content = file_get_contents("$table_path/$tablename.sql");
        $dbuser = self::theuser();
        $dbpw   = self::thepass();
        $dbdb   = self::thedbname();
        $dbhost = self::thehost();

        try {
            /* run mysql command line client, mysql+query does not support multi query */
            $loadit = shell_exec($this->myclient . ' -h '.$dbhost.' -u '.$dbuser.' -p'.$dbpw.' -v '.$dbdb.' < '.$fulltable.'');
            $msg = "successfully loaded table $tablename\n$loadit\n";
            return $msg;
        }
        catch (Exception $e){
            $msg = "failed to load table";
            return $msg;
        }
    }


    /**
     * clean up tables.sql and do a fresh save
     *
     * @return string
     */
    public function clean_tables(){
        $table_path = $this->base_path . '/'.DB_DB.'/tables';
        $cleaned   = self::clean_table_dir($table_path);
        if($cleaned){
            return TRUE;
        }else{
            return FALSE;
        }
    }


    /**
     * clean directory recursively
     *
     * @param $dir
     */
    private function clean_table_dir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {

                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);
        }
    }
}

/**
 * Class OpsSmithdbProcManager
 */
class OpsSmithdbProcManager extends legacyDb{
    /**
     * using trait OpsSmithDBConfig;
     */
    use OpsSmithDBConfig;

    /**
     * @var array
     */
    public $result     = array();


    /**
     * @var array
     */
    public $results    = array();

    /**
     * @var array
     */
    public $proc_list  = array();


    /**
     * @var string
     */
    public $sql        = "";


    /**
     * @var array
     */
    public $row        = array();


    /**
     * @var string
     */
    public $qObj       = "";


    /**
     * @var array
     */
    public $proc_obj   = array();


    /**
     * @var string
     */
    public $base_path = "./databases";


    /**
     * @var string
     */
    public $myclient  = "/usr/bin/mysql";


    /**
     * @var
     */
    public $dbh;

    public function __construct(){
        parent::legacy_connect();
    }

    /**
     *  dump list of all procedures from database
     *
     * @return array
     */
    public function get_proc_list(){
        $this->dbh     = parent::$dbname;
        $this->sql       = "SHOW PROCEDURE STATUS WHERE db = '$this->dbh' ";
        $this->qObj      = parent::legacy_query($this->sql);

        if (!$this->qObj) {
            throw new Exception("Failed to fetch procedures list.");
        }

        while ($this->row = mysqli_fetch_assoc($this->qObj)) {
            $this->proc_list[] = $this->row['Name'];
        }
        return $this->proc_list;
    }

    /**
     * list of all procedures located on filesystem
     *
     * @return array
     */
    public function get_proc_list_from_fs(){
        $proc_path = $this->base_path . '/'.DB_DB.'/procs';
        $dir            = new DirectoryIterator($proc_path);
        foreach ($dir as $fileinfo) {
            if (!$fileinfo->isDot()) {
                $thefile                    = $fileinfo->getFilename();
                $this->proc_list[]          = "$thefile";
            }
        }
        return $this->proc_list;
    }


    /**
     * *dump each proc details by name*
     *
     * @param $procname
     * @return mixed
     */
    public function get_proc_by_name($procname){
        $this->sql        = sprintf("SHOW CREATE PROCEDURE %s", $procname);
        $this->result     = parent::legacy_query($this->sql);

        if (!$this->result) {
            throw new Exception("Failed to fetch procedure details for procedure: $procname");
        }

        $this->row        = mysqli_fetch_assoc($this->result);
        $proc_structures[$procname] = $this->row["Create Procedure"];

        return $proc_structures[$procname];
    }

    /**
     * dump app proceures
     *
     * @return array
     */
    public function dump_proc(){
        $this->proc_list          = self::get_proc_list();
        foreach($this->proc_list as $proc) {
            $this->sql        = sprintf("SHOW CREATE PROCEDURE %s", $proc);
            $this->result     = parent::legacy_query($this->sql);

            if (!$this->result) {
                throw new Exception("Failed to fetch procedure details for procedure: $proc");
            }

            while ($this->row = mysqli_fetch_assoc($this->result)) {
                $proc_def =  $this->row['Create Procedure'];
                $proc_structures[$proc] = $proc_def;

            }
            $this->proc_obj[$proc] = @$proc_structures[$proc];
        }
        return $this->proc_obj;
    }

    /**
     * save stored procedures to file
     *
     * @param $procname
     */
    public function save_proc($procname){
        $procdetails = self::get_proc_by_name($procname);
        $proc_path = $this->base_path . '/'.DB_DB.'/procs';

        if(!is_dir($proc_path)){
            mkdir($proc_path, 0777, true);
        }
        $fp = fopen(''.$proc_path.'/'.$procname.'.sql','w');

        $proc_header = "Use ".DB_DB.";\n\nDROP PROCEDURE IF EXISTS `$procname` ;\n\nDELIMITER $$\n\n";

        $proc_footer = " $$\n\nDELIMITER ;\n\n";

        fwrite($fp,$proc_header);
        fwrite($fp, "$procdetails");
        fwrite($fp,$proc_footer);

        @fclose($fp);


    }


    /**
     * load procs into database
     *
     * @param $procname
     * @return string
     */
    public function load_proc($procname){
        $proc_path = $this->base_path . '/'.DB_DB.'/procs';
        $fullproc  = "$proc_path/$procname";
        $theproc_content = file_get_contents("$proc_path/$procname.sql");
        $dbuser = self::theuser();
        $dbpw   = self::thepass();
        $dbdb   = self::thedbname();
        $dbhost = self::thehost();

        try {
            /* run mysql command line client, mysql+query does not support multi query */
            $loadit = shell_exec($this->myclient . ' -h '.$dbhost.' -u '.$dbuser.' -p'.$dbpw.' -v '.$dbdb.' < '.$fullproc.'');
            $msg = "successfully loaded proc $procname\n$loadit\n";
            return $msg;
        }
        catch (Exception $e){
            $msg = "failed to load procs";
            return $msg;
        }
    }

    /**
     * clean up procs and do a fresh save
     *
     * @return string
     */
    public function clean_procs(){
        $proc_path = $this->base_path . '/'.DB_DB.'/procs';
        $cleaned   = self::clean_proc_dir($proc_path);
        if($cleaned){
            return "$cleaned\n";
        }else{
            return "error cleaning proc dir" . $cleaned . "\n";
        }
    }


    /**
     *  clean directory recursively
     *
     * Be careful, this will remove everything
     *
     * @param $dir
     */
    private function clean_proc_dir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir") {

                    } else {
                        unlink($dir.'/'.$object);
                    }
                }
            }
            reset($objects);

        }
    }

}
