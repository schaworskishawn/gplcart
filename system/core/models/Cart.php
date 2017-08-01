<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model,
    gplcart\core\Cache;
use gplcart\core\models\Sku as SkuModel,
    gplcart\core\models\User as UserModel,
    gplcart\core\models\Product as ProductModel,
    gplcart\core\models\Currency as CurrencyModel,
    gplcart\core\models\Language as LanguageModel,
    gplcart\core\models\Wishlist as WishlistModel;
use gplcart\core\helpers\Request as RequestHelper;

/**
 * Manages basic behaviors and data related to user carts
 */
class Cart extends Model
{

    /**
     * Product model instance
     * @var \gplcart\core\models\Product $product
     */
    protected $product;

    /**
     * Sku model instance
     * @var \gplcart\core\models\Sku $sku
     */
    protected $sku;

    /**
     * Currency model instance
     * @var \gplcart\core\models\Currency $currency
     */
    protected $currency;

    /**
     * User model instance
     * @var \gplcart\core\models\User $user
     */
    protected $user;

    /**
     * Wishlist model instance
     * @var \gplcart\core\models\Wishlist $wishlist
     */
    protected $wishlist;

    /**
     * Language model instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * Request model instance
     * @var \gplcart\core\helpers\Request $request
     */
    protected $request;

    /**
     * @param ProductModel $product
     * @param SkuModel $sku
     * @param CurrencyModel $currency
     * @param UserModel $user
     * @param WishlistModel $wishlist
     * @param LanguageModel $language
     * @param RequestHelper $request
     */
    public function __construct(ProductModel $product, SkuModel $sku,
            CurrencyModel $currency, UserModel $user, WishlistModel $wishlist,
            LanguageModel $language, RequestHelper $request)
    {
        parent::__construct();

        $this->sku = $sku;
        $this->user = $user;
        $this->product = $product;
        $this->request = $request;
        $this->currency = $currency;
        $this->wishlist = $wishlist;
        $this->language = $language;
    }

