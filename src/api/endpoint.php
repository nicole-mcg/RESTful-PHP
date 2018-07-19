<?php

    include('../restful.php');

    class Endpoint extends RESTfulEndpoint {

        function get($params) {
            $this->db->verify_table('test');
            $response = [];

            $response['test'] = 'works';

            return $response;
        }

    }

    $endpoint = new Endpoint();
    $endpoint->handleRequest();

?>