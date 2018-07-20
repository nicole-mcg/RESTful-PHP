<?php

    include('../restful.php');

    class Endpoint extends RESTfulEndpoint {

        function get($params) {
            
            if (!array_key_exists('id', $params)) {
                return ['error' => 'You need to enter an ID'];
            }
            
            return ['message' => 'Success'];
        }

        function post($params) {
            return ['message' => 'POST method works!'];
        }

    }

    $endpoint = new Endpoint();
    $endpoint->handleRequest();

?>