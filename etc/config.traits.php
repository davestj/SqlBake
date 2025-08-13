<?php
/**
 * SqlBake – Database management tool
 *
 * Utility to take stored procs and tables and save to SQL files
 * Ability to load SQL alter and patch scripts for deployment.
 * @author davestj@gmail.com
 */

/**
 * Trait SqlBakeDBConfig
 *
 * Config objects for db abstraction layer and system configurations
 */
trait SqlBakeDBConfig{

    public function db_manager_host(){
        return "prod-db-manager";
    }

    public function db_dev_host(){
        return "dev-db-cluster01";
    }

    public function db_prod_host(){
        return 'prod-db-cluster01';
    }

    public function ssh_tunnel_host(){
        return "127.0.0.1";
    }

    public function ssh_tunnel_dbdev_port(){
        return 23306;
    }

    public function ssh_tunnel_dbdev_cluster_port(){
        return 13306;
    }

    public function thehost(){
        return DB_HOST;
    }

    public function theuser(){
        return DB_LOGIN;
    }

    public function thepass(){
        return DB_PASS;
    }

    public function thedbname(){
        return DB_DB;
    }

    public function thedsn($drivertype){
        switch($drivertype){
            case "mysql":
                return "mysql:host=".self::thehost().";dbname=".self::thedbname().";port=3306";
            case "pgsql":
                return "pgsql:host=".self::thehost().";dbname=".self::thedbname().";port=5432";
            case "mssql":
                return "dblib:host=".self::thehost().";dbname=".self::thedbname().";port=1433";
            default:
                return "mysql:host=localhost;dbname=".self::thedbname().";port=3306";
        }
    }

    public function themysql_dsn(){
        return "mysql:host=localhost;dbname=".self::thedbname().";port=3306";
    }

}

/**
 * Trait SqlBakeConfig
 */
trait SqlBakeConfig{

    public function get_env(){
        return ENV;
    }

    public function get_sysenv(){
        return shell_exec("env");
    }

    public function mydb_import($host, $user, $pass, $dbname, $importfle){
        try {
            $result = shell_exec('/usr/bin/mysql -h'.$host.' -u'.$user.' -p'.$pass.' '.$dbname.' < '.$importfle.' > /dev/null 2>&1');
            if ($result === null) {
                throw new Exception("Error: MySQL client library not installed. Please install the MySQL client library.");
            }
            return $result;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    public function mydb_dump_all($host, $port, $user, $pass, $dbname, $options, $backupfile){
        try {
            $result = shell_exec('/usr/bin/mysqldump -h'.$host.' --port='.$port.' -u'.$user.' -p'.$pass.' '.$dbname.' '.$options.' > '.$backupfile.'');
            if ($result === null) {
                throw new Exception("Error: MySQL client library not installed. Please install the MySQL client library.");
            }
            return $result;
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }
}
?>
