<?php
    
    require_once('../database.php');

    class MySQLiConnector {

        function connect($address, $db_name, $username, $password) {
            $mysqli = new mysqli($address, $username, $password);
            $message = true;

            if ($mysqli->connect_errno) {
                $message = 'Could not connect to database server.';
            }

            $query = 'CREATE DATABASE IF NOT EXISTS ' . $db_name;
            $result = $mysqli->query($query);
            if (!$result) {
                $message = 'Could not verify database.';
            }

            if (!$mysqli->select_db($db_name)) {
                $message = 'Could not select database.';
            }

            $this->connection = $mysqli;
            return $message;
        }

        function query($sql) {
            return $this->connection->query($sql);
        }

        function disconnect() {
            return $this->connection->close();
        }
    }
    

?>