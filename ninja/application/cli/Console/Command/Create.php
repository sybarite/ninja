<?php
namespace Console\Command;

class Create extends \Ninja\Console\Command
{
    protected $_help = "A few lines describing what this command is all about.";

    protected $_subCommands = array(
                                // file subcommand
                                'file' => array(
                                        'rules' => array(
                                            'user|u=i' => 'User id.',
                                            'password|p=s' => 'Password for user',
                                            'l' => 'long mode'
                                        ),
                                    ),

                                // folder subcommand
                                'folder' => array(
                                        'help' => 'Create a directory',
                                    ),
                                );


    /**
     * Override to take control of just the 'command' being called.
     * Else, it is broken into a sub-command, and an action is called.
     *
     * @param array $args
     * @return void
     */
//    public function index($args)
//    {
//
//    }

    

    /**
     * @param \Ninja\Console\Getopt $opts
     * @return void
     */
    public function file($opts)
    {
        //echo $opts->getUsageMessage();
        var_dump($opts->getOption('u'));
        var_dump($opts->getOption('p'));
        var_dump($opts->getOptions());
        var_dump($opts->getRemainingArgs());
    }

    public function folder()
    {
        echo "Simple subcommand folder called...";
    }
}