<?php

/**
 * Třída poskytuje přístup k datům o průchodech v docházce
 */
class Dochazka_Model_AkceDochazky extends Fc_Model_DatabaseAbstract
{      
    /**
     * ID akce
     * @var integer
     */
    protected $_idAction = null;
    
    /**
     * ID uživatele
     * @var integer 
     */
    protected $_idUser = null;
    
    /**
     * ID čipu
     * @var integer 
     */
    protected $_idChip = null;
    
    /**
     * Počáteční datum
     * @var string 
     */
    protected $_dateFrom = '2011-01-01';
    
    /**
     * Koncové datum
     * @var string 
     */
    protected $_dateTo = '2111-01-01';       
    
    /**
     * Načtení záznamů o průchodech, přerušeních a přípaltcích podle vybraných 
     * kritérií
     * Funkce filtruje nesmazaná data pro konkrétní čip uživatele.
     * 
     * @return array
     */    
    public function getAkce() 
    {                           
        $result = array();

        $kalendarInstance = new Application_Model_Kalendar($this->_dateFrom,$this->_dateTo);

        $kalendar = $kalendarInstance->ziskejKalendar();
        $pruchody = $this->_getPruchody();
        $preruseni = $this->_getPreruseni();
        $priplatky = $this->_getPriplatky();        
        
        foreach ($kalendar as $den)
        {
            $polePruchodu = array();            
            foreach ($pruchody as $index => $akce) {
                if ($akce['datum'] == $den['datum']) {
                    
                    // upravíme časový údaj do čitelnějších podob
                    $timestamp = strtotime($akce['cas_akce']);
                    $akce['casAkce'] = date('d. m. y, H.i', $timestamp);
                    $akce['timestamp'] = date('Y-m-d, H.i', $timestamp);
                    $akce['dbTimestamp'] = date('Y-m-d H:i:s', $timestamp);
                    
                    // zdrojový záznam přesuneme do výsledného pole
                    $polePruchodu[] = $akce;
                    
                    // ve zdrojovém poli dál tento záznam nebude potřeba
                    unset($pruchody[$index]);
                }
            }
            
            $polePreruseni = array();            
            foreach ($preruseni as $index => $akce) {
                if ($akce['datum'] == $den['datum']) {                    
                    $polePreruseni[] = $akce;
                    unset($preruseni[$index]);
                }
            }  
            
            $polePriplatku = array();            
            foreach ($priplatky as $index => $akce) {
                if ($akce['datum'] == $den['datum']) {
                    $polePriplatku[] = $akce;
                    unset($priplatky[$index]);
                }
            } 
    
            $result[] = array(
                'datum' => $den['datum'],
                'svatek' => $den['svatek'],
                'pruchody' => $polePruchodu,
                'preruseni' => $polePreruseni,
                'priplatky' => $polePriplatku                
            );
        }       
        return $result;
    }
   
