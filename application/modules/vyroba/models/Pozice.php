<?php

class Vyroba_Model_Pozice extends Fc_Model_DatabaseAbstract
{
    /**
     * ID pozice
     * @var integer
     */
    private $_id;
    
    /**
     * Ověří existenci konkrétní pozice na základě jejího ID
     * @return boolean true = existuje, false = neexistuje
     */
    public function overExistenci()
    {
        $select = $this->_adapter->select()
            ->from('pozice',array('pocet' => 'count(id_pozice)'))
            ->where('id_pozice = ?', $this->_id);
        
        $data = $this->_adapter->fetchRow($select);
        
        if ( $data['pocet'] == 1)
            return true;
        else 
            return false;
    }
    
    public function setId($id) {

        $this->_id = $id;
    }
 
}