<?php

    include('../restful.php');

    class Endpoint extends RESTfulEndpoint {

        function get($params) {
            $response = [];

            $response['test'] = 'works';

            return $response;
        }

    }

    $endpoint = new Endpoint();
    $endpoint->handleRequest();

?>