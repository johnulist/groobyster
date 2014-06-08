Groobyster: Group On Register
=============================

This Prestashop module lets you choose a custom group that each new user will be added to during sign up. 

Compatibility
-------------

Prestashop >= 1.6

Installation
------------

Download the ZIP and unpack the groobyster folder to your Prestashop /modules directory. After installing and activating the module, create a special users group and pick it on Groobyster's configuration page.


Advanced installation
---------------------

If you'd like to update the user's group when they are registering using the blocknewsletter form, you'll have to edit the blocknewsletter.php file and replace the original registerUser function with this one:


    protected function registerUser($email)
    {
    	$sql = 'UPDATE '._DB_PREFIX_.'customer
    			SET `newsletter` = 1, newsletter_date_add = NOW(), `ip_registration_newsletter` = \''.pSQL(Tools::getRemoteAddr()).'\'
    			WHERE `email` = \''.pSQL($email).'\'
    			AND id_shop = '.$this->context->shop->id;
    
    	if (!Db::getInstance()->execute($sql)) {
    		return false;
    	}
    
    	$customers = new Customer();
    	$find_customer = $customers->getByEmail( $email );
    
    	if ( !$find_customer ) {
    		return false;
    	}
    
    	return ( $find_customer->update(true) );
    
    }


Authors: http://www.bazingadesigns.com/en
