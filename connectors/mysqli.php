<?php

    class MySQLiConnector extends DatabaseConnector {

        function connect($address, $db_name, $username, $password) {
            $mysqli = new mysqli($address, $username, $password, $db_name);
            $message = true;

            if ($mysqli->connect_errno) {
                $message = 'Could not connect to database server.';
            }

            $this->connection = $mysqli;
            return $message;
        }

        function query($sql) {
            $result = $this->connection->query($sql);
            return $result ? new MySQLiResult($result) : false;
        }

        function insert_id() {
            return $this->connection->insert_id;
        }

        function num_rows() {
            return $this->connection->affected_rows;
        }

        function disconnect() {
            return $this->connection->close();
        }
    }

    class MySQLiResult extends DatabaseResult {

        function __construct($result) {
            $this->result = $result;
        }

        function set_current_row($index) {
            return $this->result->data_seek($index);
        }

        function get_next_row($enumerated=false) {
            return $enumerated ? $this->result->fetch_row() : $this->result->fetch_assoc();
        }

        function get_all_rows($enumerated=false) {
            return $enumerated ? $this->result->fetch_all() : $this->result->fetch_all(MYSQLI_ASSOC);
        }

        function get_row_as_class($className, $params=null) {
            return $this->result->fetch_object($className, $params);
        }

        function get_field_info() {
            return $this->result->fetch_fields();
        }

        function free() {
            return $this->result->free();
        }

    }
    

?>