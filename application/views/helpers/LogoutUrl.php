<?php

/**
 *  View helper pro zobrazení odkazu k odhlášení uživatele
 */
class App_View_Helper_LogoutUrl extends Zend_View_Helper_Abstract 
{
    /**
     * Vrátí odkaz k odhlášení 
     * 
     * @return string Odkaz k odhlášení
     */
    public function logoutUrl ()
    {
        $url = new Zend_View_Helper_Url();
        
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            
            $logout  = '<a href="';
            $logout .= $url->url(array('controller'=>'auth', 'action'=>'logout'),null, true);
            $logout .= '">odhlásit</a>';
            
            return $logout;
        } else {
            return null;
        }         
    }
}
