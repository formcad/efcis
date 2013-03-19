<?php

/**
 * Zákldní informace o typech průchodů docházky
 */
class Dochazka_Model_TypyPruchodu extends Fc_Model_DatabaseAbstract
{
    /**
     * Parametr pro filtr typů - buď prichod nebo odchod
     * @var string 
     */
    protected $_typ = 'prichod';
    
    /**
     * Získá seznam všech typů průchodů
     * @return array 
     */
    public function getTypy() 
    {
        $select = self::$_adapter->select()
            ->from('dochazka',
                   array('id' => 'id_akce','nazev' => 'nazev_akce',
                       'zkratka' => 'zkratka_akce','ikona'))
            ->where('platna IS TRUE')
            ->order(array('poradi_terminal'));
        
        return self::$_adapter->fetchAll($select);        
    }      
   
    /**
     * Získá filtrované typy - buď příchod nebo odchod
     * @return array 
     */
    public function getKonkretniTypy() {

        switch ($this->_typ) {
            case 'prichod': $typ = 1; break;
            case 'odchod':  $typ = 2; break;
        }
        
        $select = self::$_adapter->select()
            ->from(array('d' => 'dochazka'),
                   array('id' => 'id_akce','nazev' => 'nazev_akce'))
            ->where('d.id_typu = ?', $typ)
            ->where('d.platna IS TRUE')
            ->order(array('d.poradi_software'));
        
        return self::$_adapter->fetchAll($select);    
   }

   public function getTyp() {
       return $this->_typ;
   }

   public function setTyp($_typ) {
       $this->_typ = $_typ;
   }

}
