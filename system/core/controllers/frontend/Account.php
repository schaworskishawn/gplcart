<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\controllers\frontend;

use gplcart\core\models\Order as OrderModel;
use gplcart\core\models\State as StateModel;
use gplcart\core\models\Payment as PaymentModel;
use gplcart\core\models\Address as AddressModel;
use gplcart\core\models\Country as CountryModel;
use gplcart\core\models\UserRole as UserRoleModel;
use gplcart\core\models\Shipping as ShippingModel;
use gplcart\core\models\PriceRule as PriceRuleModel;
use gplcart\core\controllers\frontend\Controller as FrontendController;

/**
 * Handles incoming requests and outputs data related to user accounts
 */
class Account extends FrontendController
{

    /**
     * Address model instance
     * @var \gplcart\core\models\Address $address
     */
    protected $address;

    /**
     * Country model instance
     * @var \gplcart\core\models\Country $country
     */
    protected $country;

    /**
     * State model instance
     * @var \gplcart\core\models\State $state
     */
    protected $state;

    /**
     * Order model instance
     * @var \gplcart\core\models\Order $order
     */
    protected $order;

    /**
     * User role model instance
     * @var \gplcart\core\models\UserRole $role
     */
    protected $role;

    /**
     * Price rule model instance
     * @var \gplcart\core\models\PriceRule $pricerule
     */
    protected $pricerule;

    /**
     * Payment model instance
     * @var \gplcart\core\models\Payment $payment
     */
    protected $payment;

    /**
     * Shipping model instance
     * @var \gplcart\core\models\Shipping $shipping
     */
    protected $shipping;

    /**
     * The current user
     * @var array
     */
    protected $data_user = array();

    /**
     * Constructor
     * @param AddressModel $address
     * @param CountryModel $country
     * @param StateModel $state
     * @param OrderModel $order
     * @param UserRoleModel $role
     * @param PriceRuleModel $pricerule
     * @param PaymentModel $payment
     * @param ShippingModel $shipping
     */
    public function __construct(AddressModel $address, CountryModel $country,
            StateModel $state, OrderModel $order, UserRoleModel $role,
            PriceRuleModel $pricerule, PaymentModel $payment,
            ShippingModel $shipping)
    {
        parent::__construct();

        $this->role = $role;
        $this->state = $state;
        $this->order = $order;
        $this->country = $country;
        $this->address = $address;
        $this->payment = $payment;
        $this->shipping = $shipping;
        $this->pricerule = $pricerule;
    }

    /**
     * Displays the customer account page
     * @param integer $user_id
     */
    public function indexAccount($user_id)
    {
        $this->setUserAccount($user_id);

        $this->setTitleIndexAccount();
        $this->setBreadcrumbIndexAccount();

        $query = $this->getFilterQuery();
        $total = $this->getTotalOrderAccount();

        $default_limit = $this->config('account_order_limit', 5);
        $limit = $this->setPager($total, $query, $default_limit);

        $this->setData('user', $this->data_user);
        $this->setData('orders', $this->getListOrderAccount($limit, $query));

        $this->outputIndexAccount();
    }

    /**
     * Returns a user
     * @param integer $user_id
     * @return array
     */
    protected function setUserAccount($user_id)
    {
        $user = $this->user->get($user_id);

        if (empty($user)) {
            $this->outputHttpStatus(404);
        }

        if (empty($user['status']) && !$this->access('user')) {
            $this->outputHttpStatus(403);
        }

        $this->data_user = $user;
        return $user;
    }

    /**
     * Returns a number of total orders for the customer
     * @return integer
     */
    protected function getTotalOrderAccount()
    {
        $options = array(
            'count' => true,
            'user_id' => $this->data_user['user_id']
        );

        return (int) $this->order->getList($options);
    }

