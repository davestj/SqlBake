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
 * Extended PDO Wrapper for custom and optimized queries
 *
 * @author davestj@gmail.com
 *
 * Class DevOpsToolSmithDb
 */
class DevOpsToolSmithDb extends PDO {
    /**
     * @var string|null
     */
    private ?string $error = null;

    /**
     * @var string|null
     */
    private ?string $sql = null;

    /**
     * @var array
     */
    private array $bind = [];

    /**
     * @var callable|null
     */
    private $errorCallbackFunction = null;

    /**
     * @var string
     */
    private string $errorMsgFormat = "html";

    /**
     * class constructor
     *
     * Connect on instantiation
     *
     * @param string $dsn
     * @param string $user
     * @param string $passwd
     */
    public function __construct(string $dsn, string $user = "", string $passwd = "") {
        $options = [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        try {
            parent::__construct($dsn, $user, $passwd, $options);
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * debug method, backtrace error and print in html or plain text
     *
     * Internal debug method
     *
     * @function debug()
     */
    private function debug(): void {
        if (!empty($this->errorCallbackFunction)) {
            $error = ["Error" => $this->error];
            if (!empty($this->sql))
                $error["SQL Statement"] = $this->sql;
            if (!empty($this->bind))
                $error["Bind Parameters"] = trim(print_r($this->bind, true));
            /**
             * @function debug_backtrace();
             */
            $backtrace = debug_backtrace();
            if (!empty($backtrace)) {
                foreach ($backtrace as $info) {
                    if ($info["file"] != __FILE__)
                        $error["Backtrace"] = $info["file"] . " at line " . $info["line"];
                }
            }

            $msg = "";
            if ($this->errorMsgFormat == "html") {
                if (!empty($error["Bind Parameters"]))
                    $error["Bind Parameters"] = "<pre>" . $error["Bind Parameters"] . "</pre>";
                $msg .= "\n" . '<div style="color:red; background-color:grey;" ">' . "\n\t<h3>SQL Error</h3>";
                foreach ($error as $key => $val)
                    $msg .= "\n\t<label>" . $key . ":</label>" . $val;
                $msg .= "\n\t</div>\n</div>";
            } elseif ($this->errorMsgFormat == "text") {
                $msg .= "SQL Error\n" . str_repeat("-", 50);
                foreach ($error as $key => $val)
                    $msg .= "\n\n$key:\n$val";
            }

            $func = $this->errorCallbackFunction;
            $func($msg);
        }
    }

    /**
     * crud ops, delete record
     *
     * @param string $table
     * @param string $where
     * @param string|array $bind
     */
    public function delete(string $table, string $where, $bind = ""): void {
        $sql = "DELETE FROM " . $table . " WHERE " . $where . ";";
        $this->run($sql, $bind);
    }

    /**
     *  filter data method to use prior to insert pdo update methods
     *
     * @param string $table
     * @param array $info
     * @return array
     */
    private function filter(string $table, array $info): array {
        $driver = $this->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver == 'sqlite') {
            $sql = "PRAGMA table_info('" . $table . "');";
            $key = "name";
        } elseif ($driver == 'mysql') {
            $sql = "DESCRIBE " . $table . ";";
            $key = "Field";
        } else {
            $sql = "SELECT column_name FROM information_schema.columns WHERE table_name = '" . $table . "';";
            $key = "column_name";
        }

        if (false !== ($list = $this->run($sql))) {
            $fields = [];
            foreach ($list as $record)
                $fields[] = $record[$key];
            return array_values(array_intersect($fields, array_keys($info)));
        }
        return [];
    }

    /**
     * scrub and reset bind
     *
     * @param mixed $bind
     * @return array
     */
    private function cleanup($bind): array {
        if (!is_array($bind)) {
            if (!empty($bind))
                $bind = [$bind];
            else
                $bind = [];
        }
        return $bind;
    }

    /**
     * crud ops, insert data
     *
     * @param string $table
     * @param array $info
     * @return array|bool|int
     */
    public function insert(string $table, array $info) {
        $fields = $this->filter($table, $info);
        $sql = "INSERT INTO " . $table . " (" . implode($fields, ", ") . ") VALUES (:" . implode($fields, ", :") . ");";
        $bind = [];
        foreach ($fields as $field)
            $bind[":$field"] = $info[$field];
        return $this->run($sql, $bind);
    }

    /**
     *  run prepared statement and return results in associative array
     *
     * @param string $sql
     * @param mixed $bind
     * @return array|bool|int
     */
    public function run(string $sql, $bind = "") {
        $this->sql = trim($sql);
        $this->bind = $this->cleanup($bind);
        $this->error = "";

        try {
            $pdostmt = $this->prepare($this->sql);
            if ($pdostmt->execute($this->bind) !== false) {
                if (preg_match("/^(" . implode("|", array("select", "describe", "pragma")) . ") /i", $this->sql))
                    return $pdostmt->fetchAll(PDO::FETCH_ASSOC);
                elseif (preg_match("/^(" . implode("|", array("delete", "insert", "update")) . ") /i", $this->sql))
                    return $pdostmt->rowCount();
            }
        } catch (PDOException $e) {
            throw new Exception("SQL execution error: " . $e->getMessage());
        }
    }

    /**
     * selective SELECT
     *
     * @param string $table
     * @param string $where
     * @param string|array $bind
     * @param string $fields
     * @return array|bool|int
     */
    public function select(string $table, string $where = "", $bind = "", string $fields = "*") {
        $sql = "SELECT " . $fields . " FROM " . $table;
        if (!empty($where))
            $sql .= " WHERE " . $where;
        $sql .= ";";
        return $this->run($sql, $bind);
    }

}


class legacyDb {

    /**
     * @var string
     */
    private string $host = DB_HOST;

    /**
     * @var string
     */
    private string $user = DB_LOGIN;

    /**
     * @var string
     */
    private string $pass = DB_PASS;

    /**
     * @var string
     */
    public string $dbname = DB_DB;

    /**
     * @var mysqli
     */
    private mysqli $connection;

    /**
     * @var mysqli
     */
    public mysqli $remote_connection;

    /**
     * Connect to the legacy database
     *
     * @return mysqli
     */
    public function legacy_connect(): mysqli {
        try {
            $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->dbname);
            if ($this->connection->connect_error) {
                throw new Exception("Connection failed: " . $this->connection->connect_error);
            }
        } catch (Exception $e) {
            throw new Exception("Unable to connect to the legacy database: " . $e->getMessage());
        }
        return $this->connection;
    }

    /**
     * Connect to a remote legacy database
     *
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param string $dbname
     * @param int $port
     */
    public function remote_connect(string $host, string $user, string $pass, string $dbname, int $port): void {
        try {
            $this->remote_connection = new mysqli("$host:$port", $user, $pass, $dbname);
            if ($this->remote_connection->connect_error) {
                throw new Exception("Unable to connect to remote host: $host:$port" . $this->remote_connection->connect_error);
            }
        } catch (Exception $e) {
            throw new Exception("Error connecting to remote legacy database: " . $e->getMessage());
        }
    }

    /**
     * Check if the local database exists
     *
     * @param string $dbname
     * @return bool
     */
    public function does_local_db_exist(string $dbname): bool {
        try {
            $this->connection = new mysqli($this->host, $this->user, $this->pass);
            if (!$this->connection->select_db($dbname)) {
                $sql = "CREATE DATABASE $dbname";
                if ($this->legacy_query($sql)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception("Error checking local database: " . $e->getMessage());
        }
    }

    /**
     * Perform a legacy query
     *
     * @param string $sql
     * @return bool|mysqli_result
     */
    public function legacy_query(string $sql) {
        try {
            return $this->connection->query($sql);
        } catch (Exception $e) {
            throw new Exception("Error executing legacy query: " . $e->getMessage());
        }
    }

}
?>
