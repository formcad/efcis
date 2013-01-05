<?php

/**
 * Třída pro maniupulaci s přerušeními docházky
 */
class Dochazka_Model_PreruseniDochazky extends Fc_Model_DatabaseAbstract
{
    /**
     * ID přerušení
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
     * ID přerušení
     * @var integer
     */
    protected $_idPreruseni;
    
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
     * Přidá nové přerušení v docházce do DB
     */
    public function addPreruseni() 
    {
        $this->_adapter->insert( 'dochazka_preruseni', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_preruseni' => $this->_idPreruseni,
            'datum' => $this->_datum,
            'delka' => $this->_delka,
            'cas_zmeny' => $this->_casZmeny,
            'id_zmenil' => $this->_idZmenil
        ));        
    }

    /**
     * Vrátí požadovaný záznam přerušení docházky
     * 
     * @return array Pole s načteným záznamem
     */
    public function getPreruseni() 
    {    
        $select = $this->_adapter->select()
            ->from( array('pr' => 'dochazka_preruseni'),
                    array('id_zaznamu', 'id_osoby', 'id_cipu', 'id_preruseni',
                        'datum', 'delka', 'id_zmenil', 'cas_zmeny', 'smazano'))
            ->join( array('d' => 'preruseni'),
                    'd.id_preruseni = pr.id_preruseni',
                    array('nazev','id_preruseni') )                 
            ->where( 'pr.id_zaznamu = ?', $this->_idZaznamu);     
        
        $data = $this->_adapter->fetchRow($select);  
 
        if ($data['cas_zmeny'] == null) {               
            $casZmeny = null;
        } else {
            $casZmeny = date('Y-m-d H:i',strtotime($data['cas_zmeny']));
        }

        $result = array(
            'idZaznamu' => $data['id_zaznamu'],
            'idOsoby' => $data['id_osoby'],
            'idCipu' => $data['id_cipu'],
            'idPreruseni' => $data['id_preruseni'],
            'typPreruseni' => $data['nazev'],
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
    public function editPreruseni()
    {              
        $this->_adapter->update(
            'dochazka_preruseni',
            array(
                'cas_zmeny' => date('Y-m-d H:i'),
                'id_zmenil' => $this->_idZmenil,
                'id_preruseni' => $this->_idPreruseni,
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
    public function deletePreruseni() 
    {
        $this->_adapter->update(
            'dochazka_preruseni',
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

    public function setIdZaznamu($idZaznamu) {
        $this->_idZaznamu = $idZaznamu;
    }

    public function getIdOsoby() {
        return $this->_idOsoby;
    }

    public function setIdOsoby($idOsoby) {
        $this->_idOsoby = $idOsoby;
    }

    public function getIdCipu() {
        return $this->_idCipu;
    }

    public function setIdCipu($idCipu) {
        $this->_idCipu = $idCipu;
    }

    public function getIdPreruseni() {
        return $this->_idPreruseni;
    }

    public function setIdPreruseni($idPreruseni) {
        $this->_idPreruseni = $idPreruseni;
    }

    public function getDatum() {
        return $this->_datum;
    }

    public function setDatum($datum) {
        $this->_datum = $datum;
    }

    public function getDelka() {
        return $this->_delka;
    }

    public function setDelka($delka) {
        $this->_delka = $delka;
    }

    public function getCasZmeny() {
        return $this->_casZmeny;
    }

    public function setCasZmeny($casZmeny) {
        $this->_casZmeny = $casZmeny;
    }

    public function getIdZmenil() {
        return $this->_idZmenil;
    }

    public function setIdZmenil($idZmenil) {
        $this->_idZmenil = $idZmenil;
    }

    public function getSmazano() {
        return $this->_smazano;
    }

    public function setSmazano($smazano) {
        $this->_smazano = $smazano;
    }
    
}