    /**
     * Returns an array of orders for the customer
     * @param array $limit
     * @param array $query
     * @return array
     */
    protected function getListOrderAccount(array $limit, array $query)
    {
        $query += array(
            'order' => 'desc',
            'limit' => $limit,
            'sort' => 'created'
        );

        $query['user_id'] = $this->data_user['user_id'];
        $orders = (array) $this->order->getList($query);

        foreach ($orders as $order_id => &$order) {

            $order['cart'] = $this->cart->getList(array('order_id' => $order_id));
            $order['total_formatted'] = $this->price->format($order['total'], $order['currency']);

            $components = $this->getOrderComponentsAccount($order);
            $address = $this->address->get($order['shipping_address']);
            $translated_address = $this->address->getTranslated($address, true);

            $data = array(
                'order' => $order,
                'components' => $components,
                'shipping_address' => $translated_address
            );

            $order['rendered'] = $this->render('account/order', $data);
        }

        return $orders;
    }

    /**
     * Returns an array of order components
     * @param array $order
     * @return array
     */
    public function getOrderComponentsAccount(array $order)
    {
        $components = array();
        foreach ($order['data']['components'] as $type => $value) {
            $this->setComponentCartAccount($components, $type, $value, $order);
            $this->setComponentMethodAccount($components, $type, $value, $order);
            $this->setComponentRuleAccount($components, $type, $value);
        }

        ksort($components);
        return $components;
    }

    /**
     * Sets rendered component "Cart"
     * @param array $components
     * @param string $type
     * @param array $component_cart
     * @param array $order
     */
    protected function setComponentCartAccount(array &$components, $type,
            $component_cart, array $order)
    {
        if ($type === 'cart') {

            foreach ($component_cart as $sku => $price) {
                $order['cart'][$sku]['price_formatted'] = $this->price->format($price, $order['currency']);
            }

            $html = $this->render('backend|sale/order/panes/components/cart', array('order' => $order));
            $components['cart'] = $html;
        }
    }

    /**
     * Sets a rendered payment/shipping component
     * @param array $components
     * @param string $type
     * @param integer $price
     * @param array $order
     * @return null
     */
    protected function setComponentMethodAccount(array &$components, $type,
            $price, array $order)
    {
        if (!in_array($type, array('shipping', 'payment'))) {
            return null;
        }

        $method = $this->{$type}->get();
        $method['name'] = isset($method['name']) ? $method['name'] : $this->text('Unknown');

        if (abs($price) == 0) {
            $price = 0; // No negative values
        }

        $method['price_formatted'] = $this->price->format($price, $order['currency']);
        $html = $this->render('backend|sale/order/panes/components/method', array('method' => $method));

        $components[$type] = $html;
        return null;
    }

    /**
     * Sets a rendered price rule component
     * @param array $components
     * @param integer $rule_id
     * @param integer $price
     * @return null
     */
    protected function setComponentRuleAccount(array &$components, $rule_id,
            $price)
    {
        if (!is_numeric($rule_id)) {
            return null;
        }

        if (abs($price) == 0) {
            $price = 0;
        }

        $rule = $this->pricerule->get($rule_id);

        $data = array(
            'rule' => $rule,
            'price' => $this->price->format($price, $rule['currency'])
        );

        $html = $this->render('backend|sale/order/panes/components/rule', $data);
        $components['rule'][$rule_id] = $html;
        return null;
    }

    /**
     * Sets breadcrumbs on the account page
     */
    protected function setBreadcrumbIndexAccount()
    {
        $breadcrumb = array(
            'url' => $this->url('/'),
            'text' => $this->text('Shop')
        );

        $this->setBreadcrumb($breadcrumb);
    }

    /**
     * Sets titles on the account page
     */
    protected function setTitleIndexAccount()
    {
        $this->setTitle($this->text('Orders'), false);
    }

    /**
     * Renders the account page templates
     */
    protected function outputIndexAccount()
    {
        $this->output('account/account');
    }

