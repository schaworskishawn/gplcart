<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace core\controllers\admin;

use core\models\Language as ModelsLanguage;
use core\controllers\admin\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to languages
 */
class Language extends BackendController
{

    /**
     * Language model instance
     * @var \core\models\Language $language
     */
    protected $language;

    /**
     * Constructor
     * @param ModelsLanguage $language
     */
    public function __construct(ModelsLanguage $language)
    {
        parent::__construct();

        $this->language = $language;
    }

    /**
     * Displays the language edit form
     * @param string|null $code
     */
    public function editLanguage($code = null)
    {
        $language = $this->getLanguage($code);
        $default = $this->language->getDefault();

        $this->setData('language', $language);
        $this->setData('default_language', $default);

        $this->submitLanguage($language);

        $this->setTitleEditLanguage($language);
        $this->setBreadcrumbEditLanguage();
        $this->outputEditLanguage();
    }

    /**
     * Displays the language overview page
     */
    public function listLanguage()
    {
        $this->refreshLanguage();

        $languages = $this->language->getList();
        $this->setData('languages', $languages);

        $this->setTitleListLanguage();
        $this->setBreadcrumbListLanguage();
        $this->outputListLanguage();
    }

    /**
     * Removes cached translations for the given language
     */
    protected function refreshLanguage()
    {
        $code = (string) $this->request->get('refresh');

        if (!empty($code)) {
            $this->language->refresh($code);
            $this->redirect();
        }
    }

    /**
     * Sets titles on the edit language page
     * @param array $language
     */
    protected function setTitleEditLanguage(array $language)
    {
        if (isset($language['code'])) {
            $title = $this->text('Edit language %name', array(
                '%name' => $language['native_name']));
        } else {
            $title = $this->text('Add language');
        }

        $this->setTitle($title);
    }

    /**
     * Sets breadcrumbs on the edit language page
     */
    protected function setBreadcrumbEditLanguage()
    {
        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard'));

        $breadcrumbs[] = array(
            'url' => $this->url('admin/settings/language'),
            'text' => $this->text('Languages'));

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Renders the edit language page templates
     */
    protected function outputEditLanguage()
    {
        $this->output('settings/language/edit');
    }

    /**
     * Saves a submitted language
     * @param array $language
     */
    protected function submitLanguage(array $language)
    {
        if ($this->isPosted('delete')) {
            return $this->deleteLanguage($language);
        }

        if (!$this->isPosted('save')) {
            return;
        }

        $this->setSubmitted('language');
        $this->validateLanguage($language);

        if ($this->hasErrors('language')) {
            return;
        }

        if (isset($language['code'])) {
            return $this->updateLanguage($language);
        }

        $this->addLanguage();
    }

    /**
     * Adds a new language
     */
    protected function addLanguage()
    {
        $this->controlAccess('language_add');

        $values = $this->getSubmitted();
        $this->language->add($values);

        $message = $this->text('Language has been added');
        $this->redirect('admin/settings/language', $message, 'success');
    }

    /**
     * Updates a language
     * @param array $language
     */
    protected function updateLanguage(array $language)
    {
        $this->controlAccess('language_edit');

        $submitted = $this->getSubmitted();
        $this->language->update($language['code'], $submitted);

        $message = $this->text('Language has been updated');
        $this->redirect('admin/settings/language', $message, 'success');
    }

    /**
     * Deletes a language
     * @param array $language
     * @return null
     */
    protected function deleteLanguage(array $language)
    {
        $this->controlAccess('language_delete');

        $deleted = $this->language->delete($language['code']);

        if ($deleted) {
            $message = $this->text('Language has been deleted');
            $this->redirect('admin/settings/language', $message, 'success');
        }

        $message = $this->text('Unable to delete this language.'
                . ' The most probable reason - it is default language or blocked by a module');

        $this->redirect('', $message, 'danger');
    }

    /**
     * Sets titles on the language overview page
     */
    protected function setTitleListLanguage()
    {
        $this->setTitle($this->text('Languages'));
    }

    /**
     * Sets breadcrumbs on the language overview page
     */
    protected function setBreadcrumbListLanguage()
    {
        $breadcrumbs[] = array(
            'url' => $this->url('admin'),
            'text' => $this->text('Dashboard'));

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Renders the language overview page templates
     */
    protected function outputListLanguage()
    {
        $this->output('settings/language/list');
    }

    /**
     * Returns a language
     * @param string $code
     * @return array
     */
    protected function getLanguage($code)
    {
        if (empty($code)) {
            return array();
        }

        $language = $this->language->get($code);

        if (empty($language)) {
            $this->outputError(404);
        }

        return $language;
    }

    /**
     * Validates a language
     * @param array $language
     */
    protected function validateLanguage(array $language)
    {

        $this->addValidator('code', array(
            'regexp' => array('pattern' => '/^[a-z]{2}$/', 'required' => true)
        ));

        $this->addValidator('name', array(
            'regexp' => array('pattern' => '/^[A-Za-z]{1,50}$/')
        ));

        $this->addValidator('native_name', array(
            'length' => array('max' => 50)
        ));

        $this->addValidator('weight', array(
            'numeric' => array(),
            'length' => array('max' => 2)
        ));

        $this->setValidators($language);
    }

}
