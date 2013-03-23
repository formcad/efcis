<?php

/**
 * Třída poznámek oficiální docházky
 */
class Dochazka_Model_OficialniPoznamky extends Dochazka_Model_OficialniAbstract
{        
    /**
     * Text poznámky
     * @var String
     */
    protected $_text = null;
      
    /**
     * Pro kombinaci _osoba, _cip a _datumSmeny získá z oficiální docházky
     * zapsanou poznámku
     * 
     * @return Null|String
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
     * @param  String $text Text poznámky
     * @return Void
     */
    public function zapisPoznamku($text)
    {
        // umožníme kontrolu parametru
        $this->setText($text);        
     
        // už je pro toto datum zapsaná poznámka?
        $existuje = $this->_overPoznamku();
     
        if ($existuje) {
            $this->_zmenaPoznamky($this->_text);
        } else {
            $this->_novaPozamka($this->_text);
        }
    }
    
    /**
     * Ověření, že pro _datumSmeny, _osoba, _cip existuje zapsaná poznámka
     * 
     * @rerurn Boolean
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
     * @param String $text Text poznámky
     * @return Void
     */
    private function _zmenaPoznamky($text)
    {
        // null hodnota není prázdný řetězec
        if (strlen($text) == 0) { $text = null; }
       
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
     * @param String $text Text poznámky
     * @return Void
     */
    private function _novaPozamka($text)
    {
        // zapisujeme pouze pokud to má cenu
        if (strlen($text) > 0) {
            
            self::$_adapter->insert( 'oficialni_poznamky', array(            
                'id_osoby' => $this->_osoba,
                'id_cipu' => $this->_cip,
                'datum' => $this->_datumSmeny,
                'text' => $text,
                'id_zmenil' => $this->_uzivatel
            ));                
        }
    }

    /**
     * SQL dotaz vrátí oficiální poznámky docházky mezi nastavenými časovými
     * hodnotami
     *
     * @param  String $od Počáteční datum
     * @param  String $do Koncové datum
     * @return Array 
     */    
    public function ziskejPoznamkyObdobi($od, $do)
    {
        // umožníme kontrolu parametrů
        $this->setDatumOd($od);
        $this->setDatumDo($do);           
        
        $select = self::$_adapter->select()
            ->from( array('po' => 'oficialni_poznamky'),
                    array('id' => 'id_zaznamu', 'text') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = po.datum',
                    array('datum') )
            ->where( 'k.datum >= ?', $od)
            ->where( 'k.datum <= ?', $do)            
            ->where( 'po.id_cipu = ?', $this->_cip )
            ->where( 'po.id_osoby = ?', $this->_osoba )
            ->order( array('k.datum') );          
        
        return self::$_adapter->fetchAll($select);        
    }
    
    public function getText() {
        return $this->_text;
    }

    public function setText($text) {
        $this->_text = $text;
    }
}