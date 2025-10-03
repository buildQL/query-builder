<?php declare(strict_types=1);

namespace BuildQL\Database\Query;

use mysqli;
use Dotenv\Dotenv;
use BuildQL\Database\Query\Builder;
use BuildQL\Database\Query\Exception\BuilderException;

/*
------------------------------------------------------------------------------------------------    
DB class provide flexibility for developers to interact and connect with (mysql) database without
directly need to know complex SQL queries and its execution process.
------------------------------------------------------------------------------------------------
*/

class DB{
    /**
     *  Server Credentials
     */
    private string $servername;
    private string $username;
    private string $password;


    /**
     *  Database that are globally use in project
     */
    private ?string $database;


    /**
     *  Connection b/w database and server
     */
    protected ?mysqli $conn;


    /**
     *  Instance of that class stored in $instance property
     */
    protected static ?self $instance = null;


    /**
     *  Private constructor that are being set database configuration and established connection
     */
    private function __construct(string $server, string $username, string $pass, int $port = 3306)
    {
        $this->servername = $server;
        $this->username = $username;
        $this->password = $pass;
        $this->conn = new mysqli($server, $username, $pass, port: $port);

        if ($this->conn->connect_error){
            throw new BuilderException("Connection Failed Due to : " . $this->conn->connect_error);
        }
    }


    /**
     *  Table method used to define table and chain builder methods.
     *  @throws BuildQL\Database\Query\Exception\BuilderException
     */
    public static function table(string $table, ?string $database = null): Builder
    {
        if (self::getConnection() == null){
            throw new BuilderException("Connection is not established right now.");
        }
        return new Builder($table, self::getConnection(), $database);
    }


    /**
     *  Boot the database connection
     *  @param ?string $absoluteEnvPath - The file path should be the absolute env path
     *  @throws BuildQL\Database\Query\Exception\BuilderException
     */
    public static function boot(?string $absoluteEnvPath = null): void
    {
        if ($absoluteEnvPath != null && file_exists($absoluteEnvPath . "/.env")){
            $envFilePath = $absoluteEnvPath;
        }
        elseif (file_exists(dirname(__DIR__, 1) . "/.env")){
            $envFilePath = dirname(__DIR__, 1);
        }
        else{
            throw new BuilderException(".env file not found. Create .env file in your project root or call DB::boot('/path/to/env/directory') with absolute path.");
        }

        $dotenv = Dotenv::createImmutable($envFilePath);
        $dotenv->safeLoad();

        if (!empty($_ENV['DB_USERNAME']) && !empty($_ENV['DB_HOST'])){
            DB::setConnection(
                $_ENV['DB_HOST'],
                $_ENV['DB_USERNAME'],
                $_ENV['DB_PASSWORD'],
                $_ENV['DB_DATABASE'] ?? null,
                (int) $_ENV['DB_PORT']
            );
        }
        else {
            throw new BuilderException("Missing database configuration in .env file.");
        }
    }


    /**
     *  Get configured mysqli connection
     */
    private static function getConnection(): ?mysqli
    {
        return self::$instance?->conn;
    }


    /**
     *  Configure database connection manually
     *  @throws BuildQL\Database\Query\Exception\BuilderException
     */
    public static function setConnection(string $server, string $username, string $pass, ?string $database = null, int $port = 3306): void
    {
        if (self::$instance){
            throw new BuilderException("Connection already established. Connection will not be established more than once.");
        }

        self::$instance = new self($server, $username, $pass, $port);
        
        if ($database){
            self::$instance->setDatabaseGlobally($database);
        }
    }


    /**
     *  Set database globally in your connection
     *  @throws BuildQL\Database\Query\Exception\BuilderException
     */
    public static function setDatabaseGlobally(string $database): void
    {
        if (self::getConnection() == null){
            throw new BuilderException("Connection is not established");
        }
        self::$instance->conn->select_db($database);
        self::$instance->database = $database;
    }


    /**
     *  Get database name that are globally used.
     */
    public static function getGlobalDatabase(): ?string
    {
        return self::$instance?->database;
    }


    /**
     *  Reset and close Database connection
     */
    public static function resetConnection(): void
    {
        self::$instance?->conn->close();
        self::$instance = null;
    }

    /**
     *  Write and execute raw SQL query
     *  @throws BuildQL\Database\Query\Exception\BuilderException
     */
    public static function raw(string $sql, array $bind = []): mixed
    {
        mysqli_report(MYSQLI_REPORT_OFF);
        if (self::$instance == null){
            throw new BuilderException("Connection is not established");
        }
        if ($prepare = self::$instance->conn->prepare($sql)){
            if ($bind && stripos($sql, "?") !== false){
                $bind_type = '';
                foreach($bind as $val){
                    $bind_type .= is_double($val) || is_float($val) ? "d" : (is_int($val) ? "i" : "s");
                }
                $prepare->bind_param($bind_type, ...$bind);
            }
            if ($prepare->execute()){
                if (preg_match("/^select/i", $sql)){
                    return $prepare->get_result()->fetch_all(MYSQLI_ASSOC);
                }
                else{
                    return true;
                }
            }
            else{
                throw new BuilderException("Query Execution Failed : " . self::$instance->conn->error);
            }
        }
        else{
            throw new BuilderException("Query Preparation Failed :" . self::$instance->conn->error);
        }
    }
}


?>