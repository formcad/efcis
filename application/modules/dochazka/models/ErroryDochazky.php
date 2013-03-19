<?php

/**
 * Třída pro manipulaci s chybovými záznamy v docházce
 */
class Dochazka_Model_ErroryDochazky extends Fc_Model_DatabaseAbstract {
   
    /**
     * ID mazaného záznamu
     * @var integer 
     */
    protected $_idZaznamu;
    
    /**
     * ID uživatele, pro kterého se mažou všechny záznam
     * @var integer 
     */    
    protected $_idUser;
    
    /**
     * ID čipu, pro který se mažou všechny záznamy
     * @var integer 
     */    
    protected $_idChip;
    
    /**
     * Získá podrobnosti vyžádaného záznamu
     */
    public function getError() 
    {   
        $select = self::$_adapter->select()
            ->from( array('e' => 'dochazka_error'),
                    array('cas_akce'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = e.id_akce',
                    array('nazev_akce') )                 
            ->where( 'e.id_zaznamu = ?', $this->_idZaznamu);     
        
        $data = self::$_adapter->fetchRow($select);  

        $timestamp = strtotime($data['cas_akce']);
        return array(           
            'akce' => $data['nazev_akce'],
            'casAkce' => date('d. m. Y, H:i', $timestamp)
        );
        return $data;        
    }

    /**
     * Odstraní zadaný záznam chyby v docházce z databáze
     */
    public function deleteError()
    {
        self::$_adapter->delete('dochazka_error', array(
            'id_zaznamu = ?' => $this->_idZaznamu
        ));              
    }
    
    /**
     * Odstraní všechny záznamy chyb v docházce pro danou kombinaci uživatele 
     * a docházkového čipu
     */
    public function deleteUserErrors()
    {
        self::$_adapter->delete('dochazka_error', array(
            'id_osoby = ?' => $this->_idUser,
            'id_cipu = ?' => $this->_idChip
        ));             
    }
    
    public function getAllErrors()
    {
        $select = self::$_adapter->select()
            ->from(array('ch' => 'dochazka_error'),
                array('id'=>'id_zaznamu','datum','cas'=>'cas_akce'))
            ->join(array('o' => 'osoby'),
                'o.id_osoby = ch.id_osoby',
                array('jmeno','prijmeni'))
            ->join(array('c' => 'cipy'),
                'c.id_cipu = ch.id_cipu',
                array('nazevCipu'=>'nazev'))
            ->join(array('d' => 'dochazka'),
                'd.id_akce = ch.id_akce',
                array('akce'=>'nazev_akce'))
            ->order(array('o.prijmeni','c.id_cipu','id_zaznamu'));
        
        return self::$_adapter->fetchAll($select);
    }

    public function setIdZaznamu($_idZaznamu) {
        $this->_idZaznamu = $_idZaznamu;
    }

    public function setIdUser($_idUser) {
        $this->_idUser = $_idUser;
    }

    public function setIdChip($_idChip) {
        $this->_idChip = $_idChip;
    }
}