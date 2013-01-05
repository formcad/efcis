<?php

/**
 * Třída poskytuje přístup k datům o výrobě
 */
class Vyroba_Model_ZaznamyVyroby extends Fc_Model_DatabaseAbstract
{
    /**
     * ID záznamu
     * @var integer
     */
    protected $_idZaznamu = null;
    
    /**
     * ID pozice
     * @var integer 
     */
    protected $_idPozice = null;

    /**
     * ID operace (číslo technologie)
     * @var integer
     */
    protected $_idOperace = null;
    
    /**
     * ID karty (výroba nebo druhý stroj)
     * @var integer
     */
    protected $_idKarty = null;
    
    /**
     * ID člověka, který vykonal práci
     * @var integer 
     */
    protected $_idZamestnance = null;
            
    /**
     * ID člověka, který vytváří záznam
     * @var integer 
     */
    protected $_idUzivatele = null;
    
    /**
     * Čas zahájení práce
     * @var string
     */
    protected $_timeStart = null;
    
    /**
     * Čas ukončení práce
     * @var string
     */
    protected $_timeEnd = null;
    
    /**
     * Čas změny záznamu
     * @var string
     */
    protected $_timeUpdate = null;

    /**
     * SQL dotaz pro uložení nového záznamu do tabulky odpracovaných časů
     */
    public function ulozZaznam()
    {
        $this->_adapter->insert('odpracovane_casy',array(
            'id_pozice' => $this->_idPozice,
            'cislo_technologie' => $this->_idOperace,
            'id_typu' => $this->_idKarty,
            'time_start' => $this->_timeStart,
            'time_end' => $this->_timeEnd,
            'id_osoby' => $this->_idZamestnance,
            'id_naposled_upravil' => $this->_idUzivatele
        ));
    }    
    
    /**
     * SQL dotaz pro změnu konkrétního záznamu v tabulce odpracovaných časů
     */
    public function zmenZaznam()
    {
        $this->_adapter->update('odpracovane_casy',array(
            'id_pozice' => $this->_idPozice,
            'cislo_technologie' => $this->_idOperace,
            'id_typu' => $this->_idKarty,
            'time_start' => $this->_timeStart,
            'time_end' => $this->_timeEnd,
            'id_osoby' => $this->_idZamestnance,
            'id_naposled_upravil' => $this->_idUzivatele
        ), array( 'id_zaznamu = ?' => $this->_idZaznamu ));
    }        
    
    /**
     * SQL dotaz pro získání konkrétního řádku z tabulky odpracovaných časů
     * 
     * @return array 
     * @throws Exception
     */
    public function getZaznam()
    {
        if ($this->_idZaznamu == null) {
            throw new Exception('Není zadáno ID záznamu');
        } else {            
            $select = $this->_adapter->select()
                ->from(array('vyr'=>'odpracovane_casy'),
                    array('idZaznamu'=>'id_zaznamu', 'idPozice'=>'id_pozice',
                        'idOsoby'=>'id_osoby','karta'=>'id_typu',
                        'start'=>'time_start','end'=>'time_end',
                        'operace'=>'cislo_technologie'))
                ->join(array('io'=>'identifikace_osoby'),
                    'io.id_osoby = vyr.id_osoby',
                    array('vyrobniKarta'=>'hodnota'))
                ->where('io.id_typu = vyr.id_typu')
                ->where('vyr.id_zaznamu = ?', $this->_idZaznamu);
            
            return $this->_adapter->fetchRow($select);
        }
    }
    
    public function setIdZaznamu($idZaznamu) {
        $this->_idZaznamu = $idZaznamu;
    }

    public function setIdPozice($idPozice) {
        $this->_idPozice = $idPozice;
    }

    public function setIdOperace($idOperace) {
        $this->_idOperace = $idOperace;
    }

    public function setIdKarty($idKarty) {
        $this->_idKarty = $idKarty;
    }

    public function setIdZamestnance($idZamestnance) {
        $this->_idZamestnance = $idZamestnance;
    }

    public function setIdUzivatele($idUzivatele) {
        $this->_idUzivatele = $idUzivatele;
    }

    public function setTimeStart($timeStart) {
        $this->_timeStart = $timeStart;
    }

    public function setTimeEnd($timeEnd) {
        $this->_timeEnd = $timeEnd;
    }

    public function setTimeUpdate($timeUpdate) {
        $this->_timeUpdate = $timeUpdate;
    }

}

