<?php

    include('../restful.php');

    class Endpoint extends RESTfulEndpoint {

        function get($params) {

            $this->db->verify_table('comments');

            $response = [];

            $response['test'] = 'works';

            return $response;
        }

        function post($params) {

            $this->db->verify_table('comments');

            $name = trim($params['name']);
            $content = trim($params['content']);

            if ($name === '' || $content === '') {
                return ['error' => 'You must enter a title, body, and tags'];
            }

            $message = $this->db->insert('comments', [
                'name' => $name,
                'content' => $content,
                'test' => 3
            ]) ? 'success' : 'error';

            return ['message' => $message];

        }

    }

    $endpoint = new Endpoint();
    $endpoint->handleRequest();

?>