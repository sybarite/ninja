<?php
namespace Ninja;

/**
 * Class for connecting to MySQL databases and performing common operations.
 *
 * @throws \Ninja\Db\Exception
 */
class Db extends \Zend_Db_Adapter_Pdo_Mysql
{
    /**
     * Use the INT_TYPE, BIGINT_TYPE, and FLOAT_TYPE with the quote() method.
     */
    const INT_TYPE    = 0;
    const BIGINT_TYPE = 1;
    const FLOAT_TYPE  = 2;

    /**
     *  Constructor for a new db connection
     *  $options = array(
     *  				'host'     => 'localhost'
     *              	'username' => 'my_user',
     *                  'password' => 'qwerty123',
     *                  'dbname'   => 'project_db' )
     *
     *
     * @param array $config
     */
    public function __construct($config)
    {
        parent::__construct($config);

        // The error mode is already set to PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION

        // Set default fetch mode to objects
        $this->setFetchMode(\Zend_Db::FETCH_OBJ);
    }


    /**
     * Converts an object into an array of column-value pairs and inserts it into a table.
     * Note: Arrays and Objects which are not Expressions are ignored.
     *
     * @param mixed $table The table to insert data into.
     * @param \Ninja\Db\Row|mixed $row Object with column-value pairs.
     * @return int The number of affected rows.
     */
    public function insertObject($table, $row)
    {
        return parent::insert($table, $this->_sanitizeObjectToArray($row));
    }

    /**
     * Converts an object into an array of column-value pairs and updates table rows
     * with specified data based on a WHERE clause.
     *
     * Note: Arrays and Objects which are not Expressions are ignored.
     *
     * @param  mixed                $table The table to update.
     * @param  \Ninja\Db\Row|mixed   $row  Object with column-value pairs.
     * @param  mixed                $where UPDATE WHERE clause(s).
     * @return int                  The number of affected rows.
     */
    public function updateObject($table, $row, $where = '')
    {
        return parent::update($table, $this->_sanitizeObjectToArray($row), $where);
    }


    /**
	* Inserts multiple rows using the MySQL Extended Insert syntax.
	*
	* @param string $table The table to insert data into.
	* @param array $rows Array of objects or Array of Arrays with each column-value pairs
	* @return int The number of rows affected
	*/
	public function insertMany($table, array $rows)
	{
        /**
		* Step 1:
		* 	Loop through all rows to
		* 		1.1 Sanitize the rows
		* 			a) quoting all scalar values
		*	 		b) adding a ( ) for instances of \Ninja\Db_Function
		* 			c) unsetting any arrays and instances of other classes
		*
		* 		1.2 Sort array by key(i.e columns)
		*/

		$query_parts = array(); // stores insert values for each row

		$columns_count = NULL; // set to the NUMBER of columns in ascending order from first row
		$columns_names = NULL; // set to the merged NAMES of columns in ascending order from first row


		// Loop through all rows
		foreach( $rows as $index => $row )
		{
            // Convert object row to array
            if (is_scalar($row) || is_null($row))
            {
                throw new \Ninja\Db\Exception("Row must be an object or associative array. Invalid value found at index `$index` while inserting multiple rows.");
            }

            if ( is_object($row) )
            {
                $row = $this->_sanitizeObjectToArray($row);
            }


			// Loop through all columns to sanitize
			foreach($row as $column => $value)
			{
                // If a flat value?
				if( is_scalar($value) )
				{
					$row[$column] = $this->quote($value);
				}
				else if( is_object($value) && ($value instanceof \Zend_Db_Expr) )
				{ // if it's an object that is instance of a \Ninja\Db\Expr
				    $row[$column] = '(' . $value->__toString() . ')';
				}
				else if( is_null($value) )
				{
					$row[$column] = 'NULL';
				}
				else // if array or some other kind of object
				{
					unset($row[$column]);
				}
			}

			ksort($row); // Sort array by columns

			// Merge column names into (`col1`, `col2`, `col3`, ...)  format
			$cur_column_names = '`' . implode('`, `', array_keys($row)) . '`';

			if( $columns_count !== NULL )
			{
				// if column count mismatch?
				if( count($row) !== $columns_count )
				{
					throw new \Ninja\Db\Exception("Unequal number of columns in row index `$index` while inserting multiple rows.");
				}
				else if( $cur_column_names !== $columns_names )
				{
					throw new \Ninja\Db\Exception("Columns mismatch in row index `$index` while inserting multiple rows.");
				}
			}
			else // is first pass?
			{
				$columns_count = count($row); // Store the number of columns in first row
				$columns_names = $cur_column_names; // comma separated list of columns
			}

			// If reached here means the row is safe to add to the insert query
			$query_parts[] = '(' . implode(', ', $row) . ')';
		}

		$query = "INSERT INTO "
               . $this->quoteIdentifier($table, true)
               . "($columns_names) VALUES " . implode(', ', $query_parts);

        return $this->exec($query);
	}