    /**
     * Displays the customer edit account page
     * @param integer $user_id
     */
    public function editAccount($user_id)
    {
        $this->setUserAccount($user_id);
        $this->controlAccessEditAccount();

        $this->setTitleEditAccount();
        $this->setBreadcrumbEditAccount();

        $this->setData('user', $this->data_user);
        $this->setData('roles', $this->role->getList());
        $this->setData('stores', $this->store->getNames());

        $this->submitEditAccount();
        $this->outputEditAccount();
    }

    /**
     * Controls user access to the edit account page
     */
    protected function controlAccessEditAccount()
    {
        if ($this->data_user['user_id'] != $this->uid && !$this->access('user_edit')) {
            $this->outputHttpStatus(403);
        }
    }

    /**
     * Saves submitted user account settings
     */
    protected function submitEditAccount()
    {
        if (!$this->isPosted('save')) {
            return null;
        }

        $this->setSubmitted('user', null, 'raw');
        $this->validateAccount();

        if (!$this->hasErrors('user')) {
            $this->updateAccount();
        }
    }

    /**
     * Validates a user
     * @return boolean
     */
    protected function validateAccount()
    {
        $this->setSubmitted('update', $this->data_user);
        $this->setSubmitted('user_id', $this->data_user['user_id']);

        $this->validate('user');
    }

    /**
     * Updates a user with submitted values
     */
    protected function updateAccount()
    {
        $this->controlAccessEditAccount();

        $values = $this->getSubmitted();
        $this->user->update($this->data_user['user_id'], $values);

        $message = $this->text('Account has been updated');
        $this->redirect('', $message, 'success');
    }

