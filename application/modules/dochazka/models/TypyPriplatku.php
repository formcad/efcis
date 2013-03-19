<?php

/**
 * Zákldní informace o typech příplatků docházky
 */
class Dochazka_Model_TypyPriplatku extends Fc_Model_DatabaseAbstract
{
    /**
     * Datum platnosti příplatku
     * @var string 
     */
    protected $_platnost = null;

    /**
     * Získá seznam všech typů příplatků docházky pro určité datum (_platnost)
     * 
     * @return array 
     */        
    public function getTypy() 
    {
         $select = self::$_adapter->select()
             ->from('priplatky',
                    array('id' => 'id_priplatku','nazev','zkratka'))
             ->where('platnost_od <= ?', $this->_platnost)
             ->where('platnost_do >= ? OR platnost_do IS NULL', $this->_platnost)
             ->order(array('poradi'));

         return self::$_adapter->fetchAll($select);        
    }    

    public function getPlatnost() {
        return $this->_platnost;
    }

    public function setPlatnost($platnost) {
        $this->_platnost = $platnost;
    }
    
}
