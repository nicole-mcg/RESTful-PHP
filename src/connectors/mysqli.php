<?php
    
    require_once('../database.php');

    class MySQLiConnector {

        function connect($address, $username, $password) {
            $mysqli = new mysqli($address, $username, $password);
            $message = true;

            if ($mysqli->connect_errno) {
                $message = 'Could not connect to database.';
            }

            $query = 'CREATE DATABASE IF NOT EXISTS ' . $db_name;
            if ($connection->connected && !$result = $mysqli->query($query)) {
                $message = 'Could not verify database.';
            }

            if ($connection->connected && !$mysqli->select_db($db_name)) {
                $message = 'Could not select database.';
            }

            $this->connection = $mysqli;
        }

        function query($sql) {
            return $this->connection->query($sql);
        }

        function disconnect() {
            return $this->connection->close();
        }
    }
    

?>