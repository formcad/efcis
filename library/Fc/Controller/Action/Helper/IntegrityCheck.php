<?php

/**
 * Action hepler pro kontrolu integrity docházkových dat
 */
class Fc_Controller_Action_Helper_IntegrityCheck  
        extends Zend_Controller_Action_Helper_Abstract 
{
    /**
     * Strategy pattern: call helper as broker method
     *
     * @param  array $data
     * @return array
     */ 
    public function direct($data) 
    { 
        return $this->integrityCheck($data); 
    }  
    
    /**
     * Zkontroluje, zda jsou docházková data z každého dne v pořádku
     * 
     * @param array $data pole dat
     * @return array pole s hodnotami, zda data každého jednoho dne jsou v pořádku nebo ne
     */
    public function integrityCheck($data) 
    {    
        $vysledek = null;
        
        if (!empty($data)) {
            
            // pro každný z dnů 
            foreach ($data as $index => $den) {            
            
                // předpokládáme integritní data
                $integrity = true;
                
                // zpřehlednění kódu
                $pruchody = $den['pruchody'];
                    
                // pouze pokud jsou nějaké průchody ke zkontrolování
                if (!empty($pruchody)) {

                    // inicializace proměnné akce
                    $akce = null;
                    
                    // první musí být příchod
                    if ($pruchody[0]['typ'] <> 1) { $integrity = false; }

                    // poslední musí být odchod
                    if ($pruchody[count($pruchody)-1]['typ'] <> 2) { $integrity = false; }            

                    // příchody a odchody se střídají
                    foreach ($pruchody as $key => $zaznam) {

                        // první příchod už je odkontrolovaný
                        if ($key > 0) {

                            // pokud se dvakrát za sebou opakuje stejná akce, je to špatně
                            if ($zaznam['typ'] == $akce) { $integrity = false; }
                        }

                        // současnou akci uložíme do proměnné pro využití v příštím průběhu
                        $akce = $zaznam['typ'];                 
                    }   
                }

                // uložíme výsledek integrityChecku
                $vysledek[$index] = $integrity;              
            }
        }

        return $vysledek;
    }    
}