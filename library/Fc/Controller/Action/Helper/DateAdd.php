<?php

/**
 * Action hepler pro přidání jména dne a formátované zkratky data do vstupního
 * pole
 */
class Fc_Controller_Action_Helper_DateAdd 
        extends Zend_Controller_Action_Helper_Abstract 
{
    /**
     * Strategy pattern: call helper as broker method
     *
     * @param  array $pole
     * @param  array $souborIntegrity
     * @return array
     */ 
    public function direct($poleZaznamu) 
    { 
        return $this->dateAdd($poleZaznamu); 
    }  
    
    /**
     * Přiřadí záznam ze souboru integrity do pole záznamů. Předpokládá se, že 
     * počet záznamů v obou polích je sejný
     * 
     * @param array $data pole dat
     * @return array pole s hodnotami, zda data každého jednoho dne jsou v pořádku nebo ne
     */
    public function dateAdd($poleZaznamu) 
    {              
        $dny = new Fc_Date_Days();
        $format = new Fc_Date_Format();
        
        if (!empty($poleZaznamu)) {
            
            foreach ($poleZaznamu as $key => $den) {
                $poleZaznamu[$key]['zkratkaDne'] = $dny->dayShortcut($den['datum']);
                $poleZaznamu[$key]['strDate'] = $format->dateFormat($den['datum']);

                switch ($den['svatek']) {
                    case true: $poleZaznamu[$key]['svatekText'] = ' (Sv)'; break;
                    default: $poleZaznamu[$key]['svatekText'] = null; break;
                }
            }
        }
        return $poleZaznamu;
    }    
}