<?php

/**
 * Třída pro maniupulaci s příplatky docházky
 */
class Dochazka_Model_PriplatkyDochazky extends Fc_Model_DatabaseAbstract
{
    /**
     * ID příplatku
     * @var integer 
     */
    protected $_idZaznamu;
    
    /**
     * ID osoby
     * @var integer 
     */
    protected $_idOsoby;
    
    /**
     * ID čipu
     * @var integer 
     */
    protected $_idCipu;
    
    /**
     * ID typu přerušení
     * @var integer 
     */
    protected $_idTypuPriplatku;
    
    /**
     * Datum směny
     * @var date
     */
    protected $_datum;
    
    /**
     * Délka přerušení
     * @var float
     */
    protected $_delka;

    /**
     * Čas změny
     * @var timestamp
     */
    protected $_casZmeny;
    
    /**
     * ID osoby, která mění záznam
     * @var integer 
     */
    protected $_idZmenil;
    
    /**
     * Příznak smazaného záznamu
     * @var boolean
     */
    protected $_smazano = false;
   

  
    /**
     * Přidá nový přílatek v docházce do DB
     */
    public function addPriplatek() 
    {
        self::$_adapter->insert( 'dochazka_priplatky', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_priplatku' => $this->_idTypuPriplatku,
            'datum' => $this->_datum,
            'delka' => $this->_delka,
            'cas_zmeny' => $this->_casZmeny,
            'id_zmenil' => $this->_idZmenil
        ));   
    }

    /**
     * Vrátí požadovaný záznam příplatku docházky
     * 
     * @return array Pole s načteným záznamem
     */
    public function getPriplatek() 
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_priplatky'),
                    array('id_zaznamu', 'id_osoby', 'id_cipu', 'id_priplatku',
                        'datum', 'delka', 'id_zmenil', 'cas_zmeny', 'smazano'))
            ->join( array('d' => 'priplatky'),
                    'd.id_priplatku = pr.id_priplatku',
                    array('nazev') )                 
            ->where( 'pr.id_zaznamu = ?', $this->_idZaznamu);     
        
        $data = self::$_adapter->fetchRow($select);  
 
        if ($data['cas_zmeny'] == null) {               
            $casZmeny = null;
        } else {
            $casZmeny = date('Y-m-d H:i',strtotime($data['cas_zmeny']));
        }

        $result = array(
            'idZaznamu' => $data['id_zaznamu'],
            'idOsoby' => $data['id_osoby'],
            'idCipu' => $data['id_cipu'],
            'idPriplatku' => $data['id_priplatku'],
            'typPriplatku' => $data['nazev'],
            'datum' => $data['datum'],
            'delka' => $data['delka'],
            'idZmenil' => $data['id_zmenil'],
            'casZmeny' => $casZmeny,
            'smazano' => $data['smazano']
        );
       
        return $result;         
    }    
    
    /**
     * Změní požadovaný záznam přerušení docházky
     */
    public function editPriplatek() 
    {
        self::$_adapter->update(
            'dochazka_priplatky',
            array(
                'cas_zmeny' => date('Y-m-d H:i'),
                'id_zmenil' => $this->_idZmenil,
                'id_priplatku' => $this->_idTypuPriplatku,
                'delka' => $this->_delka,
                'datum' => $this->_datum
                ),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
        ));
    }
    
    /**
     * Označí požadované přerušení za zasmazané
     */
    public function deletePriplatek() 
    {
        self::$_adapter->update(
            'dochazka_priplatky',
            array(
                'cas_zmeny' => date('Y-m-d H:i'),
                'smazano' => true,
                'id_zmenil' =>$this->_idZmenil),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
        ));  
    }
    
//== GETTERS AND SETTERS =======================================================    
    
    public function getIdZaznamu() {
        return $this->_idZaznamu;
    }

    public function setIdZaznamu($_idZaznamu) {
        $this->_idZaznamu = $_idZaznamu;
    }

    public function getIdOsoby() {
        return $this->_idOsoby;
    }

    public function setIdOsoby($_idOsoby) {
        $this->_idOsoby = $_idOsoby;
    }

    public function getIdCipu() {
        return $this->_idCipu;
    }

    public function setIdCipu($_idCipu) {
        $this->_idCipu = $_idCipu;
    }

    public function getIdTypuPriplatku() {
        return $this->_idTypuPriplatku;
    }

    public function setIdTypuPriplatku($_idTypuPriplatku) {
        $this->_idTypuPriplatku = $_idTypuPriplatku;
    }

    public function getDatum() {
        return $this->_datum;
    }

    public function setDatum($_datum) {
        $this->_datum = $_datum;
    }

    public function getDelka() {
        return $this->_delka;
    }

    public function setDelka($_delka) {
        $this->_delka = $_delka;
    }

    public function getCasZmeny() {
        return $this->_casZmeny;
    }

    public function setCasZmeny($_casZmeny) {
        $this->_casZmeny = $_casZmeny;
    }

    public function getIdZmenil() {
        return $this->_idZmenil;
    }

    public function setIdZmenil($_idZmenil) {
        $this->_idZmenil = $_idZmenil;
    }

    public function getSmazano() {
        return $this->_smazano;
    }

    public function setSmazano($_smazano) {
        $this->_smazano = $_smazano;
    }
    
}
