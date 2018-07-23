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

    class DatabaseResult {

        // Sets the current row index
        function set_current_row($index) {
        }

        // Returns an array of the next row
        // Result keys will be indexed if $enumerated is true, otherwise the keys will be field names
        function get_next_row($enumerated=false) {
        }

        // Returns an array of all rows
        // Result keys will be indexed if $enumerated is true, otherwise the keys will be field names
        function get_all_rows($enumerated=false) {
        }

        // Returns a class instance with values from the row set as properties
        // keys in $params will also be added as properties 
        function get_row_as_class($className, $params=null) {
        }

        // Returns an array of field info
        function get_field_info() {
        }

        function free() {
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

            $foreign_keys = [];
            $len = count((array) $table_config);
            $index = 0;
            foreach ($table_config as $key => $value) {

                if ($key === 'primary_key') {
                    continue;
                }

                $value = (array) $value;

                if ($index != 0) {
                    $query .= ', ';
                }

                if (!array_key_exists('type', $value)) {
                    $this->message = "Could not find type for column: " . $key;
                    $this->error = true;
                    return false;
                }

                $type = $value['type'];
                $nullable = array_key_exists('nullable', $value) ? $value['nullable'] : true;
                $auto_increment = array_key_exists('auto_increment', $value) ? $value['auto_increment'] : false;
                $default = array_key_exists('default', $value) ? $value['default'] : null;
                $foreign_key = array_key_exists('foreign_key', $value) ? (array) $value['foreign_key'] : null;

                $query .= '`' . $key . '` ' . $type . 
                    ($nullable ? '' : ' NOT NULL') . 
                    ($default === null ? '' : ' DEFAULT ' . var_export($default, true)) . 
                    ($auto_increment ? ' AUTO_INCREMENT' : '');

                if ($foreign_key !== null) {

                    if (!$foreign_key['table']) {
                        $this->message = "No 'table' key in foreign_key: " . $key;
                        $this->error = true;
                        return false;
                    }

                    if (!$foreign_key['column']) {
                        $this->message = "No 'column' key in foreign_key: " . $key;
                        $this->error = true;
                        return false;
                    }

                    $VALID_ACTIONS = [
                        'cascade',
                        'restrict',
                        'no action',
                        'set default',
                        'set null'
                    ];

                    if ($foreign_key['on_delete']) {
                        $found = false;
                        foreach ($VALID_ACTIONS as $value) {
                            if (strtolower($foreign_key['on_delete']) === $value) {
                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $this->message = 'Error with column ' . $key . 'on_delete must be one of ' . join(' ', $VALID_ACTIONS);
                            $this->error = true;
                            return false;
                        }
                    }

                    if ($foreign_key['on_update']) {
                        $found = false;
                        foreach ($VALID_ACTIONS as $value) {
                            if (strtolower($foreign_key['on_update']) === $value) {
                                $found = true;
                                break;
                            }
                        }

                        if (!$found) {
                            $this->message = 'Error with column ' . $key . 'on_update must be one of ' . join(' ', $VALID_ACTIONS);
                            $this->error = true;
                            return false;
                        }
                    }

                    $foreign_key['og_col'] = $key;

                    $foreign_keys[] = $foreign_key;
                }



                $index++;
            }

            if (property_exists($table_config, 'primary_key')) {
                $query .= ', CONSTRAINT ' . $table_config->primary_key . ' PRIMARY KEY (`' . $table_config->primary_key . '`)';
            }

            foreach ($foreign_keys as $foreign_key) {
                $query .= ', CONSTRAINT fk_' . $foreign_key['og_col'] . ' FOREIGN KEY(' . $foreign_key['og_col'] . ')' . ' REFERENCES ' . $foreign_key['table'] . '(' . $foreign_key['column'] . ')';

                if ($foreign_key['on_update']) {
                    $query .= ' ON UPDATE ' . $foreign_key['on_update'];
                }

                if ($foreign_key['on_delete']) {
                    $query .= ' ON DELETE ' . $foreign_key['on_delete'];
                }
             }

            $query .= ');';

            $result = $this->connector->query($query);
            if (!$result) {
                $this->message = "Could not verify table " . $table_name . "<br/>Query: " . $query . '<br/> Error: ' . $this->connector->connection->error;
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
        function select($table, $params) {
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
                $query .= '* ';
            }

            $query .= "FROM " . $table . " ";

            if ($where) {
                $query .= "WHERE " . $where . " ";
            }

            if ($group_by) {
                $query .= "GROUP BY" . $group_by . " ";
            }

            
            if ($order_by) {
                $query .= "ORDER BY ";
                $query .= $order_by . " ";
                $query .= $desc ? "DESC " : "ASC ";
            }

            if ($limit) {
                $query .= "LIMIT " . $limit;
            }

            $result = $this->connector->query($query);

            return $result;
        }

        function update($table, $values, $where=null) {
            $query = "UPDATE " . $table . ' SET ';


            $first = true;
            foreach ($values as $key => $value) {
                if (!$first) {
                    $query .= ',';
                }
                $query .= $key . '=\'' . $value . '\' ';
                $first = false;
            }

            if ($where) {
                $query .= "WHERE " . $where . " ";
            }

            $result = $this->connector->query($query);

            return $result;
        }

        function delete($table, $where) {

            if (!$where) {
                if ($DEBUG) {
                    echo "You almost deleted the whole table! You need a where clause.";
                }

                return null;
            }

            $query = "DELETE FROM " . $table . ' ';

            $query .= "WHERE " . $where . " ";

            $result = $this->connector->query($query);

            return $result;
        }

        function delete_all($table) {
            $result = $this->connector->query("DELETE FROM " . $table . ' ');

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

    $DEBUG = false;

    $config_path = $CONFIG_PATH ? $CONFIG_PATH : '/../../../../rest-config.json';

    //TODO make sure file exists
    $json_string = file_get_contents(__DIR__ . $config_path);
    $database_config = json_decode($json_string);

    if ($database_config->debug) {
        $DEBUG = true;
    }

    $database_config->tables->restful_accounts = (object) [
        'primary_key' => 'id',
        'id' => [
            'type' => 'INT',
            'nullable' => false,
            'auto_increment' => true
        ],
        'username' => [
            'type' => 'CHAR(12)',
            'nullable' => false
        ],
        'password' => [
            'type' => 'CHAR(64)',
            'nullable' => false
        ]
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