<?php

/**
 * Zákldní informace o typech přerušení docházky
 */
class Dochazka_Model_TypyPreruseni extends Fc_Model_DatabaseAbstract
{
    /**
     * Získá seznam všech typů přerušení docházky
     * @return array 
     */    
   public function getTypy() 
   {
        $select = $this->_adapter->select()
            ->from('preruseni',
                   array('id' => 'id_preruseni','nazev'))
            ->where('aktivni IS TRUE')
            ->order(array('poradi'));
        
        return $this->_adapter->fetchAll($select);        
   }
}
