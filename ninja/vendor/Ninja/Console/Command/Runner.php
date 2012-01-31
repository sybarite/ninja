<?php
namespace Ninja\Console\Command;

/**
 * Manages commands and executes the requested command.
 */
class Runner
{

    /**
     * Name of the calling script (the shell file, bat file, etc)
     * @var String
     */
    private $_scriptName;

    /**
     * @param $scriptName
     */
    public function __construct($scriptName)
    {
        $this->_scriptName = $scriptName;
    }

    /**
	 * @return string the entry script name
	 */
	public function getScriptName()
	{
		return $this->_scriptName;
	}

    /**
     * Run a console command.
     * @param string|array  $args
     * @return Runner returns this instance for seamless chaining
     */
    public function run($args)
    {
        // if string representation of command passed, break it into args
        if(is_string($args))
        {
            $args = explode(' ', trim($args));
        }


        // Note: the script name is already removed from the args (it's defined in the constructor instead)

        // Get the command name
		if(isset($args[0]))
		{
			$name=$args[0];
			array_shift($args); // Shift array one up and remove command name
		}
		else
        {
			echo "Please enter a command-name you'd like to execute.\n";
            echo "    ninja <command-name> [parameters...]\n";
            exit();
        }

        $command = $this->_getCommand($name);

        $command->index($args);

        return $this;
    }

    /**
     * @throws \Ninja\Console\Exception
     * @param $name
     * @return \Ninja\Console\Command
     */
    private function _getCommand($name)
    {
        $className = '\\Console\\Command\\' . ucfirst($name);
        $commandFile = \Ninja::$autoLoader->find($className);

        // If expected command file not found?
        if (! is_file($commandFile))
        {
            //throw new \Ninja\Console\Exception("Class file `$className` not found for command `$name` at `$commandFile`");
            echo "Unknown command: $name\n";
            exit(-1);
        }

        $command = new $className($name);

        // If the command class has not implemented the \Ninja\Console\Command parent class?
        if( ! is_subclass_of($command, 'Ninja\Console\Command') )
        {
            throw new \Ninja\Console\Exception("Class `$className` must extend the `\\Ninja\\Console\\Command` class.");
        }

        return $command;
    }
}