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
     * Konstruktor třídy
     * 
     * @param  integer $idDochazky ID výkazu docházky
     * @param  integer $osoba      ID zaměstnance
     * @param  integer $cip        ID čipu
     * @return void
     */
    function __construct($idDochazky = null, $osoba = null, $cip = null) 
    {
        $this->setIdDochazky($idDochazky);
        $this->setOsoba($osoba);
        $this->setCip($cip);
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
    
}