    /**
     * Returns a cart content for a given user ID
     * @param array $data
     * @return array
     */
    public function getContent(array $data)
    {
        $result = &Cache::memory(array(__METHOD__ => $data));

        if (isset($result)) {
            return $result;
        }

        $this->hook->fire('cart.get.content.before', $data, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $data['order_id'] = 0;

        $items = $this->getList($data);

        if (empty($items)) {
            return array();
        }

        $result = array(
            'store_id' => $data['store_id'],
            'currency' => (string) $this->currency->get()
        );

        $total = 0;
        $quantity = 0;
        foreach ((array) $items as $sku => $item) {

            $prepared = $this->prepareItem($item, $result);

            if (empty($prepared)) {
                continue;
            }

            $result['items'][$sku] = $prepared;
            $total += (int) $prepared['total'];
            $quantity += (int) $prepared['quantity'];
        }

        $result['total'] = $total;
        $result['quantity'] = $quantity;

        $this->hook->fire('cart.get.content.after', $data, $result, $this);
        return $result;
    }

    /**
     * Prepare a cart item
     * @param array $item
     * @param array $data
     * @return array
     */
    protected function prepareItem(array $item, array $data)
    {
        $product = $this->product->getBySku($item['sku'], $item['store_id']);

        if (empty($product['status']) || $data['store_id'] != $product['store_id']) {
            return array();
        }

        $product['price'] = $this->currency->convert($product['price'], $product['currency'], $data['currency']);
        $calculated = $this->product->calculate($product);

        if ($calculated['total'] != $product['price']) {
            $item['original_price'] = $product['price'];
        }

        $item['product'] = $product;
        $item['price'] = $calculated['total'];
        $item['total'] = $item['price'] * $item['quantity'];

        return $item;
    }

    /**
     * Returns an array of cart items or counts them
     * @param array $data
     * @param string $index
     * @return array|integer
     */
    public function getList(array $data = array(), $index = 'sku')
    {
        $sql = 'SELECT c.*, COALESCE(NULLIF(pt.title, ""), p.title) AS title,'
                . ' p.status AS product_status, p.store_id AS product_store_id,'
                . ' u.email AS user_email';

        if (!empty($data['count'])) {
            $sql = 'SELECT COUNT(c.cart_id)';
        }

        $sql .= ' FROM cart c'
                . ' LEFT JOIN product p ON(c.product_id=p.product_id)'
                . ' LEFT JOIN product_translation pt ON(c.product_id = pt.product_id AND pt.language=?)'
                . ' LEFT JOIN user u ON(c.user_id = u.user_id)'
                . ' WHERE cart_id > 0';

        $where = array($this->language->current());

        if (isset($data['user_id'])) {
            $sql .= ' AND c.user_id=?';
            $where[] = $data['user_id'];
        }

        if (isset($data['order_id'])) {
            $sql .= ' AND c.order_id=?';
            $where[] = (int) $data['order_id'];
        }

        if (isset($data['store_id'])) {
            $sql .= ' AND c.store_id=?';
            $where[] = (int) $data['store_id'];
        }

        if (isset($data['user_email'])) {
            $sql .= ' AND u.email LIKE ?';
            $where[] = "%{$data['user_email']}%";
        }

        if (isset($data['sku'])) {
            $sql .= ' AND c.sku LIKE ?';
            $where[] = "%{$data['sku']}%";
        }

        $allowed_order = array('asc', 'desc');

        $allowed_sort = array(
            'sku' => 'c.sku',
            'created' => 'c.created',
            'user_id' => 'c.user_id',
            'user_email' => 'u.email',
            'store_id' => 'c.store_id',
            'order_id' => 'c.order_id',
            'quantity' => 'c.quantity',
            'product_id' => 'c.product_id'
        );

        if (isset($data['sort']) && isset($allowed_sort[$data['sort']])//
                && isset($data['order']) && in_array($data['order'], $allowed_order)) {
            $sql .= " ORDER BY {$allowed_sort[$data['sort']]} {$data['order']}";
        } else {
            $sql .= ' ORDER BY c.modified DESC';
        }

        if (!empty($data['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $data['limit']));
        }

        if (!empty($data['count'])) {
            return (int) $this->db->fetchColumn($sql, $where);
        }

        $options = array('unserialize' => 'data', 'index' => $index);
        $list = $this->db->fetchAll($sql, $where, $options);

        $this->hook->fire('cart.list', $list, $this);
        return $list;
    }

    /**
     * Returns the cart limits
     * @param null|string $item
     * @return array|integer
     */
    public function getLimits($item = null)
    {
        $limits = array(
            'sku' => (int) $this->config->get('cart_sku_limit', 10),
            'item' => (int) $this->config->get('cart_item_limit', 20)
        );

        return isset($item) ? $limits[$item] : $limits;
    }

    /**
     * Adds a product to the cart
     * @param array $product
     * @param array $data
     * @return array
     */
    public function addProduct(array $product, array $data)
    {
        $result = array();
        $this->hook->fire('cart.add.product.before', $product, $data, $result, $this);

        if (!empty($result)) {
            return (array) $result;
        }

        $result = array(
            'redirect' => '',
            'severity' => 'warning',
            'message' => $this->language->text('Unable to add this product')
        );

        $data += array(
            'quantity' => 1,
            'user_id' => $this->getUid(),
            'store_id' => $product['store_id'],
            'product_id' => $product['product_id']
        );

        $cart_id = $this->setProduct($data);

        if (!empty($cart_id)) {

            $options = array(
                'user_id' => $data['user_id'],
                'store_id' => $data['store_id']
            );

            $existing = $this->getQuantity($options);
            $vars = array('!href' => $this->request->base() . 'checkout');

            $result = array(
                'redirect' => '',
                'cart_id' => $cart_id,
                'severity' => 'success',
                'quantity' => $existing['total'],
                'message' => $this->language->text('Product has been added to your cart. <a href="!href">Checkout</a>', $vars)
            );
        }

        $this->hook->fire('cart.add.product.after', $product, $data, $result, $this);
        return (array) $result;
    }

    /**
     * Returns a cart user ID
     * @return string
     */
    public function getUid()
    {
        $session_user_id = $this->user->getId();

        if (!empty($session_user_id)) {
            return (string) $session_user_id;
        }

        $cookie_name = $this->config->get('user_cookie_name', 'user_id');
        $cookie_user_id = $this->request->cookie($cookie_name, '', 'string');

        if (!empty($cookie_user_id)) {
            return $cookie_user_id;
        }

        $user_id = '_' . gplcart_string_random(6); // Add prefix to prevent from being "numeric"
        $this->request->setCookie($cookie_name, $user_id, $this->config->get('cart_cookie_lifespan', 31536000));
        return $user_id;
    }

    /**
     * Adds/updates products in the cart
     * @param array $data
     * @return integer|boolean
     */
    protected function setProduct(array $data)
    {
        $sql = 'SELECT cart_id, quantity'
                . ' FROM cart'
                . ' WHERE sku=? AND user_id=? AND store_id=? AND order_id=?';

        $conditions = array($data['sku'], $data['user_id'], $data['store_id'], 0);
        $existing = $this->db->fetch($sql, $conditions);

        if (empty($existing['cart_id'])) {
            return $this->add($data);
        }

        $existing['quantity'] += $data['quantity'];

        $conditions2 = array('quantity' => $existing['quantity']);
        $this->update($existing['cart_id'], $conditions2);

        return $existing['cart_id'];
    }

    /**
     * Adds a cart record to the database
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->fire('cart.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $data['created'] = $data['modified'] = GC_TIME;
        $result = $this->db->insert('cart', $data);

        Cache::clearMemory();

        $this->hook->fire('cart.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Updates a cart
     * @param integer $cart_id
     * @param array $data
     * @return boolean
     */
    public function update($cart_id, array $data)
    {
        $result = null;
        $this->hook->fire('cart.update.before', $cart_id, $data, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $data['modified'] = GC_TIME;
        $result = (bool) $this->db->update('cart', $data, array('cart_id' => $cart_id));

        Cache::clearMemory();

        $this->hook->fire('cart.update.after', $cart_id, $data, $result, $this);
        return (bool) $result;
    }

    /**
     * Returns an array containing a total number of products
     * and a number of products per SKU for the given user and store
     * @param array $conditions
     * @param null|string $key
     * @return array|integer
     */
    public function getQuantity(array $conditions, $key = null)
    {
        $conditions += array('order_id' => 0);

        $items = $this->getList($conditions);
        $result = array('total' => 0, 'sku' => array());

        foreach ((array) $items as $item) {
            $result['total'] += (int) $item['quantity'];
            $result['sku'][$item['sku']] = (int) $item['quantity'];
        }

        if (isset($key)) {
            return $result[$key];
        }

        return $result;
    }

    /**
     * Loads a cart from the database
     * @param integer $cart_id
     * @return array
     */
    public function get($cart_id)
    {
        $result = null;
        $this->hook->fire('cart.get.before', $cart_id, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $result = $this->db->fetch('SELECT * FROM cart WHERE cart_id=?', array($cart_id));

        $this->hook->fire('cart.get.after', $cart_id, $result, $this);
        return $result;
    }

    /**
     * Moves a cart item to the wishlist
     * @param array $data
     * @return array
     */
    public function moveToWishlist(array $data)
    {
        $result = array();
        $this->hook->fire('cart.move.wishlist.before', $data, $result, $this);

        if (!empty($result)) {
            return (array) $result;
        }

        $skuinfo = $this->sku->get($data['sku'], $data['store_id']);

        if (empty($skuinfo['product_id'])) {
            return array('redirect' => null, 'severity' => '', 'message' => '');
        }

        $this->db->delete('cart', $data);

        $data['product_id'] = $skuinfo['product_id'];

        $conditions = $data;
        unset($conditions['sku']);
        $this->db->delete('wishlist', $conditions);

        $data['wishlist_id'] = $this->wishlist->addProduct($data);

        Cache::clearMemory();

        $url = $this->request->base() . 'wishlist';
        $message = $this->language->text('Product has been moved to your <a href="!href">wishlist</a>', array('!href' => $url));

        $result = array(
            'redirect' => '',
            'message' => $message,
            'severity' => 'success',
            'wishlist_id' => $data['wishlist_id']
        );

        $this->hook->fire('cart.move.wishlist.after', $data, $result, $this);
        return (array) $result;
    }

    /**
     * Performs all needed tastks when customer logged in during checkout
     * @param array $user
     * @param array $cart
     * @return array
     */
    public function login(array $user, array $cart)
    {
        $result = array();
        $this->hook->fire('cart.login.before', $user, $cart, $result, $this);

        if (!empty($result)) {
            return (array) $result;
        }

        if (!$this->config->get('cart_login_merge', 0)) {
            $items = $this->getList(array('user_id' => $user['user_id'], 'order_id' => 0));
            foreach ((array) $items as $item) {
                $this->delete($item['cart_id']);
            }
        }

        foreach ($cart['items'] as $item) {
            $this->update($item['cart_id'], array('user_id' => $user['user_id']));
        }

        $this->deleteCookie();

        $result = array(
            'user' => $user,
            'redirect' => 'checkout',
            'severity' => 'success',
            'message' => $this->language->text('Hello, %name. Now you\'re logged in', array(
                '%name' => $user['name']
            ))
        );

        $this->hook->fire('cart.login.after', $user, $cart, $result, $this);
        return (array) $result;
    }

    /**
     * Deletes a cart record from the database
     * @param integer $cart_id
     * @return boolean
     */
    public function delete($cart_id)
    {
        $result = null;
        $this->hook->fire('cart.delete.before', $cart_id, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        if (!$this->canDelete($cart_id)) {
            return false;
        }

        $result = (bool) $this->db->delete('cart', array('cart_id' => $cart_id));

        Cache::clearMemory();

        $this->hook->fire('cart.delete.after', $cart_id, $result, $this);

        return (bool) $result;
    }

    /**
     * Whether a cart item can be deleted
     * @param integer $cart_id
     * @return boolean
     */
    public function canDelete($cart_id)
    {
        $sql = 'SELECT order_id FROM cart WHERE cart_id=?';
        $result = $this->db->fetchColumn($sql, array($cart_id));
        return isset($result) && empty($result);
    }

    /**
     * Deletes a cart user id from cookie
     * @return boolean
     */
    public function deleteCookie()
    {
        $cookie = $this->config->get('user_cookie_name', 'user_id');
        return $this->request->deleteCookie($cookie);
    }

}
