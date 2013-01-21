<?php

/**
 * Třída poskytuje přístup k datům o režiích
 */
class Vyroba_Model_AkceRezie extends Fc_Model_DatabaseAbstract
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
     * Získání režijních akcí na základě vstupních kritérií
     * 
     * @return array
     */
    public function getAkce() 
    {
        $akce = array();
        
        $select = $this->_adapter->select()
            ->from( array('o' => 'odpracovane_rezie'),
                    array('id_zaznamu','time_start','time_end','time_update','poznamka'))                   
            ->join( array('t' => 'rezijni_technologie'),
                    't.id_operace = o.id_operace',
                    array('nazev_operace') ) 
            ->joinLeft( array('os' => 'osoby'),
                        'o.id_naposled_upravil = os.id_osoby',
                        array('jmeno','prijmeni'))
            ->where( 'o.time_start >= ?', $this->_dateFrom)
            ->where( 'o.time_end <= ?', $this->_dateTo)
            ->where( 'o.id_osoby = ?', $this->_idUser)
            ->order( array('time_start') );        
                        
        $data = $this->_adapter->fetchAll($select);        
      
        if (!empty($data)) {
            
            foreach ($data as $zaznam) {
                 
                if ($zaznam['time_update'] !== null) {
                    $casUpdate = date('d. m. Y, H:i',strtotime($zaznam['time_update']));
                } else {
                    $casUpdate = null;       
                }
                                
                if ($zaznam['id_naposled_upravil'] !== null) {
                    $zdrojDat  = $zaznam['jmeno'];
                    $zdrojDat .= ' ';
                    $zdrojDat .= $zaznam['prijmeni'];             
                } else {
                    $zdrojDat = 'Čárový kód';             
                }
                
                $datum = date('d. m. Y', strtotime($zaznam['time_start']));
                $trvani = strtotime($zaznam['time_end']) - strtotime($zaznam['time_start']);
                $roundTrvani = round($trvani/3600, 2);
                
                $akce[] = array (
                    'id' => $zaznam['id_zaznamu'],
                    'casStart' => date('d. m. Y, H.i',strtotime($zaznam['time_start'])),
                    'casEnd' => date('d. m. Y, H.i',strtotime($zaznam['time_end'])),
                    'casUpdate' => $casUpdate,
                    'datum' => $datum,
                    'timestampStart' => $zaznam['time_start'],
                    'timestampEnd' => $zaznam['time_end'],
                    'zdrojDat' => $zdrojDat,
                    'trvani' => $roundTrvani,
                    'technologie' => $zaznam['nazev_operace'],
                    'poznamka' => $zaznam['poznamka']
                );
            }
            return $akce; 
        }
        else 
            return null;                 
    }    
    
    /**
     * Zpracování pole režijních akcí - výstupem je celkový součet režií
     * 
     * @param array $poleAkci 
     * @return float 
     */    
    public function sumaRezijnichAkci($poleAkci) {
        
        if (!empty($poleAkci)) {
            
            foreach ($poleAkci as $akce) {
                
                $time += strtotime($akce['timestampEnd']) - strtotime($akce['timestampStart']);                
            }    
            $time = $time/3600;
        }
        return $time;                    
    }

    /**
     * Z tabulky odpracovaných časů vybere posledních 10 ručně vložených záznamů
     * @return array
     */
    public function getPosledniZaznamy()
    {
        $select = $this->_adapter->select();
        $select->from(array('r' => 'odpracovane_rezie'),
                array('start' => 'time_start','end' => 'time_end', 
                    'idZaznamu' => 'id_zaznamu','poznamka'))           
            ->join(array('t' => 'rezijni_technologie'),
                't.id_operace = r.id_operace',
                array('technologie' => 'nazev_operace'))
            ->join(array('uz' => 'osoby'),
                'uz.id_osoby = r.id_osoby',
                array('userJmeno' => 'jmeno', 'userPrijmeni' => 'prijmeni'))
            ->join(array('os' => 'osoby'),
                'os.id_osoby = r.id_naposled_upravil',
                array('zmenilJmeno' => 'jmeno', 'zmenilPrijmeni' => 'prijmeni', 'userId' => 'id_osoby'))
            ->order(array('r.id_zaznamu DESC'))
            ->limit(10);
        
        return $this->_adapter->fetchAll($select);
    }
    
    public function getIdAction() {
        return $this->_idAction;
    }

    public function setIdAction($_idAction) {
        $this->_idAction = $_idAction;
    }

    public function getIdUser() {
        return $this->_idUser;
    }

    public function setIdUser($_idUser) {
        $this->_idUser = $_idUser;
    }

    public function getDateFrom() {
        return $this->_dateFrom;
    }

    public function setDateFrom($_dateFrom) {
        $this->_dateFrom = $_dateFrom;
    }

    public function getDateTo() {
        return $this->_dateTo;
    }

    public function setDateTo($_dateTo) {
        $this->_dateTo = $_dateTo;
    }
    
}

