<?php
if (!defined('_PS_VERSION_'))
	exit;

class idztrans extends Module
{	
	// Retrocompatibility 1.4/1.5
	private function initContext()
	{
	  if (class_exists('Context'))
	    $this->context = Context::getContext();
	  else
	  {
	    global $smarty, $cookie;
	    $this->context = new StdClass();
	    $this->context->smarty = $smarty;
	    $this->context->cookie = $cookie;
	  }
	}
	
	public function __construct()
	{
		$this->name        = 'idztrans';
		$this->tab         = 'analytics_stats';
		$this->version     = '1.0.0';
		$this->author      = 'iAdvize';
		$this->displayName = 'iAdvize Transaction';
		$this->module_key  = '';
		// Retrocompatibility
		$this->initContext();

		parent::__construct();

		$this->description = $this->l('iAdvize transaction.');
	}

	public function install()
	{
		return (parent::install() && $this->registerHook('orderConfirmation'));
	}

	public function hookOrderConfirmation($params)
	{
		// Setting parameters
		$parameters = Configuration::getMultiple(array('PS_LANG_DEFAULT'));

		$order = $params['objOrder'];
		if (Validate::isLoadedObject($order))
		{
			$delivery_address = new Address((int)$order->id_address_delivery);
			$conversion_rate  = 1;
			$currency         = new Currency((int)$order->id_currency);

			if ($order->id_currency != Configuration::get('PS_CURRENCY_DEFAULT'))
				$conversion_rate = (int)$currency->conversion_rate;

			$state_name = '';
			if ((int)$delivery_address->id_state > 0)
			{
				$state      = new State($delivery_address->id_state);
				$state_name = $state->name;
			}

			// Order general information
			$trans = array(
				'id'       => (int)$order->id,
				'store'    => htmlentities(Configuration::get('PS_SHOP_NAME')),
				'total'    => Tools::ps_round((float)$order->total_paid / (float)$conversion_rate, 2),
				'tax'      => $order->getTotalProductsWithTaxes() - $order->getTotalProductsWithoutTaxes(),
				'shipping' => Tools::ps_round((float)$order->total_shipping / (float)$conversion_rate, 2),
				'city'     => addslashes($delivery_address->city),
				'state'    => $state_name,
				'country'  => addslashes($delivery_address->country),
				'currency' => $currency->iso_code
			);

			// Product information
			$products = $order->getProducts();
			$items = array();
			foreach ($products as $product)
			{
				$category = Db::getInstance()->getRow('
								SELECT name FROM `'._DB_PREFIX_.'category_lang` , '._DB_PREFIX_.'product 
								WHERE `id_product` = '.(int)$product['product_id'].' AND `id_category_default` = `id_category`
								AND `id_lang` = '.(int)$parameters['PS_LANG_DEFAULT']);

				$items[] = array(
					'OrderId' => (int)$order->id,
					'SKU' => addslashes($product['product_id']),
					'Product' => addslashes($product['product_name']),
					'Category' => addslashes($category['name']),
					'Price' => Tools::ps_round((float)$product['product_price_wt'] / (float)$conversion_rate, 2),
					'Quantity' => addslashes((int)$product['product_quantity'])
				);
			}

			$this->context->smarty->assign('items', $items);
			$this->context->smarty->assign('trans', $trans);
			$this->context->smarty->assign('isOrder', true);

			return $this->display(__FILE__, 'views/templates/hook/header.tpl');
		}
	}
}
