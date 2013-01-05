<?php

/**
 * Třída pro práci s dny
 */
class Fc_Date_Days {
    
    /**
     * Pole názvů dní
     * 
     * @var array 
     */
    protected $_dayNames = array('Neděle','Pondělí','Úterý','Středa','Čtvrtek',
                                 'Pátek','Sobota');
    
    /**
     * Pole zkratek dní
     * 
     * @var array 
     */
    protected $_dayShortcuts = array('Ne','Po','Út','St','Čt','Pá','So');
    
    /**
     * Vrátí název dne pro toto datum
     * 
     * @param string $date Datum ve forámtu 'YYYY-MM-DD'
     * @return array Název dne
     */
    public function dayName($date) {
        
        $dayNumber = date('w', strtotime($date));

        return $this->_dayNames[$dayNumber];
    }
    
    
    /**
     * Vrátí zkratku dne pro toto datum
     * 
     * @param string $date Datum ve forámtu 'YYYY-MM-DD'
     * @return array Zkratka dne
     */    
    public function dayShortcut($date) {
        
        $dayNumber = date('w', strtotime($date));

        return $this->_dayShortcuts[$dayNumber];        
    }
}