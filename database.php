<?php

    $CONNECTORS = [
        'mysqli' => 'MySQLiConnector'
    ];

    class DatabaseConnector {

        // Should set $connection property
        function connect($address, $db_name, $username, $password) {

        }

        function query($sql) {

        }

        function insert_id() {
            
        }

        function num_rows() {
            
        }

        function disconnect() {

        }

    }

    class DatabaseConnection {

        static $table_config;

        function __construct($connector) {
            $this->connector = $connector;
            $this->connected = true;
            $this->error = false;
            $this->message = '';
        }

        function verify_table($table_name) {

            if (!array_key_exists($table_name, self::$table_config)) {
                $this->message = 'Could not find configuration for table ' . $table_name;
                $this->error = true;
                return false;
            }

            $table_config = self::$table_config->$table_name;

            $query = 'CREATE TABLE IF NOT EXISTS `' . $table_name . '` (';

            $len = count((array) $table_config);
            $index = 0;
            foreach ($table_config as $key => $value) {

                if ($key === 'primary_key') {
                    continue;
                }


                if ($index != 0) {
                    $query .= ', ';
                }

                $query .= '`' . $key . '` ' . $value;

                $index++;
            }

            if (property_exists($table_config, 'primary_key')) {
                $query .= ', PRIMARY KEY (`' . $table_config->primary_key . '`)';
            }

            $query .= ');';

            $result = $this->connector->query($query);
            if (!$result) {
                $this->message = "Could not verify table " . $table_name;
                return false;
            }

            return true;
        }

        function insert($table, $values) {
            $query = "INSERT INTO {$table} (";


            $first = true;
            foreach ($values as $key => $value) {
                if (!$first) {
                    $query .= ',';
                }
                $query .= "{$key}";
                $first = false;
            }

            $query .= ") VALUES (";

            $first = true;
            foreach ($values as $key => $value) {
                if (!$first) {
                    $query .= ',';
                }
                $query .= "'" . $value . "'";
                $first = false;
            }
            
            $query .= ")";

            $result = $this->connector->query($query);

            if (!$result) {
                $this->error = true;
                $this->message = 'Could not insert into table ' . $table;

                if ($DEBUG) {
                    echo $query, $this->connector->connection->error . '<br/>';
                    echo var_dump($result);
                }

                return false;
            }

            return true;
        }

        function insert_id() {
            return $this->connector->insert_id();
        }

        function num_rows() {
            return $this->connector->num_rows();
        }

        // Params: columns (string[]), where (string), group_by (string), order_by (string), limit (number)
        function select($table, $params, $desc=true) {
            $columns = $params['columns'];
            $where = $params['where'];
            $group_by = $params['group_by'];
            $order_by = $params['order_by'];
            $limit = $params['limit'];

            $query = "SELECT ";

            if ($columns) {
                $first = true;
                foreach ($columns as $key => $value) {
                    if (!$first) {
                        $query .= ",";
                    }
                    $query .= $value;
                    $first = false;
                }
            } else {
                $query .= '* '
            }

            $query .= "FROM " . $table . " ";

            if ($where) {
                $query .= "WHERE " . $where . " ";
            }

            if ($group_by) {
                $query .= "GROUP BY" . $group_by . " ";
            }

            $query .= "ORDER BY "
            if ($order_by) {
                $query .= $order_by . " ";
            }

            $query .= $desc ? "DESC " : "ASC ";

            if ($limit) {
                $query .= "LIMIT " . $limit;
            }

            $result = $this->connector->query($query);

            return $result;
        }

        function disconnect($is_error=false, $message = '') {
            $this->error = $is_error;

            if ($message != '') {
                $this->message = $message;
            }

            $this->connector->disconnect();
            $this->connected = false;
        }

    }

    //TODO make sure file exists
    $json_string = file_get_contents(__DIR__ . '/../../../database-config.json');
    $database_config = json_decode($json_string);

    $database_config->tables->restful_accounts = (object) [
        'primary_key' => 'id',
        'id' => 'INT NOT NULL AUTO_INCREMENT',
        'username' => 'CHAR(12)',
        'password' => 'CHAR(64) NOT NULL'
    ];

    DatabaseConnection::$table_config = $database_config->tables;

    $connectorName = $database_config->connector;
    require('connectors/' . $connectorName . '.php');

    $connector = new $CONNECTORS[$connectorName]();

    $db_name = $database_config->db_name;
    $db_user = $database_config->db_user;
    $db_pass = $database_config->db_pass;

    $result = $connector->connect('127.0.0.1', $db_name, $db_user, $db_pass);

    $connection = new DatabaseConnection($connector);

    if ($result !== TRUE) {
        //TODO error
        echo $result;
    }

    if (!$connection->verify_table('restful_accounts')) {
        //TODO error
        echo 'could not verify accounts table', $connection->message, var_dump($connection);

        if ($DEBUG) {

        }
    }

    return $connection;

?>