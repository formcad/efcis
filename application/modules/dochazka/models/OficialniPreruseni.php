<?php

/**
 * Třída přerušení oficiální docházky
 */
class Dochazka_Model_OficialniPreruseni extends Dochazka_Model_OficialniAbstract
{
    /**
     * ID typu přerušení
     * @var Integer     
     */
    protected $_idPreruseni = null;    
    
    /**
     * Číslená hodnota délky přerušení
     * @var Float
     */
    protected $_hodnota = null;     
    
    /**
     * Zápis pauzy pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @param  Integer $typ     Id typu přerušení
     * @param  Float   $hodnota Doba, kterou přerušení trvalo
     * @return Void
     */
    public function zapisPreruseni($typ, $hodnota)
    {
        // umožníme kontrolu parametrů
        $this->setIdPreruseni($typ);
        $this->setHodnota($hodnota);
        
        // zjištění, jestli je pauza zapsaná
        $idZaznamu = $this->_overPreruseni($this->_idPreruseni);
                
        // pauza není zapsaná - zapíšeme ji
        if (null == $idZaznamu) {
            $this->_novePreruseni($this->_idPreruseni,$this->_hodnota);
        }
        // pauza je zapsaná - upravíme ji
        else {
            $this->_zmenaPreruseni($idZaznamu,$this->_idPreruseni,$this->_hodnota);
        }                
    }
    
    /**
     * Ověření, že pro _datumSmeny, _osoba, _cip, _idPreruseni existuje zapsané
     * přerušení
     * 
     * @param  Integer $typ Id typu přerušení
     * @rerurn Array|Null
     */    
    protected function _overPreruseni($typ)
    {
        $select = self::$_adapter->select()
            ->from(array('pu'=>'oficialni_preruseni'),
                array('id'=>'id_zaznamu'))
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->where('datum = ?', $this->_datumSmeny)
            ->where('id_preruseni = ?', $typ);;
        
        $result = self::$_adapter->fetchRow($select);
        
        if (!empty($result)) {
            return $result;
        } else {
            return null;
        }        
    }

    /**
     * Zápis přerušení pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @param  Integer $typ     Id typu přerušení
     * @param  Float   $hodnota Doba, kterou přerušení trvalo
     * @return Void
     */
    protected function _novePreruseni($typ, $hodnota)
    {
        self::$_adapter->insert( 'oficialni_preruseni', array(
            'id_preruseni' => $typ,
            'id_osoby' => $this->_osoba,
            'id_cipu' => $this->_cip,
            'datum' => $this->_datumSmeny,
            'delka' => $hodnota,
            'id_zmenil' => $this->_uzivatel
        ));     
    }    
    
    /**
     * Zápis přerušení pro kombinaci _osoba, _cip a _datumSmeny do
     * oficiální docházky
     * 
     * @param  Integer $id      Id měněného záznamus
     * @param  Integer $typ     Id typu přerušení
     * @param  Float   $hodnota Doba, kterou přerušení trvalo
     * @return Void
     */
    protected function _zmenaPreruseni($id, $typ, $hodnota)
    {
        self::$_adapter->update(
            'oficialni_preruseni',
            array(
                'delka' => $hodnota,
                'id_preruseni' => $typ,
                'id_zmenil' => $this->_uzivatel,
            ),
            array(
                'id_zaznamu = ?' => $id
            )
        );          
    }       

    /**
     * SQL dotaz vrátí oficiální přerušení docházky mezi nastavenými časovými
     * hodnotami
     *
     * @param  String $od Počáteční datum
     * @param  String $do Koncové datum
     * @return Array 
     */
    public function ziskejPreruseniObdobi($od, $do)
    {
        // umožníme kontrolu parametrů
        $this->setDatumOd($od);
        $this->setDatumDo($do);         
        
        $select = self::$_adapter->select()
            ->from( array('pr' => 'oficialni_preruseni'),
                    array('id' => 'id_zaznamu', 'delka') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )  
            ->join( array('p' => 'preruseni'),
                    'p.id_preruseni = pr.id_preruseni',
                    array('nazev') )                
            ->where( 'k.datum >= ?', $this->_datumOd)
            ->where( 'k.datum <= ?', $this->_datumDo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_cip )
            ->where( 'pr.id_osoby = ?', $this->_osoba )
            ->order( array('k.datum') );          
        
        return self::$_adapter->fetchAll($select); 
    }    
    

    public function getIdPreruseni() {
        return $this->_idPreruseni;
    }

    public function setIdPreruseni($idPreruseni) {
        $this->_idPreruseni = $idPreruseni;
    }

    public function getHodnota() {
        return $this->_hodnota;
    }

    public function setHodnota($hodnota) {
        $this->_hodnota = $hodnota;
    }

}