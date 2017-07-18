<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model,
    gplcart\core\Cache,
    gplcart\core\Handler;
use gplcart\core\models\Language as LanguageModel;

/**
 * Manages basic behaviors and data related admin dashboard
 */
class Dashboard extends Model
{

    /**
     * Language model instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * @param LanguageModel $language
     */
    public function __construct(LanguageModel $language)
    {
        parent::__construct();

        $this->language = $language;
    }

    /**
     * Returns an array of dashboard handlers
     * @return array
     */
    public function getHandlers()
    {
        $handlers = Cache::memory(__METHOD__);

        if (isset($handlers)) {
            return $handlers;
        }

        $handlers = require GC_CONFIG_DASHBOARD;
        $this->hook->fire('dashboard.handlers', $handlers, $this);
        return $handlers;
    }

    /**
     * Adds a dashboard record
     * @param array $data
     * @return boolean|integer
     */
    public function add(array $data)
    {
        $this->hook->fire('dashboard.add.before', $data, $this);

        if (empty($data)) {
            return false;
        }

        $data['dashboard_id'] = $this->db->insert('dashboard', $data);
        $this->hook->fire('dashboard.add.after', $data, $this);
        return $data['dashboard_id'];
    }

    /**
     * Updates a dashboard
     * @param integer $dashboard_id
     * @param array $data
     * @return boolean
     */
    public function update($dashboard_id, array $data)
    {
        $this->hook->fire('dashboard.update.before', $dashboard_id, $data, $this);

        if (empty($data)) {
            return false;
        }

        $result = $this->db->update('dashboard', $data, array('dashboard_id' => $dashboard_id));
        $this->hook->fire('dashboard.update.after', $dashboard_id, $data, $result, $this);
        return (bool) $result;
    }

    /**
     * Returns a dashboard record by a user ID
     * @param integer $user_id
     * @param bool $active
     * @return array
     */
    public function getByUser($user_id, $active = true)
    {
        $sql = 'SELECT * FROM dashboard WHERE user_id=?';
        $result = $this->db->fetch($sql, array($user_id), array('unserialize' => 'data'));

        $handlers = $this->getHandlers();

        if (empty($result['data'])) {
            $result['data'] = $handlers;
        } else {
            $result['data'] = array_replace_recursive($handlers, $result['data']);
        }

        foreach ($result['data'] as $handler_id => &$handler) {
            if ($active && empty($handler['status'])) {
                unset($result['data'][$handler_id]);
                continue;
            }

            $handler['title'] = $this->language->text($handler['title']);
            $handler['data'] = Handler::call($handlers, $handler_id, 'data');
        }

        gplcart_array_sort($result['data']);

        $this->hook->fire('dashboard.get.user', $result, $this);
        return $result;
    }

    /**
     * Add/update a dashboard record for a user
     * @param integer $user_id
     * @param array $data
     * @return bool|integer
     */
    public function setByUser($user_id, array $data)
    {
        $existing = $this->getByUser($user_id);

        if (isset($existing['dashboard_id'])) {
            return $this->update($existing['dashboard_id'], array('data' => $data));
        }

        return $this->add(array('user_id' => $user_id, 'data' => $data));
    }

    /**
     * Deletes a dashboard record
     * @param integer $dashboard_id
     * @return boolean
     */
    public function delete($dashboard_id)
    {
        $this->hook->fire('dashboard.delete.before', $dashboard_id, $this);

        if (empty($dashboard_id)) {
            return false;
        }

        $result = $this->db->delete('dashboard', array('dashboard_id'));
        $this->hook->fire('dashboard.delete.after', $dashboard_id, $result, $this);
        return $result;
    }

}