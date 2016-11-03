<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace core\controllers;

use core\classes\Tool;
use core\models\Install as ModelsInstall;
use core\controllers\Controller as FrontendController;

/**
 * Handles incoming requests and outputs data related to installation process
 */
class Install extends FrontendController
{

    /**
     * Install model instance
     * @var \core\models\Install $install
     */
    protected $install;

    /**
     * Language selected upon installation
     * @var string
     */
    protected $install_language = '';

    /**
     * Constructor
     * @param ModelsInstall $install
     */
    public function __construct(ModelsInstall $install)
    {
        parent::__construct();

        $this->install = $install;
        $this->install_language = $this->request->get('lang', '');
    }
    
    /**
     * Dispays the installation page
     * @param null|string $installer_id
     */
    public function install($installer_id = null)
    {
        $this->controlAccessInstall();
        $installer = $this->getInstall($installer_id);
        $this->submitInstall($installer);

        $timezones = Tool::timezones();
        $languages = $this->getLanguagesInstall();
        $requirements = $this->getRequirementsInstall();
        $installers = $this->getListInstall();

        $issues = $this->getRequirementErrorsInstall($requirements);
        $severity = $this->getSeverityInstall($issues);

        $this->setData('installer', $installer);
        $this->setData('installers', $installers);
        $this->setData('issues', $issues);
        $this->setData('severity', $severity);
        $this->setData('url_wiki', GC_WIKI_URL);
        $this->setData('timezones', $timezones);
        $this->setData('languages', $languages);
        $this->setData('requirements', $requirements);
        $this->setData('url_licence', $this->url('license.txt'));
        $this->setData('settings.store.timezone', 'Europe/London');
        $this->setData('settings.store.language', $this->install_language);

        $this->setCssInstall();
        $this->setTitleInstall($installer);
        $this->outputInstall();
    }
    
    /**
     * Returns a list of available installers
     * @return array
     */
    protected function getListInstall()
    {
        $list = $this->install->getList();
        
        array_walk($list, function(&$value, $key) {
            $value['url'] = $this->url($value['path'], $this->query);
        });
        
        return $list;
    }
    
    /**
     * Returns an installer
     * @param string $installer_id
     * @return array
     */
    protected function getInstall($installer_id){
        
        if(empty($installer_id)){
            $installer_id = 'default';
        }
        
        $installer = $this->install->get($installer_id);
        
        if(empty($installer['path'])){
            $this->redirect('install');
        }
        
        if($installer['path'] !== $this->path){
            $this->redirect($installer['path']);
        }
        
        return $installer;
    }

    /**
     * Returns an array of ISO languages
     * @return type
     */
    protected function getLanguagesInstall()
    {
        $iso = $this->language->getIso();
        $available = $this->language->getAvailable();

        return array_intersect_key($iso, $available);
    }

    /**
     * Controls access to installer
     */
    protected function controlAccessInstall()
    {
        if ($this->config->exists() && !$this->session->get('install', 'processing')) {
            $this->redirect('/');
        }
    }

    /**
     * Returns an array of system requirements
     * @return array
     */
    protected function getRequirementsInstall()
    {
        return $this->install->getRequirements();
    }

    /**
     * Returns an array of requirement errors
     * @param array $requirements
     * @return array
     */
    protected function getRequirementErrorsInstall(array $requirements)
    {
        return $this->install->getRequirementErrors($requirements);
    }

    /**
     * Returns a string with the current severity
     * @param array $issues
     * @return string
     */
    protected function getSeverityInstall(array $issues)
    {
        if (isset($issues['warning'])) {
            return 'warning';
        }

        if (isset($issues['danger'])) {
            return 'danger';
        }

        return '';
    }
    
    /**
     * Starts installing the system
     * @param array $installer
     * @return null
     */
    protected function submitInstall(array $installer)
    {
        if (!$this->isPosted('install')) {
            return null;
        }

        $this->setSubmitted('settings');
        $this->validateInstall($installer);

        if (!$this->hasErrors('settings')) {
            return $this->processInstall();
        }
        
        return null;
    }

    /**
     * Performs all needed operations to install the system
     */
    protected function processInstall()
    {
        $submitted = $this->getSubmitted();
        $this->processStartInstall($submitted);
        
        $result = $this->install->full($submitted);
        
        if($result !== true){
            $url = $this->url('', $this->query);
            $this->redirect($url, $result, 'danger');
        }
        
        $this->processFinishInstall();
    }
    
    /**
     * Prepares installation
     * @param array $submitted
     */
    protected function processStartInstall(array $submitted)
    {
        ini_set('max_execution_time', 0);
        $this->session->delete('install');
        $this->session->set('install', 'processing', true);
        $this->session->set('install', 'settings', $submitted);
    }

    /**
     * Finishes the installation process
     */
    protected function processFinishInstall()
    {
        $this->session->delete();
        Tool::deleteCookie();

        $message = $this->text('Congratulations! You have successfully installed your store');
        $this->redirect('admin', $message, 'success');
    }
    
    /**
     * Sets titles on the installation page
     * @param array $installer
     */
    protected function setTitleInstall(array $installer)
    {
        $this->setTitle($installer['title']);
    }

    /**
     * Renders installation page
     */
    protected function outputInstall()
    {
        $variables = array(
            'layout' => 'install/layout',
            'region_body' => 'install/body',
            'region_head' => 'install/head'
        );

        $this->output($variables);
    }

    /**
     * Adds CSS on the installation page
     */
    protected function setCssInstall()
    {
        $this->setCss('system/modules/frontend/css/install.css', 99);
    }
    
    /**
     * Validates an array of submitted form values
     * @param array $installer
     * @return array
     */
    protected function validateInstall(array $installer)
    {
        $language = array(
            $this->install_language => $this->language->getIso($this->install_language)
        );

        $this->setSubmitted('store.language', $language);

        $this->addValidator('database.host', array(
            'required' => array()
        ));

        $this->addValidator('database.name', array(
            'required' => array()
        ));

        $this->addValidator('database.user', array(
            'required' => array()
        ));

        $this->addValidator('database.port', array(
            'numeric' => array('required' => true)
        ));

        $this->addValidator('user.password', array(
            'length' => $this->user->getPasswordLength()
        ));

        $this->addValidator('user.email', array(
            'required' => array(),
            'email' => array()
        ));

        $this->addValidator('store.title', array(
            'length' => array('min' => 1, 'max' => 255)
        ));

        $errors = $this->setValidators();

        if (!empty($errors)) {
            return null;
        }

        $db = $this->getSubmitted('database');
        $connect = $this->install->connect($db);

        if ($connect !== true) {
            $this->setError('database.connect', $connect);
            return null;
        }
        
        $this->setSubmitted('store.host', $this->request->host());
        $this->setSubmitted('store.basepath', trim($this->request->base(true), '/'));
    }

}
