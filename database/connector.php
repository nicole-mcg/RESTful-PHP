<?php

    #Author: Nik M
    #Website: https://github.com/nik-m2/RESTful-PHP

    class DatabaseConnector {

        // Should set $connection property
        function connect($address, $db_name, $username, $password) {
            $this->connection = null;
        }

        function escape_string($string) {

        }

        function multi_query($query) {

        }

        function query($sql) {

        }

        function insert_id() {
            
        }

        function num_rows() {
            
        }

        function disconnect() {

        }

        function get_result_code() {

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
        // keys in $properties will also be added as properties 
        function get_row_as_class($class_name, $properties=null) {
        }

        // Returns an array of field info
        function get_field_info() {
        }

        function free() {
        }

    }

?>