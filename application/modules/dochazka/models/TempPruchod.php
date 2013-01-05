<?php

/**
 * Třída získává data z tabulky dochazka_temp
 */
class Dochazka_Model_TempPruchod extends Fc_Model_DatabaseAbstract
{
   
    /**
     * Role uživatele v modulu docházka
     * @var integer 
     */
    protected $_role = null;   
    
    /**
     * Zjištění lidí aktuálně přítomných v práci
     * @return array
     */
    public function zjistiPritomne()
    {
        if ($this->_role == 'guest') {
            throw new Exception('Nemáte dostatečné oprávnění k prohlížení stránky');    
        }
        
        $select = $this->_adapter->select()
            ->from(array('dt' => 'dochazka_temp'),
                   array('casPrichodu' => 'cas_akce'))
            ->join(array('o' => 'osoby'),
                   'o.id_osoby = dt.id_osoby',
                   array('jmeno','prijmeni'))
            ->order(array('o.prijmeni'));
        
        return $this->_adapter->fetchAll($select);
    }
    
    public function getRole() {
        return $this->_role;
    }

    public function setRole($role) {
        $this->_role = $role;
    }

}