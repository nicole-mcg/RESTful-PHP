# RESTful-PHP

In-depth tutorial: http://ossoftware.ca/blog-post?id=1

This is a PHP framework for a RESTful API. It provides an easy framework to add endpoints that handle any request type. It also handles database connection, creation and queries. 

In order to make this easy to set up, it uses HTTP protocol for data (GET, POST) and does not handle routing. Example request: `http://localhost/api/endpoint.php?id=5`. Responses are in JSON.

## Usage

- configure `rest-config.json` (Don't put this in a public folder!)
- Include `restful.php`
- Extend `RESTfulEndpoint`
- Handle the request

```
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
```

## Database Usage

Access database from within endpoints via `$this->db`. Methods in `DatabaseConnection`:

- `verify_table(string $tableName)` - This will create the table with the specified name based on `rest-config.json`, if it doesn't already exist. It will also create the database if it doesn't exist. Use this for any tables used, at the start of your endpoint handlers.

- `insert(string $tableName, array $values)` - Inserts the array into the specified table. The array's keys should match the column names of the table being inserted into

- `select(string $tableName, array $params)` - Retrieves data from the database
    `$params` - Keys: `string[] column`, `string where`, `string group_by`, `string order_by`, `int limit`. `where`, `group_by`, and `order_by` are all placed directory in SQL queries as shown: `SELECT * WHERE $where`
    
- `update(string $tableName, array $values, string $where)` - Updates the table with the specified values on rows where `$where` evaluates to true. See `select` above for contents of `$where`

- `updateAll(string $tableName, array $values)` - Updates all rows in the table. This functionality is prevented in `update`, so it is provided here.

- `delete(string $tableName, string $where)` - Deletes data from the table on rows where `$where` evaluates to true. See `select` above for contents of `$where`

- `updateAll(string $tableName, array $values)` - Deletes all rows in the table. This functionality is prevented in `delete`, so it is provided here.



- `disconnect($isError=false, $message='')` - Closes the server connection.
`
