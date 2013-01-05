<?php

/**
 *  Třída pro hlídání autentizace
 * 
 * @category   Fc
 * @package    Fc_Controller
 * @subpackage Plugins
 */
class Fc_Controller_Plugin_Auth extends Zend_Controller_Plugin_Abstract {

    /**
     * Je vykonaná před startem dispečera
     *
     * @param  Zend_Controller_Request_Abstract $request
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        // pokud nejde o zabezpečenou stránku, nebudeme nic ověřovat
        if (!$this->_isAuthRequest($request))
        {
            return;
        }
        
        // nepřihlášení se musí přihlásit
        if (!Zend_Auth::getInstance()->hasIdentity()) {
            $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
            $redirector->gotoUrlAndExit('/auth/index');
        }        
    }
    
    /**
     * Ověří, zda jde o heslem zabezpečenou stránku
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @return boolean false = stránka není zabezpečená, true znamená opak
     */
    protected function _isAuthRequest($request)
    { 
        $controller = $request->getControllerName();
        $action = $request->getActionName();
        
        // stránky které nejsou zabezpečené heslem
        if (($controller == 'auth' and in_array($action, array('index'))) or
            ($controller == 'terminal'))
        {
            return false;            
        } else {
            return true;
        }       
//        // pouze pro účely ladění - zakomentovat funckci, nechat tento řádek
//        return false;
    }      
    
}