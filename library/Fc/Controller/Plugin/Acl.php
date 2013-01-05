<?php

/**
 *  Třída hlídající autorizaci
 * 
 * @category   Fc
 * @package    Fc_Controller
 * @subpackage Plugins
 */
class Fc_Controller_Plugin_Acl extends Zend_Controller_Plugin_Abstract {

    /**
     * Před spuštěním dispečera se ověří, zda má na danou stránku uživatel přístup
     * 
     * @param Zend_Controller_Request_Abstract $request 
     * @return void
     */
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {            
        $module = $request->getModuleName();
        $controller = $request->getControllerName();
        $action = $request->getActionName();            
             
        //ACL nás zajímá pouze u přihlášených uživatelů
        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
     
            $acl = Fc_Acl::getInstance($module);
            
            $roles = $auth->getIdentity()->roles;
            $role = $roles[$module];
            $resource = $module.':'.$controller;
            $privilege = $action;
            
            if(!$acl->isAllowed($role, $resource, $privilege)) {
                $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
                $redirector->gotoUrlAndExit('/index/forbidden');                                
            }                      
        }
    }
}