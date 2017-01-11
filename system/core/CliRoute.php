<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core;

use gplcart\core\Hook;
use gplcart\core\Handler;
use gplcart\core\helpers\Cli as CliHelper;

/**
 * Routes CLI commands
 */
class CliRoute
{

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * Cli class instance
     * @var \gplcart\core\helpers\Cli $cli
     */
    protected $cli;

    /**
     * The current CLI route data
     * @var array
     */
    protected $route = array();

    /**
     * An array of parsed CLI arguments
     * @var array
     */
    protected $arguments = array();

    /**
     * Constructor
     * @param Cli $cli
     * @param Hook $hook
     */
    public function __construct(CliHelper $cli, Hook $hook)
    {
        $this->cli = $cli;
        $this->hook = $hook;

        $this->init();
    }

    /**
     * Sets parsed arguments
     */
    protected function init()
    {
        $source = GC_CLI_EMULATE ? $_POST['command'] : $_SERVER['argv'];
        $this->arguments = $this->cli->parse($source);
    }

    /**
     * Returns the current route
     * @return array
     */
    public function get()
    {
        return $this->route;
    }

    /**
     * Returns an array of arguments of the current command
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * Returns an array of CLI routes
     */
    public function getList()
    {
        $routes = include GC_CONFIG_CLI_ROUTE;

        $this->hook->fire('cli.route', $routes);
        return $routes;
    }

    /**
     * Processes the current command
     */
    public function process()
    {
        $this->callController();
    }

    /**
     * Finds and calls an appropriate controller for the current command
     * @return mixed
     */
    protected function callController()
    {
        $routes = $this->getList();
        $command = array_shift($this->arguments);

        if (empty($routes[$command])) {
            exit("Unknown command. Use 'help' command to see what you have");
        }

        $routes[$command]['command'] = $command;
        $routes[$command]['arguments'] = $this->arguments;
        $this->route = $routes[$command];

        Handler::call($this->route, null, 'process', array($this->arguments));
        exit('The command was not completed correctly');
    }

}
