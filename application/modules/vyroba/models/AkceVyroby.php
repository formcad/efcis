<?php

/**
 * Třída poskytuje přístup k datům o výrobě
 */
class Vyroba_Model_AkceVyroby extends Fc_Model_DatabaseAbstract
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
     * Filtr dat podle typu zápisu (rucni, automaticky, libovolny)
     * @var string 
     */    
    protected $_zapis = true;   
    
    /**
     * Pro daného uživatele vrátí pole rozpracovaných dílů
     * 
     * @return array 
     */
    public function getAktualniZaznamy() 
    {
        $poleZaznamu = array();
        
        $select = self::$_adapter->select()
            ->from( array('o' => 'operace'),
                    array('id_pozice','cas'))
            ->join( array('pz' => 'pozice'),
                    'o.id_pozice = pz.id_pozice',
                    array('nazev') )    
            ->join( array('pl' => 'polozky'),
                    'pz.id_polozky = pl.id_polozky',
                    array('cislo_zakazky') )        
            ->join( array('t' => 'technologie'),
                    't.cislo_technologie = o.cislo_technologie',
                    array('nazev_technologie') ) 
            ->where( 'o.id_osoby = ?', $this->_idUser)
            ->order( array('cas') );          
        
        $data = self::$_adapter->fetchAll($select);        
       
        if (!empty($data)) {
            foreach ($data as $zaznam) {

                $poleZaznamu[] = array(
                    'cisloZakazky' => $zaznam['cislo_zakazky'],
                    'nazevPozice' => $zaznam['nazev'],
                    'idPozice' => $zaznam['id_pozice'],
                    'technologie' => $zaznam['nazev_technologie'],
                    'casStart' => $zaznam['cas'],
                    'dateTimeStart' => date('d. m. Y H:i',strtotime($zaznam['cas']))
                );
            }
        }
        return $poleZaznamu;
    }
    
    /**
     * Získání výrobních akcí na základě vstupních kritérií
     * 
     * @return array
     */
    public function getAkce() 
    {
        $akce = array();
        
        $select = self::$_adapter->select()
            ->from( array('o' => 'odpracovane_casy'),
                    array('id'=>'id_zaznamu', 'timeStart'=>'time_start',
                        'timeEnd'=>'time_end', 'timeUpdate'=>'time_update',
                        'idUpravil'=>'id_naposled_upravil'))
            ->join( array('pz' => 'pozice'),
                    'o.id_pozice = pz.id_pozice',
                    array('idPozice'=>'id_pozice', 'nazevPozice'=>'nazev'))    
            ->join( array('pl' => 'polozky'),
                    'pz.id_polozky = pl.id_polozky',
                    array('cisloZakazky'=>'cislo_zakazky') )        
            ->join( array('t' => 'technologie'),
                    't.cislo_technologie = o.cislo_technologie',
                    array('technologie'=>'nazev_technologie') ) 
            ->join( array('tp' => 'typy_prace'),
                    'tp.id_typu = o.id_typu',
                    array('idPrace'=>'id_typu', 'zkratkaPrace'=>'zkratka'))
            ->joinLeft( array('os' => 'osoby'),
                        'o.id_naposled_upravil = os.id_osoby',
                        array('jmeno','prijmeni'))
            ->where( 'o.time_start >= ?', $this->_dateFrom)
            ->where( 'o.time_start <= ?', $this->_dateTo)
            ->where( 'o.id_osoby = ?', $this->_idUser)
            ->order( array('time_start') );        

        switch ($this->_zapis) {
            case 'rucni':
                $select->where('o.id_naposled_upravil IS NOT NULL');
                break;

            case 'automaticky':
                $select->where('o.id_naposled_upravil IS NULL');
                break;

            case 'libovolny':
                break;
        }       
                        
        $data = self::$_adapter->fetchAll($select);        
      
        if (!empty($data)) {
            
            foreach ($data as $zaznam) {
                
                if ($zaznam['timeUpdate'] !== null) {
                    $casUpdate = date('d. m. Y, H:i',strtotime($zaznam['timeUpdate']));
                } else {
                    $casUpdate = null;       
                }
                                
                if ($zaznam['idUpravil'] !== null) {
                    $zdrojDat  = $zaznam['jmeno'].' '.$zaznam['prijmeni'];
                    $tinyCasStart = '';
                } else {
                    $zdrojDat = 'Čárový kód';
                    $tinyCasStart = date('H.i',strtotime($zaznam['timeStart']));
                }
                
                if ($tinyCasStart == '00.00') {
                    $tinyCasStart = '';
                }                
                
                $trvani = strtotime($zaznam['timeEnd']) - strtotime($zaznam['timeStart']);
                $roundTrvani = round($trvani/3600, 2);
                
                $akce[] = array (
                    'id' => $zaznam['id'],
                    'casStart' => date('d. m. Y, H.i',strtotime($zaznam['timeStart'])),
                    'tinyCasStart' => $tinyCasStart,
                    'denStart' => date('d. m. Y',strtotime($zaznam['timeStart'])),
                    'casEnd' => date('d. m. Y, H.i',strtotime($zaznam['timeEnd'])),
                    'casUpdate' => $casUpdate,
                    'timestampStart' => $zaznam['timeStart'],
                    'timestampEnd' => $zaznam['timeEnd'],
                    'trvani' => $roundTrvani,
                    'typPrace' => $zaznam['zkratkaPrace'],
                    'idTypuPrace' => $zaznam['idPrace'],
                    'idPozice' => $zaznam['idPozice'],
                    'nazevPozcie' => $zaznam['nazevPozice'],
                    'cisloZakazky' => $zaznam['cisloZakazky'],
                    'technologie' => $zaznam['technologie'],
                    'zdrojDat' => $zdrojDat                    
                );                
            }
            return $akce; 
        }
        else 
            return null;        
    }
    
    /**
     * Zpracování pole výrobních akcí - výstupem je celkový součet práce
     * 
     * @param array $poleAkci 
     * @return float 
     */
    public function sumaVyrobnichAkci($poleAkci) 
    { 
        $time = 0;
        
        if (!empty($poleAkci)) {
            
            foreach ($poleAkci as $akce) {
            
                $time += strtotime($akce['timestampEnd']) - strtotime($akce['timestampStart']);                               
            }    
            $time = $time/3600;
        }
        return $time;        
    }
    
    /**
     * Z pole výrobních akcí jsou vybrány takové, které jsou prováděny na druhém
     * stroji a je vrácen jejich součet
     * 
     * @param array $poleAkci
     * @return float
     */
    public function sumaDvoustroje($poleAkci) {
        
        if (!empty($poleAkci)) {
            
            foreach ($poleAkci as $akce) {
                
                // jenom když jde o dvoustrj
                if ($akce['idTypuPrace'] == 2) {
                    $time += strtotime($akce['timestampEnd']) - strtotime($akce['timestampStart']);    
                }
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
        $select->from(array('c' => 'odpracovane_casy'),
                array('start' => 'time_start','end' => 'time_end', 
                    'idPozice' => 'id_pozice', 'idZaznamu' => 'id_zaznamu'))
            ->join(array('pz' => 'pozice'),
                'pz.id_pozice = c.id_pozice',
                array('nazevPozice' => 'nazev'))
            ->join(array('pl' => 'polozky'),
                'pl.id_polozky = pz.id_polozky',
                array('cisloZakazky' => 'cislo_zakazky'))
            ->join(array('t' => 'technologie'),
                't.cislo_technologie = c.cislo_technologie',
                array('technologie' => 'nazev_technologie'))
            ->join(array('uz' => 'osoby'),
                'uz.id_osoby = c.id_osoby',
                array('userJmeno' => 'jmeno', 'userPrijmeni' => 'prijmeni', 'userId' => 'id_osoby'))
            ->join(array('os' => 'osoby'),
                'os.id_osoby = c.id_naposled_upravil',
                array('zmenilJmeno' => 'jmeno', 'zmenilPrijmeni' => 'prijmeni'))
            ->order(array('c.id_zaznamu DESC'))
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

    public function getZapis() {
        return $this->_zapis;
    }

    public function setZapis($_zapis) {
        $this->_zapis = $_zapis;
    }

}

