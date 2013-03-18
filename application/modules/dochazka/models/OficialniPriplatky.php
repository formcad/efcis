<?php

/**
 * Informace o příplatcích oficiální docházky
 */
class Dochazka_Model_OficialniPriplatky extends Dochazka_Model_DochazkaOficialni
{
    /**
     * Datum směny
     * @var string
     */
    protected $_datumSmeny = null;    
    
    /**
     * ID typu příplatku
     * @var integer     
     */
    protected $_idPriplatku = null;
    
    /**
     * Číslená hodnota příplatku
     * @var float
     */
    protected $_hodnota = null;

    /**
     * Konctruktor třídy
     * 
     * @param integer $osoba            ID zaměstnance
     * @param integer $cip              ID typu čipu zaměstnance
     * @param ingeger $uzivatel         ID uživatele IS
     * @param string|null  $datumSmeny  Datum směny
     * @param integer|null $idPriplatku ID typu příplatku
     * @param float|null   $hodnota     Číslená hodnota příplatku
     */
    function __construct($osoba, $cip, $uzivatel, $datumSmeny = null,
        $idPriplatku = null, $hodnota = null) 
    {
        parent::__construct($osoba, $cip, $uzivatel);
        
        $this->setDatumSmeny($datumSmeny);
        $this->setIdPriplatku($idPriplatku);
        $this->setHodnota($hodnota);
    }
 
    /**
     * Pro kombinaci _osoba, _cip a _datumSmeny získá z oficiální docházky
     * zapsané příplatky
     * 
     * @return array
     */
    public function ziskejPriplatky()
    {  
        $select = self::$_adapter->select()
            ->from(array('pp' => 'oficialni_priplatky'),
                array('idPriplatku'=>'id_priplatku', 'delka'))
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip);
        
        return self::$_adapter->fetchAll($select);
    }

    /**
     * Zápis poznámky _text pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @return void
     */
    public function zapisPriplatek()
    {
        // už je pro toto datum zapsaný teno typ příplatku?
        $existuje = $this->_overPriplatek();
        
        if ($existuje) {
            $this->_zmenaPriplatku();
        } else {
            // pokud je co zapsat, zapíšeme to
            if ($this->_hodnota > 0) {
                $this->_novyPriplatek();
            }
        }
    }
    
    /**
     * Ověření, že pro _datumSmeny, _osoba, _cip, _idPriplatku existuje zapsaný
     * příplatek
     * 
     * @rerurn boolean
     */
    private function _overPriplatek()
    {
        $select = self::$_adapter->select()
            ->from(array('pp' => 'oficialni_priplatky'),
                array('id'=>'id_zaznamu'))
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->where('id_priplatku = ?', $this->_idPriplatku);
        
        switch (count(self::$_adapter->fetchAll($select))) {
            case 0:  return false; break;
            default: return true;  break;
        }           
    }
    
    /**
     * Pro kombinaci _osoba, _cip, _datumuSmeny a idPriplatku v databázi změní
     * hodnotu příplatku na _hodnota
     * 
     * @return void
     */
    private function _zmenaPriplatku()
    {
        // null hodnota není prázdný řetězec
        switch (strlen($this->_hodnota)) {
            case 0:  $text = null;         break;
            default: $text = $this->_hodnota; break;
        }
        
        self::$_adapter->update(
            'oficialni_priplatky',
            array(
                'delka' => $this->_hodnota,
                'id_zmenil' => $this->_uzivatel
            ),
            array(
                'id_osoby = ?' => $this->_osoba,
                'id_cipu = ?' => $this->_cip,
                'datum = ?' => $this->_datumSmeny,
                'id_priplatku = ?' => $this->_idPriplatku
            )
        );        
    }

    /**
     * Do databáze uloží novoý příplatek _hodnota pro kombinaci _osoba, _cip,
     * _datumSmeny a _idPriplatku
     * 
     * @return void
     */
    private function _novyPriplatek()
    {
        self::$_adapter->insert( 'oficialni_priplatky', array(            
            'id_osoby' => $this->_osoba,
            'id_cipu' => $this->_cip,
            'id_priplatku' => $this->_idPriplatku,
            'datum' => $this->_datumSmeny,
            'delka' => $this->_hodnota,
            'id_zmenil' => $this->_uzivatel
        ));                       
    }

    public function getDatumSmeny() {
        return $this->_datumSmeny;
    }

    public function setDatumSmeny($datumSmeny) {
        $this->_datumSmeny = $datumSmeny;
    }

    public function getIdPriplatku() {
        return $this->_idPriplatku;
    }

    public function setIdPriplatku($idPriplatku) {
        $this->_idPriplatku = $idPriplatku;
    }

    public function getHodnota() {
        return $this->_hodnota;
    }

    public function setHodnota($hodnota) {
        $this->_hodnota = $hodnota;
    }    
}