Groobyster: Group On Register
=============================

This Prestashop module lets you choose a custom group that each new user will be added to during sign up. 

Compatibility
-------------

Prestashop >= 1.6

Installation
------------

Download the ZIP and unpack the groobyster folder to your Prestashop /modules directory. After installing and activating the module, create a special users group and pick it on Groobyster's configuration page.


Making blocknewsletter module work with groobyster
--------------------------------------------------

The newsletter registrations made with blocknewsletter form are handled by the module's own method called registerUser() . We cannot hook into it from outside and update the user's group like we do during normal user's update and creation so in order to fill the missing scenario we need to change this method a bit. 

Open modules/blocknewsletter/blocknewsletter.php and replace the original registerUser() with the one provided below. Ideally, you'd like to create a copy of the original blocknewsletter module and work on it because each automatic update is going to destroy your changes.

    protected function registerUser($email)
    {
    	$customers = new Customer();
    	$find_customer = $customers->getByEmail( $email );
    
    	if ( !$find_customer ) {
    		return false;
    	}
    
    	$find_customer->newsletter = 1;
    	$find_customer->newsletter_date_add = date('Y-m-d H:i:s');
    	$find_customer->ip_registration_newsletter = pSQL( Tools::getRemoteAddr() );
    
    	return ( $find_customer->update(true) );
    }

Authors
-------

Greg @ http://www.bazingadesigns.com/en | grzegorz.karol.jasinski@gmail.com