    /**
     * Sets breadcrumbs on the account edit form
     */
    protected function setBreadcrumbEditAccount()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('/'),
            'text' => $this->text('Shop')
        );

        $breadcrumbs[] = array(
            'text' => $this->text('Account'),
            'url' => $this->url("account/{$this->data_user['user_id']}")
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Sets titles on the edit account page
     */
    protected function setTitleEditAccount()
    {
        $this->setTitle($this->text('Edit account'), false);
    }

    /**
     * Renders the edit account page templates
     */
    protected function outputEditAccount()
    {
        $this->output('account/edit');
    }

    /**
     * Displays the addresses overview page
     * @param integer $user_id
     */
    public function listAddressAccount($user_id)
    {
        $this->setUserAccount($user_id);

        $this->setTitleListAddressAccount();
        $this->setBreadcrumbListAddressAccount();

        $this->deleteAddressAccount();

        $this->setData('user', $this->data_user);
        $this->setData('addresses', $this->getListAddressAccount());

        $this->outputListAddressAccount();
    }

    /**
     * Returns an array of addresses
     * @return array
     */
    protected function getListAddressAccount()
    {
        return $this->address->getTranslatedList($this->data_user['user_id']);
    }

    /**
     * Deletes an address
     * @return array
     */
    protected function deleteAddressAccount()
    {
        $address_id = (int) $this->request->get('delete');

        if (empty($address_id)) {
            return null;
        }

        $this->controlAccessEditAccount();

        $deleted = $this->address->delete($address_id);

        if ($deleted) {
            $message = $this->text('Address has been deleted');
            $this->redirect('', $message, 'success');
        }

        $message = $this->text('Address cannot be deleted');
        $this->redirect('', $message, 'warning');
    }

    /**
     * Sets breadcrumbs on the address list page
     */
    protected function setBreadcrumbListAddressAccount()
    {
        $breadcrumbs = array();

        $breadcrumbs[] = array(
            'url' => $this->url('/'),
            'text' => $this->text('Shop')
        );

        $breadcrumbs[] = array(
            'text' => $this->text('Account'),
            'url' => $this->url("account/{$this->data_user['user_id']}")
        );

        $this->setBreadcrumbs($breadcrumbs);
    }

    /**
     * Sets titles on the addresses overview page
     */
    protected function setTitleListAddressAccount()
    {
        $this->setTitle($this->text('Addresses'), false);
    }

    /**
     * Renders the addresses overview page
     */
    protected function outputListAddressAccount()
    {
        $this->output('account/address/list');
    }

    /**
     * Displays edit address form
     * @param integer $user_id
     * @param integer|null $address_id
     */
    public function editAddressAccount($user_id, $address_id = null)
    {
        $this->setUserAccount($user_id);
        $this->controlAccessEditAccount();

        $this->outputEditAddressFormAccount();

        $this->setDataEditAddressAccount();
        $this->setTitleEditAddressAccount();

        $this->setData('user', $this->data_user);
        $this->setData('address', $this->getAddressAccount($address_id));

        $this->submitAddressAccount();
        $this->outputEditAddressAccount();
    }

    /**
     * Sets template variables
     */
    protected function setDataEditAddressAccount()
    {
        $address = $this->getData('address');
        $form = $this->getEditAddressFormAccount($address);
        $this->setData('address_form', $form);
    }

    /**
     * Returns rendered address edit form
     * @param array $address
     * @return string
     */
    protected function getEditAddressFormAccount($address)
    {
        $country = isset($address['country']) ? $address['country'] : '';
        $format = $this->country->getFormat($country, true);
        $countries = $this->country->getNames(true);

        $options = array('status' => 1, 'country' => $country);
        $states = $this->state->getList($options);

        $format_country = empty($format['country']) ? array() : $format['country'];
        $format_state_id = empty($format['state_id']) ? array() : $format['state_id'];

        unset($format['country'], $format['state_id']);

        $data = array(
            'states' => $states,
            'format' => $format,
            'address' => $address,
            'countries' => $countries,
            'format_country' => $format_country,
            'format_state_id' => $format_state_id
        );

        return $this->render('account/address/form', $data);
    }

    /**
     * Displays edit address form
     */
    protected function outputEditAddressFormAccount()
    {
        $code = (string) $this->request->post('country');

        if (empty($code)) {
            return null;
        }

        $country = $this->country->get($code);

        if (empty($country['status'])) {
            return null;
        }

        $form = $this->getEditAddressFormAccount(array('country' => $code));
        $this->response->html($form);
    }

    /**
     * Returns an address
     * @param integer $address_id
     * @return array
     */
    protected function getAddressAccount($address_id)
    {
        if (!is_numeric($address_id)) {
            return array('country' => $this->country->getDefault());
        }

        $address = $this->address->get($address_id);

        if (empty($address)) {
            $this->outputHttpStatus(404);
        }

        return $address;
    }

    /**
     * Saves a user address
     * @return null
     */
    protected function submitAddressAccount()
    {
        if (!$this->isPosted('save')) {
            return null;
        }

        $this->setSubmitted('address');
        $this->validateAddressAccount();

        if (!$this->hasErrors('address')) {
            $this->addAddressAccount();
        }
    }

    /**
     * Validates a submitted address
     */
    protected function validateAddressAccount()
    {
        $this->setSubmitted('user_id', $this->data_user['user_id']);
        $this->validate('address');
    }

    /**
     * Adds an address
     */
    protected function addAddressAccount()
    {
        $this->controlAccessEditAccount();
        $result = $this->address->add($this->getSubmitted());
        $this->address->controlLimit($this->data_user['user_id']);

        if (empty($result)) {
            $message = $this->text('Address has not been added');
            $this->redirect('', $message, 'warning');
        }

        $message = $this->text('New address has been added');
        $this->redirect("account/{$this->data_user['user_id']}/address", $message, 'success');
    }

    /**
     * Sets titles on the edit address page
     */
    protected function setTitleEditAddressAccount()
    {
        $this->setTitle($this->text('Add new address'), false);
    }

    /**
     * Renders the edit address page
     */
    protected function outputEditAddressAccount()
    {
        $this->output('account/address/edit');
    }

}
