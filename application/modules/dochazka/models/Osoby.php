<?php

/**
 * Model podrobností osob
 */
class Dochazka_Model_Osoby extends Fc_Model_DatabaseAbstract
{
    /**
     * ID uživatele
     * 
     * @var integer 
     */
    protected $_id = null;
    
    /**
     * Nastavení ID uživatele
     * 
     * @param integer $idUser 
     */
    public function setId($id) {
        $this->_id = $id;
    }

    /**
     * Načtení podrobností o osobě 
     * 
     * @return array
     */
    public function getUserName() {
        
        // obecný dotaz
        $select = $this->_adapter->select()
            ->from( 'osoby',
                    array('jmeno', 'prijmeni') )
            ->where( 'id_osoby = ?',$this->_id );              
        
        $row = $this->_adapter->fetchRow($select);  
        
        return array("jmeno" => $row['jmeno'], "prijmeni" => $row['prijmeni']);
    }
}