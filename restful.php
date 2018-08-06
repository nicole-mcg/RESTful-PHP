<?php 

    if (!isset($_COOKIE['rest_auth'])) {
        $GLOBALS['rest_id'] = hash('sha256', 'salt' . rand());
        setcookie('rest_auth', $GLOBALS['rest_id'], time() + (86400 * 365), '/');
    } else {
        $GLOBALS['rest_id'] = $_COOKIE['rest_auth'];
    }

    $GLOBALS['debug'] = false;

    $GLOBALS['db'] = include('database.php');

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
        
        // Get items
        function _get() {
            if (method_exists($this, 'get')) {
                return $this->get($this->_escape_params($_GET));
            }
        }

        // New items
        function _post() {
            if (method_exists($this, 'post')) {
                return $this->post($this->_escape_params($_POST));
            }
        }

        // Replace items
        function _put() {
            if (method_exists($this, 'put')) {
                parse_str(file_get_contents("php://input"), $params);
                return $this->put($this->_escape_params($params));
            }
        }

        // Update items
        function _patch() {
            if (method_exists($this, 'patch')) {
                parse_str(file_get_contents("php://input"), $params);
                return $this->patch($this->_escape_params($params));
            }
        }

        // Delete items
        function _delete() {
            if (method_exists($this, 'delete')) {
                parse_str(file_get_contents("php://input"), $params);
                return $this->delete($this->_escape_params($params));
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

        function get($params) {
            if (!$this->verify_table()) {
                return ['error' => 'Could not find database table'];
            }

            $where = null;
            if (array_key_exists('id', $params)) {
                $where = 'id=' . $params['id'];
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
                return ['error' => 'Could not find item'];
            }

            $response = [];
            while ($row = $result->get_next_row()) {
                $response[] = $row;
            }

            return $response;
        }

        function post($params) {
            if (!$this->verify_table()) {
                if ($GLOBALS['debug']) {
                    return ['error' => 'Could not verify table: ' . $this->db->message];
                }
                return ['error' => 'Could not find table'];
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
                        if ($GLOBALS['debug']) {
                            return ['error' => 'Missing key: ' . ($GLOBALS['debug'] ? "column=" . $key : '') . ' from table ' . $this->table];
                        }
                        return ['error' => 'Missing parameter. Please notify site administrator. '];
                    }
                }
            } else {
                if ($GLOBALS['debug']) {
                    return ['error' => 'Could not find config for table: ' . $this->table];
                }
                return ['error' => 'Error loading configuration, please notify site administrator.'];
            }

            if (!$this->db->insert($this->table, $params)) {
                if ($GLOBALS['debug']) {
                    return ['error' => 'Could not add item: ' . $this->db->message];
                }
                return ['error' => 'Could not add item'];
            }

            return [
                'message' => 'Successfully added item',
                'id' => isset($params['id']) ? $params['id'] : $this->db->insert_id()
            ];
        }

        function put($params) {
            
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
                        return ['error' => 'Could not validate params ' . ($GLOBALS['debug'] ? "column=" . $key : '')];
                    }
                    $num_keys_verified++;
                }
            } else {
                if ($GLOBALS['debug']) {
                    return ['error' => 'Could not find config for table: ' . $this->table];
                }
                return ['error' => 'Error loading configuration, please notify site administrator.'];
            }

            if (!len($params) === $num_keys_verified) {
                return ['error' => "Incorrect number of params"];
            }

            return ['message' => 'PUT method'];
        }

        function patch($params) {
            if (!$this->verify_table()) {
                return ['error' => 'Could not find table'];
            }

            if (!array_key_exists('id', $params)) {
                return ['error' => 'You must enter an ID'];
            }

            foreach($params as $key => $val) {
                if (in_array($key, $this->locked_keys)) {
                    unset($params[$key]);
                }
            }

            $result = $this->db->update($this->table, $params, 'id=' . $params['id']);

            if (!$result) {
                return ['error' => 'Could not update item'];
            }

            return ['message' => 'Successfully updated item'];
        }

        function delete($params) {
            if (!$this->verify_table()) {
                return ['error' => 'Could not find table'];
            }

            $id = $params['id'];

            if (!isset($id)) {
                return ['error' => 'You did not enter an ID to delete'];
            }

            $result = $this->db->delete($this->table, "id='" . $id . "'");

            if (!$result) {
                if ($GLOBALS['debug']) {
                    return ['error' => 'Could not delete item ' . $this->db->message];
                }
                return ['error' => 'Could not delete item'];
            }

            return ['message' => 'Successfully deleted item'];
        }

    }

?>
