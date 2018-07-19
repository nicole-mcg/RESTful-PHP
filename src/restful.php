<?php

class RESTfulEndpoint {
    
    // Get items
    function _get() {
        if (method_exists($this, 'get')) {
            $this.get($_GET);
        }
    }

    // New items
    function _post() {
        if (method_exists($this, 'post')) {
            $this.post($_POST);
        }
    }

    // Replace items
    function _put() {
        if (method_exists($this, 'put')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            $this.put($params);
        }
    }

    // Update items
    function _patch() {
        if (method_exists($this, 'patch')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            $this.patch($params);
        }
    }

    // Delete items
    function _delete() {
        if (method_exists($this, 'delete')) {
            $params = parse_str(file_get_contents("php://input"),$post_vars);
            $this.delete($params);
        }
    }

    function handleRequest() {
        $method = $_SERVER['REQUEST_METHOD']

        switch($method) {

            case 'GET':
                $this._get();
                break;

            case 'POST':
                $this._post();
                break;

            case 'PUT':
                $this._put();
                break;

            case 'PATCH':
                $this._patch();
                break;

            case 'DELETE':
                $this._delete();
                break;
        }
    }
}

?>