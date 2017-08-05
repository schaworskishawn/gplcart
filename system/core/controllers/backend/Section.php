<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\backend;

use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to admin sections
 */
class Section extends BackendController
{

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Displays the admin section page
     */
    public function listSection($parent)
    {
        $this->controlAccess('admin');

        $this->setTitleListSection($parent);
        $this->setBreadcrumbListSection();

        $this->setDataListSection($parent);
        $this->outputListSection();
    }

    /**
     * Sets template data on the admin section page
     * @param string $parent
     */
    protected function setDataListSection($parent)
    {
        $this->setData('menu', $this->renderAdminMenu($parent, array('template' => 'section/menu')));
    }

    /**
     * Sets titles on the admin section page
     * @param string $parent
     */
    protected function setTitleListSection($parent)
    {
        foreach ($this->route->getList() as $route) {
            if (isset($route['menu']['admin']) && isset($route['arguments']) && in_array($parent, $route['arguments'])) {
                $this->setTitle($route['menu']['admin']);
                break;
            }
        }
    }

    /**
     * Sets breadcrumbs on the admin section page
     */
    protected function setBreadcrumbListSection()
    {
        $this->setBreadcrumbHome();
    }

    /**
     * Render and output the admin section page
     */
    protected function outputListSection()
    {
        $this->output('section/section');
    }

}
