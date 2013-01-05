<?php

/**
 * Action hepler pro součet docházkových časů, časů pauz a časů přerušení
 */
class Fc_Controller_Action_Helper_TimeSum
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
        return $this->timeSum($data); 
    }  
    
    /**
     * Ze sady záznamů pro každý den vybere časy docházky, pauzy a přerušení
     * a vrátí vstupní pole, kam přidá součty těchto dat
     * 
     * @param array $data pole dat
     * @return array
     */
    public function timeSum($data) 
    {                
        if (!empty($data)) {
            
            // pro každný z dnů 
            foreach ($data as $index => $den) {            
                
                // za předpokladu integritních časů v daném dni
                if ($den['timeIntegrity']) {
                    
                    $sumaPruchodu = 0;
                    $sumaPreruseni = 0;
                    
                    /**** PRŮCHODY ********************************************/                    

                    $pruchody = $den['pruchody'];
                    $indexDne = count($den['pruchody'])-1;
                    
                    // postupujeme seřazeným polem průchodů od konce, 
                    while ($indexDne > -1) {
                     
                        $sumaPruchodu += strtotime($pruchody[$indexDne]['timestamp']) - strtotime($pruchody[$indexDne-1]['timestamp']);

                        $indexDne = $indexDne -2;
                    }
                    
                    /**** PŘERUŠENÍ *******************************************/
                    
                    foreach ($den['preruseni'] as $preruseni) {
                        
                        $sumaPreruseni += $preruseni['delka'];
                    }                       
                }                     
                    /**** PŘIDÁNÍ ZÁZNAMŮ DO POLE *****************************/
                    
                    $data[$index]['sumaDochazky'] = round($sumaPruchodu / 3600,2);
                    $data[$index]['sumaPreruseni'] = round($sumaPreruseni,2);               
                    $data[$index]['sumaCisteDochazky'] = round($sumaPruchodu/3600 - $sumaPreruseni,2);
            }
        }
        
        return $data;
    }    
}