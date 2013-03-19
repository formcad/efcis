<?php

/**
 * Třída pro maniupulaci s průchody docházky
 */
class Dochazka_Model_PruchodyDochazky extends Fc_Model_DatabaseAbstract
{
    /**
     * ID průchodu
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
     * ID akce
     * @var integer 
     */
    protected $_idAkce;
    
    /**
     * ID typu akce
     * @var integer
     */
    protected $_idTypu;
    
    /**
     * Datum směny (Y-m-d)
     * @var string
     */
    protected $_datum;
    
    /**
     * Čas akce (Y-m-d H:i)
     * @var string
     */
    protected $_casAkce;
    
    /**
     * Čas změny (Y-m-d H:i)
     * @var string
     */
    protected $_casZmeny = null;
    
    /**
     * ID osoby, která mění záznam
     * @var integer 
     */
    protected $_idZmenil = null;
    
    /**
     * Příznak smazaného záznamu
     * @var boolean
     */
    protected $_smazano = false;
     
    
    /**
     * Přidá nový průchod v docházce do DB
     */
    public function addPruchod() 
    {
        self::$_adapter->insert( 'dochazka_pruchody', array(
            'id_cipu' => $this->_idCipu,
            'id_osoby' => $this->_idOsoby,
            'id_akce' => $this->_idAkce,
            'datum' => $this->_datum,
            'cas_akce' => $this->_casAkce,
            'cas_zmeny' => $this->_casZmeny,
            'id_zmenil' => $this->_idZmenil
        ));
    }

    /**
     * Vrátí požadovaný záznam průchodu docházky
     * 
     * @return Array Pole s načteným záznamem
     */
    public function getPruchod() 
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_pruchody'),
                    array('id_zaznamu', 'id_osoby', 'id_cipu', 'id_akce',
                        'datum', 'cas_akce', 'id_zmenil', 'cas_zmeny', 'smazano'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = pr.id_akce',
                    array('nazev_akce','id_typu') )                 
            ->where( 'pr.id_zaznamu = ?', $this->_idZaznamu);     
        
        $data = self::$_adapter->fetchRow($select);  
 
        if ($data['cas_zmeny'] == null) {               
            $casZmeny = null;
        } else {
            $casZmeny = date('Y-m-d H:i',strtotime($data['cas_zmeny']));
        }

        switch($data['id_typu']) {
            case 1: $typ = 'prichod'; break;
            case 2: $typ = 'odchod';  break;
        }

        $result = array(
            'idZaznamu' => $data['id_zaznamu'],
            'idOsoby' => $data['id_osoby'],
            'idCipu' => $data['id_cipu'],
            'idAkce' => $data['id_akce'],
            'akce' => $data['nazev_akce'],
            'datum' => $data['datum'],
            'casAkce' => date('Y-m-d, H:i', strtotime($data['cas_akce'])),
            'idZmenil' => $data['id_zmenil'],
            'casZmeny' => $casZmeny,
            'smazano' => $data['smazano'],
            'typ' => $typ
        );
       
        return $result;
    }    
    
    /**
     * Změní požadovaný záznam průchodu docházky
     */
    public function editPruchod() 
    {       
        self::$_adapter->update(
            'dochazka_pruchody',
            array(
                'cas_zmeny' => date('Y-m-d H:i'),
                'id_zmenil' => $this->_idZmenil,
                'id_akce' => $this->_idAkce,
                'cas_akce' => $this->_casAkce,
                'datum' => $this->_datum
                ),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
        ));         
    }
    
    /**
     * Označí požadovaný průchod za zasmazaný
     */
    public function deletePruchod() 
    {
        self::$_adapter->update(
            'dochazka_pruchody',
            array(
                'cas_zmeny' => date('Y-m-d H:i'),
                'smazano' => true,
                'id_zmenil' =>$this->_idZmenil),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
        ));      
    }
    
    /**
     * Vybere poslední odchod pro danou kombinaci uživatele a čipu za podmínky,
     * že tato akce ještě nenastala (tzn. akce zapsané dopředu neplatí)
     */
    public function getPosledniAkce() 
    {       
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_pruchody'),
                    array('datum', 'cas_akce','id_akce'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = pr.id_akce',
                    array('id_typu'))
            ->where( 'pr.id_osoby = ?', $this->_idOsoby)
            ->where( 'pr.id_cipu = ?', $this->_idCipu)
            ->where( 'pr.smazano IS FALSE')
            ->where( 'pr.cas_akce < ?', date('Y-m-d H:i:s'))
            ->where( 'd.id_typu = ?', $this->_idTypu)
            ->order( array('pr.cas_akce DESC'));
        
        return self::$_adapter->fetchRow($select);                
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

    public function getIdAkce() {
        return $this->_idAkce;
    }

    public function setIdAkce($_idAkce) {
        $this->_idAkce = $_idAkce;
    }

    public function getIdTypu() {
        return $this->_idTypu;
    }

    public function setIdTypu($_idTypu) {
        $this->_idTypu = $_idTypu;
    }

    public function getDatum() {
        return $this->_datum;
    }

    public function setDatum($_datum) {
        $this->_datum = $_datum;
    }

    public function getCasAkce() {
        return $this->_casAkce;
    }

    public function setCasAkce($_casAkce) {
        $this->_casAkce = $_casAkce;
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
