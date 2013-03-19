<?php

/**
 * Informace o poznámkách oficiální docházky
 */
class Dochazka_Model_OficialniPoznamky extends Dochazka_Model_DochazkaOficialni
{    
    /**
     * Datum směny
     * @var string
     */
    protected $_datumSmeny = null;    
    
    /**
     * Text poznámky
     * @var string
     */
    protected $_text = null;
     
    /**
     * Konctruktor třídy
     * 
     * @param integer $osoba
     * @param integer $cip
     * @param string  $datumSmeny
     * @param string|null  $text
     * @param ingeger|null $uzivatel     
     */
    function __construct($osoba, $cip, $uzivatel, $datumSmeny, $text = null) 
    {
        parent::__construct($osoba, $cip, $uzivatel);
        
        $this->setDatumSmeny($datumSmeny);
        $this->setText($text);            
    }
 
    /**
     * Pro kombinaci _osoba, _cip a _datumSmeny získá z oficiální docházky
     * zapsanou poznámku
     * 
     * @return null|string
     */
    public function ziskejPoznamku()
    {
        $select = self::$_adapter->select()
            ->from(array('po' => 'oficialni_poznamky'),
                array('poznamka'=>'text'))
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip);
        
        $data = self::$_adapter->fetchRow($select);
        return $data['poznamka'];
    }

    /**
     * Zápis poznámky _text pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @return void
     */
    public function zapisPoznamku()
    {
        // už je pro toto datum zapsaná poznámka?
        $existuje = $this->_overPoznamku();
        
        if ($existuje) {
            $this->_zmenaPoznamky();
        } else {
            $this->_novaPozamka();
        }
    }
    
    /**
     * Ověření, že pro _datumSmeny, _osoba, _cip existuje zapsaná poznámka
     * 
     * @rerurn boolean
     */
    private function _overPoznamku()
    {
        $select = self::$_adapter->select()
            ->from(array('po' => 'oficialni_poznamky'),
                array('id'=>'id_zaznamu'))
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip);
        
        switch (count(self::$_adapter->fetchAll($select))) {
            case 0:  return false; break;
            default: return true;  break;
        }           
    }
    
    /**
     * Pro kombinaci _osoba, _cip a _datumuSmeny v databázi změní text poznámky
     * na _text
     * 
     * @return void
     */
    private function _zmenaPoznamky()
    {
        // null hodnota není prázdný řetězec
        switch (strlen($this->_text)) {
            case 0:  $text = null;         break;
            default: $text = $this->_text; break;
        }
        
        self::$_adapter->update(
            'oficialni_poznamky',
            array(
                'text' => $text,
                'id_zmenil' => $this->_uzivatel
            ),
            array(
                'id_osoby = ?' => $this->_osoba,
                'id_cipu = ?' => $this->_cip,
                'datum = ?' => $this->_datumSmeny
            )
        );        
    }

    /**
     * Do databáze uloží novou poznámku _text pro kombinaci _osoby, _cip a
     * _datumSmeny
     * 
     * @return void
     */
    private function _novaPozamka()
    {
        // zapisujeme pouze pokud to má cenu
        if (strlen($this->_text) > 0) {
            
            self::$_adapter->insert( 'oficialni_poznamky', array(            
                'id_osoby' => $this->_osoba,
                'id_cipu' => $this->_cip,
                'datum' => $this->_datumSmeny,
                'text' => $this->_text,
                'id_zmenil' => $this->_uzivatel
            ));                
        }
    }

    public function getDatumSmeny() {
        return $this->_datumSmeny;
    }

    public function setDatumSmeny($datumSmeny) {
        $this->_datumSmeny = $datumSmeny;
    }

    public function getText() {
        return $this->_text;
    }

    public function setText($text) {
        $this->_text = $text;
    }
}