    protected function _getPruchody()
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_pruchody'),
                    array('id' => 'id_zaznamu', 'cas_akce') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )    
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = pr.id_akce',
                    array('nazev' => 'nazev_akce', 'typ' => 'id_typu') )       
            ->where( 'k.datum >= ?', $this->_dateFrom)
            ->where( 'k.datum <= ?', $this->_dateTo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_idChip )
            ->where( 'pr.id_osoby = ?', $this->_idUser )
            ->order( array('k.datum', 'pr.cas_akce') );          
        
        return self::$_adapter->fetchAll($select);  
    }
    
    protected function _getPriplatky()
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_priplatky'),
                    array('id' => 'id_zaznamu', 'delka',
                        'idPriplatku' => 'id_priplatku') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )
            ->join( array('p' => 'priplatky'),
                    'p.id_priplatku = pr.id_priplatku',
                    array('nazev') )
            ->where( 'k.datum >= ?', $this->_dateFrom)
            ->where( 'k.datum <= ?', $this->_dateTo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_idChip )
            ->where( 'pr.id_osoby = ?', $this->_idUser )
            ->order( array('k.datum') );          
        
        return self::$_adapter->fetchAll($select);          
    }

    protected function _getPreruseni()
    {
        $select = self::$_adapter->select()
            ->from( array('pr' => 'dochazka_preruseni'),
                    array('id' => 'id_zaznamu', 'delka') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )  
            ->join( array('p' => 'preruseni'),
                    'p.id_preruseni = pr.id_preruseni',
                    array('nazev') )                
            ->where( 'k.datum >= ?', $this->_dateFrom)
            ->where( 'k.datum <= ?', $this->_dateTo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_idChip )
            ->where( 'pr.id_osoby = ?', $this->_idUser )
            ->order( array('k.datum') );          
        
        return self::$_adapter->fetchAll($select);          
    }

    /**
     * Získá podrobnosti akce, která má ID = $this->_idAction
     */
    public function getPodrobnostiAkce() 
    {
        if ($this->_idAction == null) {
            throw new Exception('Není nastaveno ID akce');
        }
        
        $select = self::$_adapter->select()
            ->from( 'dochazka',
                    array('nazev'=>'nazev_akce','idTypu'=>'id_typu'))
            ->where( 'id_akce = ?', $this->_idAction);
        
        return self::$_adapter->fetchRow($select);
    }
    
    /**
     * Pro kombinaci ID uživatele a ID čipu vypíše záznam z tabulky 
     * dochazka_temp. V této tabulce je pro jednu kombinaci uživatelského ID 
     * a ID čipu pouze jeden záznam.
     */
    public function getTempAkce()
    {
        $select = self::$_adapter->select()
            ->from( array('t' => 'dochazka_temp'),
                    array('id_zaznamu', 'cas_akce'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = t.id_akce',
                    array('nazev_akce') )                 
            ->where( 't.id_osoby = ?', $this->_idUser)
            ->where( 't.id_cipu = ?', $this->_idChip)
            ->order( array('cas_akce') );          
        
        $data = self::$_adapter->fetchRow($select);  
        
        if (!empty($data)) {
            
            $timestamp = strtotime($data['cas_akce']);
            return array(               
                'casAkce' => date('d. m. Y, H:i', $timestamp),
                'akce' => $data['nazev_akce']                
            );
        }
        else 
            return null;
    }

    /**
     * Pro kombinaci ID uživatele a ID čipu vypíše záznamy z tabulky 
     * dochazka_error
     */    
    public function getErrorAkce()
    {
        $result = null;
        
        $select = self::$_adapter->select()
            ->from( array('t' => 'dochazka_error'),
                    array('id_zaznamu', 'cas_akce'))
            ->join( array('d' => 'dochazka'),
                    'd.id_akce = t.id_akce',
                    array('nazev_akce') )                 
            ->where( 't.id_osoby = ?', $this->_idUser)
            ->where( 't.id_cipu = ?', $this->_idChip)
            ->where( 't.cas_akce > ?', $this->_dateFrom)
            ->where( 't.cas_akce < ?', $this->_dateTo)
            ->order( array('cas_akce') );          
        
        $data = self::$_adapter->fetchAll($select);  
        
        if (!empty($data)) {
            
            foreach ($data as $zaznam) {
                $timestamp = strtotime($zaznam['cas_akce']);
                $result[] = array(
                    'id' => $zaznam['id_zaznamu'],
                    'casAkce' => date('d. m. Y, H:i', $timestamp),
                    'akce' => $zaznam['nazev_akce']
                );
            }
        }        
        return $result;                  
    }

    /**
     * Pro danou kominaci ID uživatele a ID čipu smaže všechny záznamy v tabulce
     * dochazka_temp
     */
    public function deleteTempActions() {
        
        self::$_adapter->delete('dochazka_temp', array(
            'id_osoby = ?' => $this->_idUser,
            'id_cipu = ?' => $this->_idChip
        ));           
    }
      
    /**
     * Nastavení ID uživatele
     * @param integer $_idUser 
     */    
    public function setIdUser($_idUser) {
        $this->_idUser = $_idUser;
    }

    /**
     * Nastavení ID akce
     * @param integer $_idChip 
     */
    public function setIdAction($_idAction) {
        $this->_idAction = $_idAction;
    }    
    
    /**
     * Nastavení ID čipu
     * @param integer $_idChip 
     */
    public function setIdChip($_idChip) {
        $this->_idChip = $_idChip;
    }

    /**
     * Nastavení koncového data
     * @param string $_dateTo 
     */
    public function setDateTo($_dateTo) {
        $this->_dateTo = $_dateTo;
    }

    /**
     * Nastavení počátečního data
     * @param string $_dateFrom
     */
    public function setDateFrom($_dateFrom) {
        $this->_dateFrom = $_dateFrom;
    }     
    
}
