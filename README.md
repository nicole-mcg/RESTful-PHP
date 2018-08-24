
This is a PHP framework for a RESTful API.

It makes it easy to add endpoints by providing the `RESTfulEndpoint` and `DatabaseEndpoint` classes.
It also provides an interface for SQL database access. 

SQL queries are made to work on all database types, meaning as long as you only use standard SQL types, you should be able to switch just by changing the `connector` attribute. To use a database, a connector for the database must exist. For a list of possible connectors see http://php.net/manual/en/refs.database.php. There is currently only a connector for MySQL.

Accepts requests as form data or JSON strings. Results are JSON strings.

# Setup

See: http://ossoftware.ca/blog-post?id=1

# Endpoint Usage

### Database Endpoint
- Contains automatic handlers for all method types
```
<?php

    include('../restful.php');

    //First param is name of database
    //Second param is an optional array of keys that cannot be set
    //  through the DatabaseEndpoint (they will be silently removed)
    $endpoint = new DatabaseEndpoint('table_name', [
        'is_admin'
    ]);
    $endpoint->handleRequest();

?>
```

```
<?php

    include('../restful.php');
    
    class Endpoint extends DatabaseEndpoint {
        
        function authenticate($method) {
            
            if ($method === 'GET') {
                return true;
            }
            
            return is_user_admin();
        }
        
        function get($params) {
        
            if (!isset($params['id'])) {
                return ['error' => 'Custom error message'];
            }
            
            return parent::get($params);
        }
        
        function post($params) {
            $result = parent::post($params);
            
            if (isset($result['error'])) {
                if ($result['return_code'] === RESULT_DUPLICATE) {
                    return ['error' => 'Tried to add duplicate item']
                }
                return ['error' => 'Error adding item'];
            }
            
            return ['message' => 'Custom success response'];
        }
        
    }

    //First param is name of database
    //Second param is an optional array of keys that cannot be set through
    //  the DatabaseEndpoint (they will be silently removed)
    $endpoint = new Endpoint('table_name', [
        'is_admin'
    ]);
    $endpoint->handleRequest();

?>
```

### Custom endpoint
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

# Database Usage

### `DatabaseConnection` (`$this->db`)
Access database from within endpoints via `$this->db`. Methods in `DatabaseConnection`:

- `verify_table(string $tableName)` - This will create the table with the specified name based on `rest-config.json`, if it doesn't already exist. Returns true if the table exists or was created

- `insert(string $tableName, array $values)` - Inserts the array into the specified table. The array's keys should match the column names of the table being inserted into

- `select(string $tableName, array $params)` - Retrieves data from the database
    `$params` - Keys: `string[] column`, `string where`, `string group_by`, `string order_by`, `int limit`. `where`, `group_by`, and `order_by` are all placed directory in SQL queries as shown: `SELECT * WHERE $where`
    
- `update(string $tableName, array $values, string $where)` - Updates the table with the specified values on rows where `$where` evaluates to true. See `select` above for contents of `$where`

- `updateAll(string $tableName, array $values)` - Updates all rows in the table. This functionality is prevented in `update`, so it is provided here.

- `delete(string $tableName, string $where)` - Deletes data from the table on rows where `$where` evaluates to true. See `select` above for contents of `$where`

- `updateAll(string $tableName, array $values)` - Deletes all rows in the table. This functionality is prevented in `delete`, so it is provided here.

- `disconnect($isError=false, $message='')` - Closes the server connection.

- `insert_id()` - Returns the auto-generated ID from the last query. Returns 0 if none exists.
- `num_rows()` - Returns the number of rows affected by the last query

### `DatabaseResult`
- `get_next_row(bool $enumerated=false)` - Gets the next row from a select query. Keys are indexes instead of column names if `$enumerated` is true
- `get_all_rows(bool $enumerated=false)` - Gets all rows from a select query. Keys are indexes instead of column names if `$enumerated` is true
- `get_row_as_class(string $class_name, array $properties=null)` - Creates an instance of the class specified with the properties from the next row. Properties in `$properties` will also be added.
- `set_current_row` - Sets the current row index
