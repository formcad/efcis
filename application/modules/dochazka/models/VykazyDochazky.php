<?php

/**
 * Výkazy oficiální docházky
 */
class Dochazka_Model_VykazyDochazky extends Fc_Model_DatabaseAbstract
{
    /**
     * ID výkazu docházky
     * @var integer
     */
    protected $_idDochazky = null;
    
    /**
     * ID zaměstnance
     * @var integer 
     */
    protected $_osoba = null; 
    
    /**
     * ID čipu
     * @var integer
     */
    protected $_cip = null;    
    
    /**
     * ID osoby měnící a zapisujíc data
     * @var type 
     */
    protected $_uzivatel = null;

    /**
     * Časový údaj - měsíc
     * @var integer
     */
    protected $_mesic = null;
    
    /**
     * Časový údaj - rok
     * @var integer
     */
    protected $_rok = null;    
    
    
    /**
     * Konstruktor třídy
     * 
     * @param  integer $idDochazky ID výkazu docházky
     * @param  integer $osoba      ID zaměstnance
     * @param  integer $cip        ID čipu
     * @param  integer $uzivatel   ID uživatele IS
     * @param  integer $mesic      Měsíc oficiální docházky
     * @param  integer $rok        Rok oficiální docházky
     * @return void
     */
    function __construct($idDochazky = null, $osoba = null, $cip = null,
        $uzivatel = null, $mesic = null, $rok = null) 
    {
        // konstruktor Fc_Model_DatabaseAbstract zajistí DB adaptér
        parent::__construct();
        
        $this->setIdDochazky($idDochazky);
        $this->setOsoba($osoba);
        $this->setCip($cip);
        $this->setUzivatel($uzivatel);
        $this->setMesic($mesic);
        $this->setRok($rok);     
    }
    
    /**
     * Ověří, zda už má zaměstnanec docházku pro konkrétní měsíc a rok (nutné
     * vyplněné _osoba, _cip, _mesic a _rok). True vrátí v případě, že oficiální
     * docházka pro kombinaci proměnných existuje
     * 
     * @return boolean
     */
    public function overExistenci() 
    {   
        $select = self::$_adapter->select()
            ->from('oficialni_dochazka',
                   array('id' => 'id_dochazky'))
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->where('mesic = ?', $this->_mesic)
            ->where('rok = ?', $this->_rok);
     
        // výsledek dotazu by měl být jenom jeden řádek nebo nic
        $vysledek = self::$_adapter->fetchAll($select);
        
        switch (count($vysledek)) {
            case 0:  return false; break;
            default: return true; break;
        }
    }      
    
    /**
     * Založení záznamu o oficiální dozcházce, které vrátí ID nového řádku
     * (nutné vyplněné _osoba, _cip, _mesic a _rok)
     * 
     * @return integer ID vloženého řádku
     */
    public function zalozDochazku()
    {
        self::$_adapter->insert( 'oficialni_dochazka', array(
            'id_cipu' => $this->_cip,
            'id_osoby' => $this->_osoba,
            'mesic' => $this->_mesic,
            'rok' => $this->_rok
        ));  
        
        return self::$_adapter->lastInsertId('oficialni_dochazka','id_dochazky');
    }
    
    /**
     * Pro kombinaci zaměstnance a čipu získá funkce všech zapsaných docházek
     * (nutné vyplněné _osoba, _cip)
     * 
     * @return array
     */
    public function ziskejDochazku()
    {
        $select = self::$_adapter->select()
            ->from(array('oficialni_dochazka'),
                array('id'=>'id_dochazky','mesic','rok'))
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->order(array('rok DESC', 'mesic'));
        
        return self::$_adapter->fetchAll($select);
    }   
    
    /**
     * Na základě konkrétního ID docházky získá její časový rozsah (nutné
     * vyplněné _idDochazky)
     * 
     * @return array
     */
    public function zjistiRozsahDochazky()
    {
        $select = self::$_adapter->select()
            ->from(array('oficialni_dochazka'),
                array('mesic','rok'))
            ->where('id_dochazky = ?', $this->_idDochazky);
        
        return self::$_adapter->fetchRow($select);
    }    
    
    public function getIdDochazky() {
        return $this->_idDochazky;
    }

    public function setIdDochazky($idDochazky) {
        $this->_idDochazky = $idDochazky;
    }

    public function getOsoba() {
        return $this->_osoba;
    }

    public function setOsoba($osoba) {
        $this->_osoba = $osoba;
    }

    public function getCip() {
        return $this->_cip;
    }

    public function setCip($cip) {
        $this->_cip = $cip;
    }

    public function getUzivatel() {
        return $this->_uzivatel;
    }

    public function setUzivatel($uzivatel) {
        $this->_uzivatel = $uzivatel;
    }

    public function getMesic() {
        return $this->_mesic;
    }

    public function setMesic($mesic) {
        $this->_mesic = $mesic;
    }

    public function getRok() {
        return $this->_rok;
    }

    public function setRok($rok) {
        $this->_rok = $rok;
    }
    
}
