<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core;

use InvalidArgumentException;

/**
 * Base parent CLI controller
 */
class CliController
{

    /**
     * CLI helper class instance
     * @var \gplcart\core\helpers\Cli $cli
     */
    protected $cli;

    /**
     * Validator model instance
     * @var \gplcart\core\models\Validator $validator
     */
    protected $validator;

    /**
     * Translation UI model instance
     * @var \gplcart\core\models\Translation $translation
     */
    protected $translation;

    /**
     * Config class instance
     * @var \gplcart\core\Config $config
     */
    protected $config;

    /**
     * CLI router class instance
     * @var \gplcart\core\CliRoute $route
     */
    protected $route;

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * The current CLI command
     * @var string
     */
    protected $command;

    /**
     * The current CLI command parameters
     * @var array
     */
    protected $params = array();

    /**
     * An array of mapped data ready for validation
     * @var array
     */
    protected $submitted = array();

    /**
     * An array of errors to output to the user
     * @var array
     */
    protected $errors = array();

    /**
     * An array of the current CLI route data
     * @var array
     */
    protected $current_route = array();

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setInstanceProperties();
        $this->setRouteProperties();

        $this->hook->attach('construct.cli.controller', $this);
        $this->outputHelp();
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->hook->attach('destruct.cli.controller', $this);
    }

    /**
     * Returns a property
     * @param string $name
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function getProperty($name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }

        throw new InvalidArgumentException("Property $name does not exist");
    }

    /**
     * Set a property
     * @param string $property
     * @param object $value
     */
    public function setProperty($property, $value)
    {
        $this->{$property} = $value;
    }

    /**
     * Returns an object instance
     * @param string $class
     * @return object
     */
    public function getInstance($class)
    {
        return Container::get($class);
    }

    /**
     * Sets class instance properties
     */
    protected function setInstanceProperties()
    {
        $this->hook = $this->getInstance('gplcart\\core\\Hook');
        $this->config = $this->getInstance('gplcart\\core\\Config');
        $this->route = $this->getInstance('gplcart\\core\\CliRoute');
        $this->cli = $this->getInstance('gplcart\\core\\helpers\Cli');
        $this->translation = $this->getInstance('gplcart\\core\\models\\Translation');
        $this->validator = $this->getInstance('gplcart\\core\\models\\Validator');
    }

    /**
     * Sets route properties
     */
    protected function setRouteProperties()
    {
        $this->current_route = $this->route->get();
        $this->command = $this->current_route['command'];
        $this->params = gplcart_array_trim($this->current_route['params'], true);
    }

    /**
     * Returns a translated string
     * @param string $text
     * @param array $arguments
     * @return string
     */
    public function text($text, array $arguments = array())
    {
        return $this->translation->text($text, $arguments);
    }

    /**
     * Sets an array of submitted mapped data
     * @param array $map
     * @param null|array $params
     * @param array $default
     * @return array
     */
    public function setSubmittedMapped(array $map, $params = null, array $default = array())
    {
        $mapped = $this->mapParams($map, $params);
        $merged = gplcart_array_merge($default, $mapped);

        return $this->setSubmitted(null, $merged);
    }

    /**
     * Sets a submitted data
     * @param null|string $key
     * @param mixed $data
     * @return array
     */
    public function setSubmitted($key, $data)
    {
        if (isset($key)) {
            gplcart_array_set($this->submitted, $key, $data);
            return $this->submitted;
        }

        return $this->submitted = (array) $data;
    }

    /**
     * Returns a submitted value
     * @param string|array $key
     * @param mixed $default
     * @return mixed
     */
    public function getSubmitted($key = null, $default = null)
    {
        if (isset($key)) {
            $value = gplcart_array_get($this->submitted, $key);
            return isset($value) ? $value : $default;
        }

        return $this->submitted;
    }

    /**
     * Returns a single parameter value
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    public function getParam($key = null, $default = null)
    {
        if (!isset($key)) {
            return $this->params;
        }

        foreach ((array) $key as $k) {
            if (isset($this->params[$k])) {
                return $this->params[$k];
            }
        }

        return $default;
    }

    /**
     * Returns the current CLI command
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * Whether a error exists
     * @param null|string $key
     * @return boolean
     */
    public function isError($key = null)
    {
        $value = $this->getError($key);
        return is_array($value) ? !empty($value) : isset($value);
    }

    /**
     * Whether a submitted key is not empty
     * @param string $key
     * @return boolean
     */
    public function isSubmitted($key)
    {
        return (bool) $this->getSubmitted($key);
    }

    /**
     * Formats a local time/date
     * @param integer $timestamp
     * @param bool $full
     * @return string
     */
    public function date($timestamp, $full = true)
    {
        if ($full) {
            $format = $this->config->get('date_full_format', 'd.m.Y H:i');
        } else {
            $format = $this->config->get('date_short_format', 'd.m.y');
        }

        return date($format, $timestamp);
    }

    /**
     * Sets an error
     * @param null|string $key
     * @param mixed $error
     * @return array
     */
    public function setError($key, $error)
    {
        if (isset($key)) {
            gplcart_array_set($this->errors, $key, $error);
            return $this->errors;
        }

        return $this->errors = (array) $error;
    }

    /**
     * Returns a single error or an array of all defined errors
     * @param null|string $key
     * @return mixed
     */
    public function getError($key = null)
    {
        if (isset($key)) {
            return gplcart_array_get($this->errors, $key);
        }

        return $this->errors;
    }

    /**
     * Output an error message and stop the script execution
     * @param string $text
     * @param bool $exit
     */
    public function errorLine($text, $exit = true)
    {
        $this->error($text)->line();

        if ($exit) {
            $this->abort(1);
        }
    }

    /**
     * Output and clear up all existing errors
     * @param boolean $exit_on_error
     */
    public function outputErrors($exit_on_error = false)
    {
        if (!empty($this->errors)) {

            foreach (gplcart_array_flatten($this->errors) as $error) {
                $this->errorLine($error, false);
            }

            $this->errors = array();

            if ($exit_on_error) {
                $this->abort(1);
            }
        }
    }

    /**
     * Output all to the user and stop the script execution
     */
    public function output()
    {
        $this->outputErrors(true);
        $this->abort();
    }

    /**
     * Map the command line parameters to an array of submitted data to be passed to validators
     * @param array $map
     * @param null|array $params
     * @return array
     */
    public function mapParams(array $map, $params = null)
    {
        if (!isset($params)) {
            $params = $this->params;
        }

        $mapped = array();
        foreach ($params as $key => $value) {
            if (isset($map[$key]) && is_string($map[$key])) {
                gplcart_array_set($mapped, $map[$key], $value);
            }
        }

        return $mapped;
    }

    /**
     * Validates a submitted data
     * @param string $handler_id
     * @param array $options
     * @return mixed
     */
    public function validateComponent($handler_id, array $options = array())
    {
        $result = $this->validator->run($handler_id, $this->submitted, $options);

        if ($result === true) {
            return true;
        }

        $this->setError(null, $result);
        return $result;
    }

    /**
     * Whether the user input passed the field validation
     * @param string $input
     * @param string $field
     * @param string $handler_id
     * @return bool
     */
    public function isValidInput($input, $field, $handler_id)
    {
        $this->setSubmitted($field, $input);
        return $this->validateComponent($handler_id, array('field' => $field)) === true;
    }

    /**
     * Output help for a certain command or the current command if a help option is presented
     * @param string|null $command
     */
    public function outputHelp($command = null)
    {
        $help_options = $this->config->get('cli_help_option', array('h', 'help'));

        if (!isset($command) && !$this->getParam($help_options, false)) {
            return null;
        }

        if (!isset($command)) {
            $command = $this->command;
        }

        $routes = $this->route->getList();

        $shown = false;
        if (!empty($routes[$command]['description'])) {
            $shown = true;
            $this->line($this->text($routes[$command]['description']));
        }

        if (!empty($routes[$command]['usage'])) {
            $shown = true;
            $this->line()->line($this->text('Usage:'));
            foreach ($routes[$command]['usage'] as $usage) {
                $this->line($usage);
            }
        }

        if (!empty($routes[$command]['options'])) {
            $shown = true;
            $this->line()->line($this->text('Options:'));
            foreach ($routes[$command]['options'] as $name => $description) {
                $vars = array('@option' => $name, '@description' => $this->text($description));
                $this->line($this->text('  @option  @description', $vars));
            }
        }

        if (!$shown) {
            $this->line($this->text('No help found for the command'));
        }

        $this->output();
    }

    /**
     * Output an error message
     * @param string $text
     * @return $this
     */
    public function error($text)
    {
        $this->cli->error($text);
        return $this;
    }

    /**
     * Output inline text
     * @param string $text
     * @return $this
     */
    public function out($text)
    {
        $this->cli->out($text);
        return $this;
    }

    /**
     * Output a text line
     * @param string $text
     * @return $this
     */
    public function line($text = '')
    {
        $this->cli->line($text);
        return $this;
    }

    /**
     * Output an input prompt
     * @param string $question
     * @param string $default
     * @param string $marker
     * @return mixed
     */
    public function prompt($question, $default = '', $marker = ': ')
    {
        return $this->cli->prompt($question, $default, $marker);
    }

    /**
     * Presents a user with a multiple choice questions
     * @param string $question
     * @param string $choice
     * @param string $default
     * @return string
     */
    public function choose($question, $choice = 'yn', $default = 'n')
    {
        return $this->cli->choose($question, $choice, $default);
    }

    /**
     * Displays a menu where a user can enter a number to choose an option
     * @param array $items
     * @param mixed $default
     * @param string $title
     * @return mixed
     */
    public function menu(array $items, $default = null, $title = '')
    {
        return $this->cli->menu($items, $default, $title);
    }

    /**
     * Terminate the current script with an optional code or message
     * @param integer|string $code
     */
    public function abort($code = 0)
    {
        exit($code);
    }

    /**
     * Read the user input
     * @param string $format
     * @return string
     */
    public function in($format = '')
    {
        return $this->cli->in($format);
    }

    /**
     * Output simple table
     * @param array $data
     * @return $this
     */
    public function table(array $data)
    {
        $this->cli->table($data);
        return $this;
    }

}
