<?php

define("DEBUG", true);

class RESTfulEndpoint {

    function __construct() {
        $this->db = include('database.php');
    }
    
    // Get items
    function _get() {
        if (method_exists($this, 'get')) {
            return $this->get($_GET);
        }
    }

    // New items
    function _post() {
        if (method_exists($this, 'post')) {
            return $this->post($_POST);
        }
    }

    // Replace items
    function _put() {
        if (method_exists($this, 'put')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            return $this->put($params);
        }
    }

    // Update items
    function _patch() {
        if (method_exists($this, 'patch')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            return $this->patch($params);
        }
    }

    // Delete items
    function _delete() {
        if (method_exists($this, 'delete')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            return $this->delete($params);
        }
    }

    function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD'];

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

        echo json_encode($response);
    }
}

?>