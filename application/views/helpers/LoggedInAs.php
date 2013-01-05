<?php

/**
 * View helper pro zobrazení jména a příjmení přihlášeného uživatele
 */
class App_View_Helper_LoggedInAs extends Zend_View_Helper_Abstract 
{
    /**
     * Vrátí jméno a příjmení přihlášeného uživatele
     * 
     * @return string Jméno a příjmení 
     */
    public function loggedInAs ()
    {
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            
            $identity = $auth->getIdentity();
            $name = $identity->jmeno;
            $surname = $identity->prijmeni;
            return $name .' '.$surname;
        } 
    }
}