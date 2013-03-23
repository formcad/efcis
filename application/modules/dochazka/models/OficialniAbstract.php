<?php

/**
 * Abstraktní třída oficiální docházky
 */
class Dochazka_Model_OficialniAbstract extends Fc_Model_DatabaseAbstract
{        
    
    /**
     * ID zaměstnance
     * @var Integer 
     */
    protected $_osoba = null; 
    
    /**
     * ID čipu
     * @var Integer
     */
    protected $_cip = null;
    
    /**
     * ID osoby měnící a zapisující data
     * @var Integer 
     */
    protected $_uzivatel = null;
    
    /**
     * Proměnná data směny použitá při změně záznamu oficiálního průchodu a
     * při zadávání nového průchodu
     * @var String
     */
    protected $_datumSmeny = null;

    /**
     * Počátek datového rozsahu
     * @var String
     */
    protected $_datumOd = null;
    
    /**
     * Konec datového rozsahu
     * @var String
     */
    protected $_datumDo = null;    
    
    /**
     * Konctruktor třídy
     * 
     * @param Integer $osoba            ID zaměstnance
     * @param Integer $cip              ID typu čipu zaměstnance
     * @param Integer $uzivatel         ID uživatele IS
     * @param String|Null  $datumSmeny  Datum směny
     */
    function __construct($osoba, $cip, $uzivatel, $datumSmeny = null) 
    {
        // konstruktor Fc_Model_DatabaseAbstract zajistí DB adaptér
        parent::__construct();
        
        $this->setOsoba($osoba);
        $this->setCip($cip);
        $this->setUzivatel($uzivatel);
        $this->setDatumSmeny($datumSmeny);
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

    public function getDatumSmeny() {
        return $this->_datumSmeny;
    }

    public function setDatumSmeny($datumSmeny) {
        $this->_datumSmeny = $datumSmeny;
    }    

    public function getDatumOd() {
        return $this->_datumOd;
    }

    public function setDatumOd($datumOd) {
        $this->_datumOd = $datumOd;
    }

    public function getDatumDo() {
        return $this->_datumDo;
    }

    public function setDatumDo($datumDo) {
        $this->_datumDo = $datumDo;
    }    
    
}