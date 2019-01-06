<?php

    #Author: Nik M
    #Website: https://github.com/nik-m2/RESTful-PHP

    include('connector.php');
    include('connection.php');

    $CONNECTORS = [
        'mysqli' => 'MySQLiConnector'
    ];
    
    $GLOBALS['debug'] = false;

    $config_path = isset($GLOBALS['config_path']) ? $GLOBALS['config_path'] : '/../../../../../rest-config.json';

    //TODO make sure file exists
    $json_string = file_get_contents(__DIR__ . $config_path);
    $database_config = json_decode($json_string);


        $GLOBALS['debug'] = true;
    if ($database_config->debug) {
        $GLOBALS['debug'] = true;
    }

    if (!$database_config->tables || !$database_config->connector || !$database_config->db_name || !$database_config->db_user || !$database_config->db_pass) {
        if ($GLOBALS['debug']) {
            echo 'rest-config.json must include keys "db_name", "db_user", "db_pass", "connector", and "tables". If all keys exist, there is probably an error parsing the file. Validate JSON here: https://jsonformatter.curiousconcept.com/<br/>';
        }
        return null;
    }

    DatabaseConnection::$table_config = $database_config->tables;

    $connectorName = $database_config->connector;
    if (!file_exists(dirname(__FILE__) . '/connectors/' . $connectorName . '.php')) {
        if ($GLOBALS['debug']) {
            echo "Cannot find connector '" . $connectorName . '". If it is empty, there was probably an error parsing rest-config.json<br/>';
        }
        return null;
    }

    require('connectors/' . $connectorName . '.php');

    $connector = new $CONNECTORS[$connectorName]();

    $db_name = $database_config->db_name;
    $db_user = $database_config->db_user;
    $db_pass = $database_config->db_pass;

    $result = $connector->connect('127.0.0.1', $db_name, $db_user, $db_pass);

    $connection = new DatabaseConnection($connector);

    if ($result !== TRUE) {
        return null;
    }

    return $connection;

?>