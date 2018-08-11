<?php

    #Author: Connor McGrogan
    #Website: https://github.com/c-mcg/RESTful-PHP

    const RESULT_UNKNOWN = -1;
    const RESULT_SUCCESS = 0;
    const RESULT_NO_TABLE = 1;
    const RESULT_MISSING_KEY = 2;
    const RESULT_NO_CONFIG = 3;
    const RESULT_DUPLICATE_KEY = 4;
    const RESULT_ = 5;

    if (!isset($_COOKIE['rest_auth'])) {
        $GLOBALS['rest_id'] = hash('sha256', 'salt' . rand());
        setcookie('rest_auth', $GLOBALS['rest_id'], time() + (86400 * 365), '/');
    } else {
        $GLOBALS['rest_id'] = $_COOKIE['rest_auth'];
    }

    $GLOBALS['debug'] = false;

    $GLOBALS['db'] = include('database/database.php');

    if ($GLOBALS['db'] === null) {
        echo "Could not connect to database";
        die();
    }

    class RESTfulEndpoint {

        function __construct() {
            $this->db = $GLOBALS['db'];
        }

        function authenticate($method) {
            return true;
        }

        function getJSONInput() {
            $json = substr(file_get_contents("php://input"), 0);
            $json = substr($json, 0, strlen($json));
            $json = str_replace('\\', '', $json);
            return json_decode($json, true);
        }
        
        // Get items
        function _get() {
            if (method_exists($this, 'get')) {
                $params = $_GET;
                if (count($params) === 0) {
                    $params = $this->getJSONInput();
                }
                return $this->get($params);
            }
        }

        // New items
        function _post() {
            if (method_exists($this, 'post')) {
                $params = $_POST;
                if (count($params) === 0) {
                    $params = $this->getJSONInput();
                }
                return $this->post($params);
            }
        }

        // Replace items
        function _put() {
            if (method_exists($this, 'put')) {
                $params = $this->getJSONInput();
                return $this->put($params);
            }
        }

        // Update items
        function _patch() {
            if (method_exists($this, 'patch')) {
                $params = $this->getJSONInput();
                return $this->patch($params);
            }
        }

        // Delete items
        function _delete() {
            if (method_exists($this, 'delete')) {
                $params = $this->getJSONInput();
                return $this->delete($params);
            }
        }

        function _escape_params($params) {
            foreach ($params as $key => $val) {
                $params[$key] = $this->db->connector->escape_string($val);
            }
            return $params;
        }

        function handleRequest() {
            $method = $_SERVER['REQUEST_METHOD'];

            if ($this->authenticate($method)) {

                switch($method) {

                    case 'GET':
                        $response = $this->_get();
                        break;

                    case 'POST':
                        $response = $this->_post();
                        break;

                    case 'PUT':
                        $response = $this->_put();
                        break;

                    case 'PATCH':
                        $response = $this->_patch();
                        break;

                    case 'DELETE':
                        $response = $this->_delete();
                        break;
                }

                if (empty($response)) {
                    $response = [];
                }

            } else {
                $response = ['error' => 'You do not have permission to use this part of the API'];
            }

            echo json_encode($response);
        }
    }

    class DatabaseEndpoint extends RESTfulEndpoint {

        function __construct($tableName, $locked_keys=[]) {
            parent::__construct();
            $this->table = $tableName;

            $this->locked_keys = $locked_keys;
        }

        function verify_table() {
            return $this->db->verify_table($this->table);
        }

        function get($params, $where=null) {
            if (!$this->verify_table()) {
                return [
                    'error' => 'Could not find table',
                    'return_code' => RESULT_NO_TABLE
                ];
            }

            if (array_key_exists('id', $params)) {
                if (!$where) {
                    $where = "";
                }
                $where .= 'AND id=' . $params['id'];
            }

            $limit = 50;
            if (array_key_exists('limit', $params)) {
                $limit = $params['limit'];
            }

            // TODO verify columns are correct
            $result = $this->db->select($this->table, [
                'limit' => $limit,
                'where' => $where
            ]);

            if (!$result) {
                return [
                    'error' => 'Could not find item'
                ];
            }

            $response = [];
            while ($row = $result->get_next_row()) {
                $response[] = $row;
            }

            return $response;
        }

        function post($params) {
            if (!$this->verify_table()) {
                return [
                    'error' => 'Could not find table',
                    'return_code' => RESULT_NO_TABLE
                ];
            }

            $tableName = $this->table;

            $table_config = (array) DatabaseConnection::$table_config->$tableName;
            if ($table_config) {
                foreach ($table_config as $key => $value) {

                    if ($key === 'primary_key') {
                        continue;
                    }

                    if (in_array($key, $this->locked_keys)) {
                        if (in_array($key, $params)) {
                            unset($params[$key]);
                        }
                        continue;
                    }

                    if (isset($value->nullable) && !$value->nullable && !isset($value->default) && !isset($params[$key])) {
                        $message = "";
                        if ($GLOBALS['debug']) {
                            $message = 'Missing key: ' . ($GLOBALS['debug'] ? "column=" . $key : '') . ' from table ' . $this->table;
                        } else {
                            $message = 'Missing parameter';
                        }
                        return [
                            'error' => $message,
                            'key' => $key,
                            'return_code' => RESULT_MISSING_KEY
                        ];
                    }
                }
            } else {
                $message = "";
                if ($GLOBALS['debug']) {
                    $message = 'Could not find config for table: ' . $this->table;
                } else {
                    $message = 'Error loading configuration';
                } 

                return [
                    'error' => $message,
                    'return_code' => RESULT_NO_CONFIG
                ];
            }

            if (!$this->db->insert($this->table, $params)) {
                $message = "";
                if ($GLOBALS['debug']) {
                    $message = 'Could not add item: ' . $this->db->message;
                } else {
                    $message = 'Could not add item';
                }
                return [
                    'error' => $message,
                    'return_code' => $this->db->connector->get_result_code()
                ];
            }

            return [
                'message' => 'Successfully added item',
                'id' => isset($params['id']) ? $params['id'] : $this->db->insert_id(),
                'return_code' => RESULT_SUCCESS
            ];
        }

        function put($params, $where=null) {
            if (!$this->verify_table()) {
                return [
                    'error' => 'Could not find table',
                    'return_code' => RESULT_NO_TABLE
                ];
            }

            $num_keys_verified = 0;
            $table_config = (array) DatabaseConnection::$table_config->$this->table;
            if ($table_config) {

                foreach ($table_config as $key => $value) {

                    if ($key === 'primary_key') {
                        continue;
                    }

                    if (in_array($key, $this->locked_keys)) {
                        if (in_array($key, $params)) {
                            unset($params[$key]);
                        }
                        continue;
                    }

                    if (!isset($params[$key])) {
                        return [
                            'error' => 'No value for ' . ($GLOBALS['debug'] ? "column: " : "") . $key,
                            'key' => $key,
                            'return_code' => RESULT_MISSING_KEY
                        ];
                    }
                    $num_keys_verified++;
                }

            } else {

                $return_val = ['return_code' => RESULT_NO_CONFIG];

                if ($GLOBALS['debug']) {
                    $return_val['error'] = 'Could not find config for table: ' . $this->table;
                } else {
                    $return_val['error'] = 'Error loading configuration';
                }

                return $return_val;

            }

            if (!len($params) === $num_keys_verified) {
                return [
                    'error' => "Not all values were entered",
                    'key' => 'UNKNOWN KEY',
                    'return_code' => RESULT_MISSING_KEY
                ];
            }

            $where = (!$where ? "" : $where . ' AND ') . "id='" . $params['id'] . "'"; 
            $result = $this->db->update($this->table, $params, $where);

            if (!$result) {
                return [
                    'error' => 'Could not update item',
                    'return_code' => $this->db->connector->get_result_code()
                ];
            }

            return [
                'message' => 'Successfully updated item',
                'return_code' => RESULT_SUCCESS
            ];
        }

        function patch($params, $where=null) {
            if (!$this->verify_table()) {
                return [
                    'error' => 'Could not find table',
                    'return_code' => RESULT_NO_TABLE
                ];
            }

            if (!array_key_exists('id', $params)) {
                return [
                    'error' => 'No id specified',
                    'key' => 'id',
                    'return_code' => RESULT_MISSING_KEY 
                ];
            }

            foreach($params as $key => $val) {
                if (in_array($key, $this->locked_keys)) {
                    unset($params[$key]);
                }
            }

            $where = (!$where ? "" : $where . ' AND ') . "id='" . $params['id'] . "'"; 

            $result = $this->db->update($this->table, $params, $where);

            if (!$result) {
                return [
                    'error' => 'Could not update item',
                    'return_code' => $this->db->connector->get_result_code()
                ];
            }

            return [
                'message' => 'Successfully updated item',
                'return_code' => RESULT_SUCCESS
            ];
        }

        function delete($params, $where=null) {
            if (!$this->verify_table()) {
                return [
                    'error' => 'Could not find table',
                    'return_code' => RESULT_NO_TABLE
                ];
            }

            $id = $params['id'];

            if (!isset($id)) {
                return [
                    'error' => 'No id specified',
                    'key' => 'id',
                    'return_code' => RESULT_MISSING_KEY 
                ];
            }

            $where = (!$where ? "" : $where . ' AND ') . "id='" . $id . "'"; 
            $result = $this->db->delete($this->table, $where);

            if (!$result) {
                $message = "";
                if ($GLOBALS['debug']) {
                    $message = 'Could not delete item ' . $this->db->message;
                } else {
                   $message = 'Could not delete item';
                }
                return [
                    'error' => $message,
                    'return_code' => $this->db->connector->get_result_code()
                ];
            }

            return [
                'message' => 'Successfully deleted item',
                'return_code' => RESULT_SUCCESS
            ];
        }

    }

?>
