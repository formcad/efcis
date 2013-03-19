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
     * @return array/null
     */
    public function getAkce() 
    {
        $akce = array();
        
        $select = self::$_adapter->select()
            ->from( array('o' => 'odpracovane_rezie'),
                    array('id'=>'id_zaznamu', 'timeStart'=>'time_start',
                        'timeEnd'=>'time_end', 'timeUpdate'=>'time_update',
                        'poznamka', 'idUpravil'=>'id_naposled_upravil'))                   
            ->join( array('t' => 'rezijni_technologie'),
                    't.id_operace = o.id_operace',
                    array('operace'=>'nazev_operace')) 
            ->joinLeft( array('os' => 'osoby'),
                    'o.id_naposled_upravil = os.id_osoby',
                    array('jmeno','prijmeni'))
            ->where( 'o.time_start >= ?', $this->_dateFrom)
            ->where( 'o.time_end <= ?', $this->_dateTo)
            ->where( 'o.id_osoby = ?', $this->_idUser)
            ->order( array('time_start'));        
                        
        $data = self::$_adapter->fetchAll($select);        
      
        if (empty($data)) {
            
            return null;
        }
        else {
            
            foreach ($data as $zaznam) {
                 
                if ($zaznam['timeUpdate'] !== null) {
                    $casUpdate = date('d. m. Y, H:i',strtotime($zaznam['timeUpdate']));
                } else {
                    $casUpdate = null;       
                }
                                
                if ($zaznam['idUpravil'] !== null) {
                    $zdrojDat  = $zaznam['jmeno'].' '.$zaznam['prijmeni'];           
                } else {
                    $zdrojDat = 'Čárový kód';             
                }
                
                $datum = date('d. m. Y', strtotime($zaznam['timeStart']));
                $trvani = strtotime($zaznam['timeEnd']) - strtotime($zaznam['timeStart']);
                $roundTrvani = round($trvani/3600, 2);
                
                $akce[] = array (
                    'id' => $zaznam['id'],
                    'casStart' => date('d. m. Y, H.i',strtotime($zaznam['timeStart'])),
                    'casEnd' => date('d. m. Y, H.i',strtotime($zaznam['timeEnd'])),
                    'casUpdate' => $casUpdate,
                    'datum' => $datum,
                    'timestampStart' => $zaznam['timeStart'],
                    'timestampEnd' => $zaznam['timeEnd'],
                    'zdrojDat' => $zdrojDat,
                    'trvani' => $roundTrvani,
                    'technologie' => $zaznam['operace'],
                    'poznamka' => $zaznam['poznamka']
                );
            }
            return $akce; 
        }
   
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
        $select = self::$_adapter->select();
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
        
        return self::$_adapter->fetchAll($select);
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

