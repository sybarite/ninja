<?php
namespace Ninja\Db;

/**
 * This class simply holds a string that is treated as an SQL expression / function
 * and is not quoted during SELECTS, INSERTS or UPDATES.
 */
class Expr extends \Zend_Db_Expr
{
    
}