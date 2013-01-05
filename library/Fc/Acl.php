<?php

require_once 'Zend/Acl.php';

/**
 * Rozšíření ACL
 * 
 * @category   Fc
 * @package    Fc_Acl
 */
class Fc_Acl extends Zend_Acl
{    
    /**
     * Instance jedináčků
     *
     * @var array obsahuje ACL objekty
     */
    protected static $_instances = array();
    
    /**
     * Získání instance
     * 
     * @param string $module Název modulu
     * @return Fc_Acl
     */
    public static function getInstance($module = 'default')
    {
        // Jestliže soubor neexistuje, přejdi k default modulu
        if (!file_exists(APPLICATION_PATH . '/configs/acl/' . $module . '.ini')) {
            $module = 'default';
        }
        
        // Otestuj, zda existuje instance a vytvoř v případě potřeby
        if (!isset(self::$_instances[$module]))
        {
            self::$_instances[$module] = new self($module);
        }
        
        // Vrať instanci
        return self::$_instances[$module];
    }    
    
    /**
     * Vytvoří ACL
     * 
     * @param string $module Název modulu
     */
    protected function __construct($module = 'default')
    {
        // načítá ACL z INI souboru
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/acl/' . $module . '.ini');
        
        // vytvoří uživatelské role          
        foreach($config->roles as $name => $parents) {
            if(!$this->hasRole($name)) {
                if(empty($parents)) {
                    $parents = array();
                } else {
                    $parents = explode(',', $parents);
                }
 
                $this->addRole(new Zend_Acl_Role($name), $parents);
            }
        }          
        
        // vytvoří zdroje
        foreach ($config->resources as $resource) {
            $this->addResource(new Zend_Acl_Resource($resource));
        }
        
        // vytvoří pravidla
        foreach ($config->rules as $function => $rule) {
            foreach ($rule as $role => $rule2) {
                foreach ($rule2 as $resource => $rule3) {
                    if ('all' == $rule3) {
                        $this->$function($role, $resource);
                    } else {
                        $this->$function($role, $resource, $rule3->toArray());
                    }
                }
            }
        }
    }    
}