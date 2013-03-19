<?php

/**
 * Třída poskytuje přístup k datům o režiích
 */
class Vyroba_Model_ZaznamyRezie extends Fc_Model_DatabaseAbstract
{
    /**
     * ID záznamu
     * @var integer
     */
    protected $_idZaznamu = null;
    
    /**
     * ID operace (číslo technologie)
     * @var integer
     */
    protected $_idOperace = null;
    
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
     * Poznámka k režii
     * @var string
     */
    protected $_poznamka = null;

    /**
     * SQL dotaz pro uložení nového záznamu do tabulky odpracovaných režijních časů
     */    
    public function ulozZaznam()
    {
        self::$_adapter->insert('odpracovane_rezie',array(
            'id_operace' => $this->_idOperace,            
            'time_start' => $this->_timeStart,
            'time_end' => $this->_timeEnd,
            'time_update' => $this->_timeUpdate,
            'id_osoby' => $this->_idZamestnance,
            'id_naposled_upravil' => $this->_idUzivatele,
            'poznamka' => $this->_poznamka
        ));
    }    
    
    /**
     * SQL dotaz pro změnu záznamu v tabulce režijních časů
     */    
    public function zmenZaznam()
    {
        self::$_adapter->update('odpracovane_rezie',array(
            'id_operace' => $this->_idOperace,            
            'time_start' => $this->_timeStart,
            'time_end' => $this->_timeEnd,
            'time_update' => $this->_timeUpdate,
            'id_osoby' => $this->_idZamestnance,
            'id_naposled_upravil' => $this->_idUzivatele,
            'poznamka' => $this->_poznamka
        ), array( 'id_zaznamu = ?' => $this->_idZaznamu ));
    }      
    
    /**
     * SQL dotaz pro získání konkrétního řádku z tabulky odpracovaných režijních časů
     * 
     * @return array 
     * @throws Exception
     */
    public function getZaznam()
    {
        if ($this->_idZaznamu == null) {
            throw new Exception('Není zadáno ID záznamu');
        } else {            
            $select = self::$_adapter->select()
                ->from(array('rez'=>'odpracovane_rezie'),
                    array('idZaznamu'=>'id_zaznamu','idOsoby'=>'id_osoby',
                        'start'=>'time_start','end'=>'time_end',
                        'operace'=>'id_operace','poznamka'))
                ->join(array('io'=>'identifikace_osoby'),
                    'io.id_osoby = rez.id_osoby',
                    array('rezijniKarta'=>'hodnota'))
                ->where('io.id_typu = 3')
                ->where('rez.id_zaznamu = ?', $this->_idZaznamu);
 
            return self::$_adapter->fetchRow($select);
        }
    }    
 
    public function setIdZaznamu($idZaznamu) {
        $this->_idZaznamu = $idZaznamu;
    }

    public function setIdOperace($idOperace) {
        $this->_idOperace = $idOperace;
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

    public function setPoznamka($poznamka) {
        $this->_poznamka = $poznamka;
    }
}