    /**
     * Inserts a row into a table. If a duplicate value in a UNIQUE index
     * or PRIMARY KEY is found, an UPDATE of the old row is performed using
     * the updateRow parameter passed.
     *
     * @throws \Ninja\Db\Exception
     * @param  $table The table to insert data into.
     * @param  $insertRow Insert array/object with column-value pairs.
     * @param  $updateRow Update array/object with column-value pairs.
     * @return int The number of rows affected.
     */
    public function insertOrUpdate($table, $insertRow, $updateRow)
    {
        if ( is_null($insertRow) || is_scalar($insertRow) )
            throw new \Ninja\Db\Exception('Insert Row must be an array or object of column-value association');

        if ( is_null($updateRow) || is_scalar($updateRow) )
            throw new \Ninja\Db\Exception('Update Row must be an array or object of column-value association');

        if ( is_object($insertRow) )
        {
            $insertRow = $this->_sanitizeObjectToArray($insertRow);
        }

        if ( is_object($updateRow) )
        {
            $updateRow = $this->_sanitizeObjectToArray($updateRow);
        }


        /**
		* ------------------ Insert Related Code ------------------------------
		*/
		$insert_row = array(); // associative array holding the columns that can actually be inserted with respective values

		foreach( $insertRow as $column => $value )
		{
			if( is_scalar($value) )
			{
				// If a flat value
				$insert_row[$column] = $this->quote($value);
			}
            else if( is_object($value) && ($value instanceof \Zend_Db_Expr) )
            { // if it's an object that is instance of a \Ninja\Db\Expr
                $insert_row[$column] = '(' . $value->__toString() . ')';
            }
			else if( is_null($value) )
			{
				$insert_row[$column] = 'NULL';
			}
		}

		$insert_columns = '`' . implode('`, `', array_keys($insert_row)) . '`';
		$insert_values  = implode(', ', $insert_row);

		$query = "INSERT INTO `$table` ($insert_columns) VALUES ($insert_values)";

        /**
		* ------------------ On Duplicate Key Related Code ------------------------
		*/
        $duplicate_row = array();
        foreach( $updateRow as $column => $value )
        {
            if( is_scalar($value) )
            {
                // If a flat value like string, number, null
                $duplicate_row[] = "`$column` = " . $this->quote($value);
            }
            else if( is_object($value) && ($value instanceof \Zend_Db_Expr) )
            { // if it's an object that is instance of a \Ninja\Db\Expr
                $duplicate_row[] = "`$column` = (" . $value->__toString() . ')';;
            }
            else if( is_null($value) )
            {
                $duplicate_row[] = "`$column` = NULL";
            }
        }

        $duplicate_row = implode($duplicate_row, ', ');
        $query .= " ON DUPLICATE KEY UPDATE $duplicate_row";

        return $this->exec($query);
    }

    /**
     * Converts an object into array of column-value pairs.
     * Note: Arrays and Objects which are not Expressions are ignored.
     *
     * @param $row
     * @return array
     */
    private function _sanitizeObjectToArray($row)
    {
        //$arrRow = (array)$row;
        $arrRow = array();
        foreach($row as $key => $value)
        {
          $arrRow[$key] = $value;
        }
    
        // Unset all arrays, objects which are not Expressions
        foreach( $arrRow as $column => $value )
		{
			if( is_scalar($value) || is_null($value) )
			    continue;
			else if( is_object($value) && ($value instanceof \Zend_Db_Expr) ) // If object, it must be an expression!
			    continue;
            else
            {
                unset($arrRow[$column]); // Unset this column + value pair as it can't be inserted
			}
		}
        return $arrRow;
    }


    /**
     * Replacement for mysql_real_escape_string
     * Deprecated, use ->quote() instead
     *
     * @deprecated
     * @param $input
     * @return mixed|string
     */
    public function escape($input)
    {
        $quoted = $this->quote($input);
        $len    = strlen($quoted);

        if($len === 0)
            return '';
        else if($quoted[0] === "'" && $quoted[$len - 1] === "'" )
            return substr( $quoted, 1, -1); // Remove the quotes

        return $quoted;
    }

    /**
    * Returns the time in the MySQL DATETIME format
    *   YYYY-MM-DD HH:MM:SS
    *
    * Uses the current time if no timestamp / time string passed.
    *
    * @param string|int $datetime Unix timestamp or String representation of a date, time or combination of both.
    * @return string YYYY-MM-DD HH:MM:SS formatted string
    */
    public function getTime($datetime = NULL)
    {
    	$timestamp = (is_numeric($datetime)) ? $datetime : ( (is_null($datetime)) ? time() : strtotime($datetime));
    	if($timestamp !== false)
			return date('Y-m-d H:i:s',$timestamp);
		return false;
    }


}
