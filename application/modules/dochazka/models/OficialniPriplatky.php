<?php

/**
 * Třída příplatků oficiální docházky
 */
class Dochazka_Model_OficialniPriplatky extends Dochazka_Model_OficialniAbstract
{
    /**
     * ID typu příplatku
     * @var Integer     
     */
    protected $_idPriplatku = null;
    
    /**
     * Číslená hodnota příplatku
     * @var Float
     */
    protected $_hodnota = null;

    /**
     * Pro kombinaci _osoba, _cip a _datumSmeny získá z oficiální docházky
     * zapsané příplatky
     * 
     * @return Array
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
     * Zápis příplatku pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @param  Integer $typ     Id typu příplatku
     * @param  Float   $hodnota Doba, za kterou je příplatek vyměřen
     * @return Void
     */
    public function zapisPriplatek($typ, $hodnota)
    {
        // umožníme kontrolu parametrů
        $this->setIdPriplatku($typ);
        $this->setHodnota($hodnota);
        
        // už je pro toto datum zapsaný teno typ příplatku?
        $existuje = $this->_overPriplatek($this->_idPriplatku);
        
        if ($existuje) {
            $this->_zmenaPriplatku($this->_idPriplatku,$this->_hodnota);
        } else {
            // pokud je co zapsat, zapíšeme to
            if ($this->_hodnota > 0) {
                $this->novyPriplatek($this->_idPriplatku,$this->_hodnota);
            }
        }
    }
    
    /**
     * Ověření, že pro _datumSmeny, _osoba, _cip, _idPriplatku existuje zapsaný
     * příplatek
     * 
     * @param  Integer $typ Id typu příplatku
     * @rerurn Boolean
     */
    private function _overPriplatek($typ)
    {
        $select = self::$_adapter->select()
            ->from(array('pp' => 'oficialni_priplatky'),
                array('id'=>'id_zaznamu'))
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->where('id_priplatku = ?', $typ);
        
        switch (count(self::$_adapter->fetchAll($select))) {
            case 0:  return false; break;
            default: return true;  break;
        }           
    }
    
    /**
     * Pro kombinaci _osoba, _cip, _datumuSmeny a idPriplatku v databázi změní
     * hodnotu příplatku na _hodnota
     *
     * @param  Integer $typ     Id typu příplatku
     * @param  Float   $hodnota Doba, za kterou je příplatek vyměřen
     * @return Void
     */
    private function _zmenaPriplatku($typ, $hodnota)
    {
        // null hodnota není prázdný řetězec
        switch (strlen($hodnota)) {
            case 0:  $text = null;     break;
            default: $text = $hodnota; break;
        }
        
        self::$_adapter->update(
            'oficialni_priplatky',
            array(
                'delka' => $hodnota,
                'id_zmenil' => $this->_uzivatel
            ),
            array(
                'id_osoby = ?' => $this->_osoba,
                'id_cipu = ?' => $this->_cip,
                'datum = ?' => $this->_datumSmeny,
                'id_priplatku = ?' => $typ
            )
        );        
    }

    /**
     * Do databáze uloží novoý příplatek _hodnota pro kombinaci _osoba, _cip,
     * _datumSmeny a _idPriplatku
     *     
     * @param  Integer $typ     Id typu příplatku
     * @param  Float   $hodnota Doba, za kterou je příplatek vyměřen
     * @return Void
     */ 
    public function novyPriplatek($typ, $hodnota)
    {
        // umožníme kontrolu parametrů
        $this->setIdPriplatku($typ);
        $this->setHodnota($hodnota);        
        
        self::$_adapter->insert( 'oficialni_priplatky', array(            
            'id_osoby' => $this->_osoba,
            'id_cipu' => $this->_cip,
            'id_priplatku' => $typ,
            'datum' => $this->_datumSmeny,
            'delka' => $hodnota,
            'id_zmenil' => $this->_uzivatel
        ));                       
    }

    /**
     * SQL dotaz vrátí oficiální příplatky docházky mezi nastavenými časovými
     * hodnotami
     *
     * @param  String $od Počáteční datum
     * @param  String $do Koncové datum
     * @return Array 
     */
    public function ziskejPriplatkyObdobi($od, $do)
    {
        // umožníme kontrolu parametrů
        $this->setDatumOd($od);
        $this->setDatumDo($do);         
        
        $select = self::$_adapter->select()
            ->from( array('pr' => 'oficialni_priplatky'),
                    array('id' => 'id_zaznamu', 'delka',
                        'idPriplatku' => 'id_priplatku') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )
            ->join( array('p' => 'priplatky'),
                    'p.id_priplatku = pr.id_priplatku',
                    array('nazev') )
            ->where( 'k.datum >= ?', $this->_datumOd)
            ->where( 'k.datum <= ?', $this->_datumDo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_cip )
            ->where( 'pr.id_osoby = ?', $this->_osoba )
            ->order( array('k.datum') );            
        
        return self::$_adapter->fetchAll($select); 
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