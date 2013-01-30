<?php

/**
 * Action hepler pro inicializaci formuláře, který slouží na více místech
 * aplikace jako našeptávač ID pozice v případě, že ID pozice neznáme
 */
class Fc_Controller_Action_Helper_NaseptavacPozice
        extends Zend_Controller_Action_Helper_Abstract 
{
    /**
     * Strategy pattern: call helper as broker method
     *
     * @param integer|null $idPolozky
     * @param string $role
     * @return \Application_Form_Naseptavac
     */ 
    public function direct($idPolozky,$role) 
    { 
        return $this->naseptavacPozice($idPolozky,$role); 
    }  
    
    /**
     * Získání formuláře našeptávače s defaultně zadaným ID položky
     * 
     * @param integer|null $idPolozky
     * @param string $role
     * @return \Application_Form_Naseptavac
     */
    public function naseptavacPozice($idPolozky,$role) 
    {                
        $zakazky = new Application_Model_PolozkyList();
        $zakazky->setRole($role);     
        $poleZakazek = $zakazky->getAktivniZakazky();
        
        switch ($idPolozky) {
            case null: $id = $poleZakazek[0]['id']; break;
            default: $id = $idPolozky;
        }        
        
        $pozice = new Application_Model_PoziceList();
        $pozice->setRole($role);
        $pozice->setIdPolozky($id);
        
        Application_Form_Naseptavac::$poleZakazek = $poleZakazek;    
        Application_Form_Naseptavac::$polePozic = $pozice->getPlnohodnotnePozice();
        Application_Form_Naseptavac::$idPolozky = $id;
        
        return new Application_Form_Naseptavac;
    }    
}