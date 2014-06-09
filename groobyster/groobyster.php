<?php

if (!defined('_PS_VERSION_'))
	exit;

class Groobyster extends Module {

	public $id_default_group;
	private $form_errors;

	public function __construct() {

		$this->name = 'groobyster';
		$this->tab = 'bazinga';
		$this->version = '0.1';
		$this->author = 'Bazinga Designs';
		$this->bootstrap = true;
		
		parent::__construct();	

		$this->displayName = $this->l( 'Add to special group' );
		$this->description = $this->l( 'This module adds users who opted for newsletter to a special discount group.' );

	}

	public function install() {

		if 	( 	

				!parent::install() || 
				!$this->registerHook('actionObjectCustomerAddBefore') ||
				!$this->registerHook('actionObjectCustomerUpdateBefore') 

			) {

			return false;
		}

		return true;
	}

	public function uninstall() {

		return parent::uninstall();
	}

	public function getContent() {
		
		$this->postProcess();
		return $this->renderForm();
	}

	public function renderForm() {

		$groups = Group::getGroups( $this->context->language->id );

		$fields_form = array(
			'form' => array(
				'legend' => array(
					'title' => $this->l('Configuration'),
					'icon' => 'icon-cogs'
				),
				'input' => array(
					array(
						'type' => 'select',
						'label' => $this->l('Group to add customers to when they opted in for the newsletter'),
						'legend' => 'Lollolo',
						'name' => 'group_id',
						'options' => array(
							'query' => $groups,
							'id' => 'id_group',
							'name' => 'name'
						),
					),

				),
				'submit' => array(
					'title' => $this->l('Save'),
				)
			),
		);

		$helper = new HelperForm();
		$helper->show_toolbar = false;
		$helper->table = $this->table;
		$lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
		$helper->default_form_language = $lang->id;
		$helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ? Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') : 0;
		$this->fields_form = array();
		$helper->id = (int)Tools::getValue('id_carrier');
		$helper->identifier = $this->identifier;
		$helper->submit_action = 'submit_groobyster';
		$helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
		$helper->token = Tools::getAdminTokenLite('AdminModules');
		$helper->tpl_vars = array(

			'fields_value' => $this->getConfigFieldsValues(),
			'languages' => $this->context->controller->getLanguages(),
			'id_language' => $this->context->language->id

		);

		if ( isset( $this->form_errors ) && count( $this->form_errors ) ) {

			return $this->displayError( implode( '<br />', $this->form_errors ) ) . $helper->generateForm( array( $fields_form ) );
		}

		else {
			
			return $helper->generateForm( array($fields_form) );
		}

	}

	public function getConfigFieldsValues() {
		
		return array(

			'group_id' => Tools::getValue( 'group_id', Configuration::get( 'GROOBYSTER_GROUP_ID' ) )

		);
	}

	public function postProcess() {

		if ( Tools::isSubmit( 'submit_groobyster' ) ) {
			
			$group_id = (int)Tools::getValue( 'group_id' );

			if ( $group_id && !Validate::isUnsignedInt( $group_id ) ) {

				$this->form_errors[] = $this->l( 'Group ID must be a positive integer' );
			}

			else {

				Configuration::updateValue( 'GROOBYSTER_GROUP_ID', $group_id );
			}

		}	
		
	}

	/*
	-----------------------------------------------------------------------------------------
	@ > Runs before every account creation to ensure user gets to
	@ > the appropiate group: either "customer" or the special newsletter group
	@ > Execute the hook only if we're in front-office
	@ > to let the admins do what they want with the users
	-----------------------------------------------------------------------------------------
	*/

	public function hookActionObjectCustomerAddBefore( $params ) {

		$context = Context::getContext();
		$controller = get_class( $context->controller );

		if ( $controller == 'AdminCustomersController' ) {
			return;
		}

		$customer = $params[ 'object' ];
		$special_group = Configuration::get( 'GROOBYSTER_GROUP_ID' );
		if ($special_group && $customer->newsletter == 1) {
					
			$customer->addGroups( (array)$special_group );
			$customer->id_default_group = (int)$special_group;

		}
	}


	/*
	-----------------------------------------------------------------------------------------
	@ > Runs before every customer account update to check if they
	@ > changed his/her mind and enabled/disabled the newsletter subscription
	@ > 
	-----------------------------------------------------------------------------------------
	*/
	
	public function hookActionObjectCustomerUpdateBefore( $params ) {

		$context = Context::getContext();
		$controller = get_class( $context->controller );

		if ( $controller == 'AdminCustomersController' ) {
			return;
		}

		$customer = $params[ 'object' ];
		$discount_group = Configuration::get( 'GROOBYSTER_GROUP_ID' );
		$current_groups = $customer->getGroups();
		
		/*
		-----------------------------------------------------------------------------------------
		@ > If the newsletter field is checked and the user is not
		@ > in the 'newsletterians' group yet, we'll add them to
		@ > the selected group and remove from the default 'cutomer' group
		-----------------------------------------------------------------------------------------
		*/
		
		if ( $customer->newsletter == 1 && !in_array( $discount_group, $current_groups ) ) {

			$updated_groups = (array)$discount_group;
			$customer->updateGroup( $updated_groups );
			$customer->id_default_group = $discount_group;
		}

		/*
		-----------------------------------------------------------------------------------------
		@ > Otherwise we'll remove them from the special group
		@ > and add them to the default "customers" group
		@ > 
		-----------------------------------------------------------------------------------------
		*/
		
		elseif ( $customer->newsletter == 0 && in_array( $discount_group, $current_groups ) ) {
			
			$updated_groups = (array)Configuration::get( 'PS_CUSTOMER_GROUP' );
			$customer->updateGroup( $updated_groups );
			$customer->id_default_group = (int)Configuration::get( 'PS_CUSTOMER_GROUP' );
		}

	}

}