<?php

/**
 * Společné atributy a funkce modelů
 */
abstract class Fc_Model_DatabaseAbstract
{    
    /**
     * Databázový adaptér
     */
    protected static $_adapter;
    
    /**
     * Konstruktor - vytvoření databázového adaptéru          
     */
    public function __construct()
    {            
        self::$_adapter = Zend_Db_Table::getDefaultAdapter();
    }
}
