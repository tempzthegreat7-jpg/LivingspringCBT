<?php


class Database
{
    public $connection;
    /**
     * connects to the database
     * 
     * @param array $config
     */

    public function __construct($config)
    {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['dbName']}";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ];


        try {
            $this->connection = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (PDOException $e) {
            throw new Exception("Database Refused to Connect: {$e->getMessage()}");
        }
    }

    /**
     * Queries The Database
     * 
     * @param string $sqlQuery
     */

    public function query($sqlQuery, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sqlQuery);
            foreach ($params as $param => $value) {
                $stmt->bindValue(':' . $param, $value);
            }
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Query failed: {$e->getMessage()}");
        }
    }
}
