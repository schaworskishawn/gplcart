<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\backend;

use gplcart\core\models\Alias as AliasModel;
use gplcart\core\controllers\backend\Controller as BackendController;

/**
 * Handles incoming requests and outputs data related to the URL aliases
 */
class Alias extends BackendController
{

    /**
     * URL model instance
     * @var \gplcart\core\models\Alias $alias
     */
    protected $alias;

    /**
     * Pager limit
     * @var array
     */
    protected $data_limit;

    /**
     * @param AliasModel $alias
     */
    public function __construct(AliasModel $alias)
    {
        parent::__construct();

        $this->alias = $alias;
    }

    /**
     * Displays the alias overview page
     */
    public function listAlias()
    {
        $this->actionListAlias();

        $this->setTitleListAlias();
        $this->setBreadcrumbListAlias();

        $this->setFilterListAlias();
        $this->setPagerListAlias();

        $this->setData('id_keys', $this->alias->getIdKeys());
        $this->setData('aliases', $this->getListAlias());
        $this->outputListAlias();
    }

    /**
     * Sets the current filter parameters
     */
    protected function setFilterListAlias()
    {
        $allowed = array('id_value', 'id_key', 'alias', 'alias_id');
        $this->setFilter($allowed);
    }

    /**
     * Applies an action to the selected aliases
     */
    protected function actionListAlias()
    {
        list($selected, $action) = $this->getPostedAction();

        if (!empty($action)) {

            $deleted = 0;
            foreach ($selected as $id) {
                if ($action === 'delete' && $this->access('alias_delete')) {
                    $deleted += (int) $this->alias->delete($id);
                }
            }

            if ($deleted > 0) {
                $message = $this->text('Deleted %num item(s)', array('%num' => $deleted));
                $this->setMessage($message, 'success');
            }
        }
    }

    /**
     * Sets pager
     * @return array
     */
    protected function setPagerListAlias()
    {
        $options = $this->query_filter;
        $options['count'] = true;
        $total = (int) $this->alias->getList($options);

        $pager = array('total' => $total, 'query' => $this->query_filter);
        return $this->data_limit = $this->setPager($pager);
    }

    /**
     * Returns an array of aliases
     * @return array
     */
    protected function getListAlias()
    {
        $options = $this->query_filter;
        $options['limit'] = $this->data_limit;
        $aliases = (array) $this->alias->getList($options);
        return $this->prepareListAlias($aliases);
    }

    /**
     * Prepare an array of aliases
     * @param array $aliases
     * @return array
     */
    protected function prepareListAlias(array $aliases)
    {
        foreach ($aliases as &$alias) {
            $entity = preg_replace('/_id$/', '', $alias['id_key']);
            $alias['entity'] = $this->text(ucfirst($entity));
        }

        return $aliases;
    }

    /**
     * Sets titles on the aliases overview page
     */
    protected function setTitleListAlias()
    {
        $this->setTitle($this->text('Aliases'));
    }

    /**
     * Sets breadcrumbs on the aliases overview page
     */
    protected function setBreadcrumbListAlias()
    {
        $this->setBreadcrumbHome();
    }

    /**
     * Render and output the alias overview page
     */
    protected function outputListAlias()
    {
        $this->output('content/alias/list');
    }

}
