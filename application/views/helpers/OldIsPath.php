<?php

/**
 *  View helper pro zobrazení cesty ke starému informačnímu systému
 */
class App_View_Helper_OldIsPath extends Zend_View_Helper_Abstract 
{
    /**
     * Vrátí cestu na starý informační systém
     * 
     * @return string Odkaz k odhlášení
     */
    public function oldIsPath ()
    {
        return "http://server:8080/vyroba";
    }
}
