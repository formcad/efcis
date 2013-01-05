<?php

/**
 * Action hepler pro přidání záznamu o kontrole integrity do jiného pole
 */
class Fc_Controller_Action_Helper_IntegrityAdd 
        extends Zend_Controller_Action_Helper_Abstract 
{
    /**
     * Strategy pattern: call helper as broker method
     *
     * @param  array $pole
     * @param  array $souborIntegrity
     * @return array
     */ 
    public function direct($poleZaznamu, $souborIntegrity) 
    { 
        return $this->integrityAdd($poleZaznamu, $souborIntegrity); 
    }  
    
    /**
     * Přiřadí záznam ze souboru integrity do pole záznamů. Předpokládá se, že 
     * počet záznamů v obou polích je sejný
     * 
     * @param array $data pole dat
     * @return array pole s hodnotami, zda data každého jednoho dne jsou v pořádku nebo ne
     */
    public function integrityAdd($poleZaznamu, $souborIntegrity) 
    {      
        if (!empty($souborIntegrity)) {
            
            // připíšeme do výsledného pole výsledek integrity checku
            foreach ($souborIntegrity as $key=>$zaznam) {
                switch ($zaznam) {
                    case true: 
                        $poleZaznamu[$key]['integrityClass'] = ''; 
                        $poleZaznamu[$key]['timeIntegrity'] = true;
                        break;
                    case false: 
                        $poleZaznamu[$key]['integrityClass'] = 'falseIntegrity'; 
                        $poleZaznamu[$key]['timeIntegrity'] = false;
                        break;
                }
            }        

        }
        return $poleZaznamu;
    }    
}