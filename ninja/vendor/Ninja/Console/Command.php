<?php
namespace Ninja\Console;

/**
 * Inherited class for all console commands.
 */
abstract class Command
{
    /**
     * Help string for this command as a whole.
     * @var string
     */
    protected $_help;

    /**
     * Subcommand configurations
     * @var array
     */
    protected $_subCommands = array();

    /**
     * Name of this command
     * @var string
     */
    private $_name;

    public function __construct($name)
    {
        $this->_name = $name;
    }

    /**
     * Called before a command action is executed
     * @return void
     */
    public function _before()
    {
    }

    /**
     * Called after a command action is executed
     * @return void
     */
    public function _after()
    {
    }

    public function index($args)
    {
        if (count($args) === 0)
        {
            echo "Type '{$this->_name} help' for usage.\n";
            exit(-1);
        }

        $methodName = $args[0];
        array_shift($args); // shift one up

        if (!method_exists($this, $methodName))
        {
            echo "Unknown command: $methodName\n";
            echo "Type '{$this->_name} help' for usage.\n";
            exit(-1);
        }

        $rules = isset($this->_subCommands[$methodName]['rules']) ? $this->_subCommands[$methodName]['rules'] : array();

        $opts = $this->_parse($rules, $args, $methodName);

        $this->_before();
        $this->$methodName($opts);
        $this->_after();
    }

    /**
     * 
     *
     * @throws \Zend_Console_Getopt_Exception
     * @param array $rules Rules for arguments passed to this command
     * @param array $args Arguments for this command
     * @param string $subCommandName Calling program/command name to show in help message
     * @return null|\Zend_Console_Getopt
     */
    protected function _parse(array $rules, array $args, $subCommandName = '')
    {
        try
        {
            $opts = new \Ninja\Console\Getopt($rules, $args);

            $opts->setProgName($this->_name . ' ' . $subCommandName);

            // If rules defined but NO arguments passed
            if( (count($rules) > 0) && count($args) === 0 )
            {
                $errMsg = "Not enough arguments provided.";
                throw new \Zend_Console_Getopt_Exception($errMsg, $opts->getUsageMessage());
            }

            // Parse the options and return the opt object
            return $opts->parse();
        }
        catch(\Zend_Console_Getopt_Exception $e)
        {
            echo $e->getMessage() . "\n";
            echo "Try '{$this->_name} help $subCommandName' for more info.\n";

            exit(-1);
        }
    }

    /**
     * @param \Ninja\Console\Getopt $opts
     * @return void
     */
    protected function help($opts)
    {
        $remainingArgs = $opts->getRemainingArgs();

        $subcommandsList = $this->_getSubcommands();

        // Whether sub-command help requested?
        if (count($remainingArgs) === 1)
        {
            $subcommand = $remainingArgs[0];

            // if subcommand does not exist?
            if ( array_search($subcommand, $subcommandsList) === FALSE )
            {
                echo "Subcommand not found: $subcommand";
                exit(-1);
            }

            echo "$subcommand: ";

            if (isset($this->_subCommands[$subcommand]['help']))
            {
                echo $this->_subCommands[$subcommand]['help'];
            }

            echo "\n\n";

            $subcommandRules = isset($this->_subCommands[$subcommand]['rules']) ? $this->_subCommands[$subcommand]['rules'] : array(); 

            $opt = new \Ninja\Console\Getopt($subcommandRules);
            $opt->setProgName($this->_name . ' ' . $subcommand);

            echo $opt->getUsageMessage();

            return;
        }

        // Else display Full help with list of sub-commands

        $help = <<<EOD
{$this->_help}

usage: {$this->_name} <subcommand> [options] [args]
Type '{$this->_name} help <subcommand>' for help on a specific subcommand.

Available subcommands:
   
EOD;
        echo $help;
        echo implode("\n   ", $subcommandsList);
        echo "\n";


        exit(-1);
    }

    /**
     * Returns a list of all subcommands
     * @return array
     */
    private function _getSubcommands()
    {
        $subcommands = array();

        $class = new \ReflectionClass(get_class($this));
        foreach ($class->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
        {
        	$name = $method->getName();
            if ($name[0] !== '_' && $name !== 'index')
            {
                $subcommands[] = $name;
            }
        }

        return $subcommands;
    }
}