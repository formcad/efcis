<?php

/**
 * Zákldní informace o oficiální docházce
 */
class Dochazka_Model_DochazkaOficialni extends Fc_Model_DatabaseAbstract
{
    /**
     * ID výkazu docházky
     * @var integer
     */
    protected $_idDochazky = null;
    
    /**
     * ID zaměstnance
     * @var integer 
     */
    protected $_osoba = null;
    
    /**
     * ID čipu
     * @var integer
     */
    protected $_cip = null;
    
    /**
     * ID osoby měnící a zapisujíc data
     * @var type 
     */
    protected $_uzivatel = null;

    /**
     * Časový údaj - měsíc
     * @var integer
     */
    protected $_mesic = null;
    
    /**
     * Časový údaj - rok
     * @var integer
     */
    protected $_rok = null;
    
    /**
     * Alternativa k měsíci a roku - datum Od
     * @var string
     */
    protected $_datumOd = null;
    
    /**
     * Alternativa k měsíci a roku - datum Do
     * @var string
     */
    protected $_datumDo = null;

    /**
     * Datová struktura používaná při automatickém zpracování nové docházky
     * @var array
     */
    protected $_novaOficialniData = array();

    /**
     * Ověří, zda už má zaměstnanec docházku pro konkrétní měsíc a rok
     * @return array 
     */
    public function overExistenci() 
    {
        $select = $this->_adapter->select()
            ->from('oficialni_dochazka',
                   array('id' => 'id_dochazky'))
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->where('mesic = ?', $this->_mesic)
            ->where('rok = ?', $this->_rok);
        
        // výsledek dotazu by měl být jenom jeden řádek nebo nic
        $vysledek = $this->_adapter->fetchAll($select);
        
        if (count($vysledek) > 0) {
            return true; 
        } else {
            return false;
        }
    }      
    
    /**
     * Pro kombinaci zaměstnance a čipu získá funkce všech zapsaných docházek
     * @return array
     */
    public function getOficialniDochazka()
    {
        $select = $this->_adapter->select()
            ->from(array('oficialni_dochazka'),
                array('id'=>'id_dochazky','mesic','rok'))
            ->where('id_osoby = ?', $this->_osoba)
            ->where('id_cipu = ?', $this->_cip)
            ->order(array('rok DESC', 'mesic'));
        
        return $this->_adapter->fetchAll($select);
    }

    /**
     * Založení záznamu o oficiální dozcházce, které vrátí ID nového řádku
     * @return integer ID vloženého řádku
     */
    public function zalozDochazku()
    {
        $this->_adapter->insert( 'oficialni_dochazka', array(
            'id_cipu' => $this->_cip,
            'id_osoby' => $this->_osoba,
            'mesic' => $this->_mesic,
            'rok' => $this->_rok
        ));  
        
        return $this->_adapter->lastInsertId('oficialni_dochazka','id_dochazky');
    }
    
    /**
     * Vytvoření vhodného tvaru z dat oficiální docházky pro uložení průchodů
     * v DB
     */
    public function ulozNoveOficialniPruchody()
    {
        foreach ($this->_novaOficialniData as $den) {
            
            foreach($den['polePruchodu'] as $pruchod) {
                $data = array(
                    'osoba' => $this->_osoba,
                    'cip' => $this->_cip,
                    'datum' => $den['datum'],
                    'prichod' => $pruchod['prichod'],
                    'odchod' => $pruchod['odchod'],
                    'zmenil' => $this->_uzivatel                   
                );
                $this->_sqlUlozOficialniPruchod($data);
            }            
        } 
    }
    
    /**
     * Funkce uloží do databáze jeden řádek s průchodem. Bohžel tedy
     * sekvenčně a ne dávkově
     * Čas změny a smazáno = false jsou výchozí hodnoty přímo v databázi
     * 
     * @param array $pruchod Hodnoty patrné z SQL dotazu
     */
    protected function _sqlUlozOficialniPruchod($pruchod)
    {       
        $this->_adapter->insert( 'oficialni_pruchody', array(
            'id_osoby' => $pruchod['osoba'],
            'id_cipu' => $pruchod['cip'],
            'datum' => $pruchod['datum'],
            'cas_prichod' => $pruchod['prichod'],
            'cas_odchod' => $pruchod['odchod'],
            'id_zmenil' => $pruchod['zmenil']
        ));                      
    }
    
    /**
     * Vytvoření vhodného tvaru z dat oficiální docházky pro uložení přerušení
     * v DB
     */    
    public function ulozNoveOficialniPreruseni()
    {
        foreach ($this->_novaOficialniData as $den) {
                      
            if ($den['sumaPreruseni'] > 0) {
                $data = array(
                    'idPreruseni' => 1,
                    'osoba' => $this->_osoba,
                    'cip' => $this->_cip,
                    'datum' => $den['datum'],
                    'delka' => $den['sumaPreruseni'],          
                    'zmenil' => $this->_uzivatel                   
                );
                $this->_sqlUlozOficialniPreruseni($data);
            }
        }         
    }
    
    /**
     * Funkce uloží do databáze jeden řádek s přerušením docházky. Bohžel tedy
     * sekvenčně a ne dávkově
     * Čas změny a smazáno = false jsou výchozí hodnoty přímo v databázi
     * 
     * @param array $preruseni Hodnoty patrné z SQL dotazu
     */    
    protected function _sqlUlozOficialniPreruseni($preruseni)    
    {
        $this->_adapter->insert( 'oficialni_preruseni', array(
            'id_preruseni' => $preruseni['idPreruseni'],
            'id_osoby' => $preruseni['osoba'],
            'id_cipu' => $preruseni['cip'],
            'datum' => $preruseni['datum'],
            'delka' => $preruseni['delka'],
            'id_zmenil' => $preruseni['zmenil']
        ));             
    }
    
    /**
     * Vytvoření vhodného tvaru z dat oficiální docházky pro uložení příplatků
     * v DB
     */    
    public function ulozNoveOficialniPriplatky()    
    {
        foreach ($this->_novaOficialniData as $den) {
                      
            if (!empty($den['sumaPriplatku'])) {
                foreach ($den['sumaPriplatku'] as $idPriplatku => $priplatek) {

                    $data = array(
                        'idPriplatku' => $idPriplatku,
                        'osoba' => $this->_osoba,
                        'cip' => $this->_cip,
                        'datum' => $den['datum'],
                        'delka' => $priplatek,          
                        'zmenil' => $this->_uzivatel                   
                    );
                    $this->_sqlUlozOficialniPriplatek($data); 
                }
            }
        }          
    }
    
    /**
     * Funkce uloží do databáze jeden řádek s příplatkem. Bohžel tedy
     * sekvenčně a ne dávkově
     * Čas změny a smazáno = false jsou výchozí hodnoty přímo v databázi
     * 
     * @param array $priplatek Hodnoty patrné z SQL dotazu
     */  
    protected function _sqlUlozOficialniPriplatek($priplatek)    
    {
        $this->_adapter->insert( 'oficialni_priplatky', array(
            'id_priplatku' => $priplatek['idPriplatku'],
            'id_osoby' => $priplatek['osoba'],
            'id_cipu' => $priplatek['cip'],
            'datum' => $priplatek['datum'],
            'delka' => $priplatek['delka'],
            'id_zmenil' => $priplatek['zmenil']
        ));             
    }
    
    /**
     * Na základě konkrétního ID docházky získá její časový rozsah
     * @return array
     */
    public function getRozsahDochazky()
    {
        $select = $this->_adapter->select()
            ->from(array('oficialni_dochazka'),
                array('mesic','rok'))
            ->where('id_dochazky = ?', $this->_idDochazky);
        
        return $this->_adapter->fetchAll($select);
    }
    
    /**
     * Získání pole oficiální docházky
     * @return array
     */
    public function getAkce()
    {
        $result = array();
        
        $kalendarInstance = new Application_Model_Kalendar();
        $kalendarInstance->setDateFrom($this->_datumOd);
        $kalendarInstance->setDateTo($this->_datumDo);
    
        $kalendar = $kalendarInstance->getKalendar();
        $pruchody = $this->_getOficialniPruchody();
        $preruseni = $this->_getOficialniPreruseni();
        $priplatky = $this->_getOficialniPriplatky();          
        $poznamky = $this->_getOficialniPoznamky();
        
        foreach ($kalendar as $den)
        {
            $polePruchodu = array();            
            foreach ($pruchody as $index => $akce) {
                if ($akce['datum'] == $den['datum']) {
                    
                    // upravíme časové údaje do čitelnějších podob
                    $prichod = strtotime($akce['cas_prichod']);
                    $akce['casPrichod'] = date('d. m. y, H.i', $prichod);
                    $akce['timestampPrichod'] = date('Y-m-d, H.i', $prichod);
                    $akce['dbTimestampPrichod'] = date('Y-m-d H:i:s', $prichod);
                    
                    // upravíme časové údaje do čitelnějších podob
                    $odchod = strtotime($akce['cas_prichod']);
                    $akce['casPrichod'] = date('d. m. y, H.i', $odchod);
                    $akce['timestampPrichod'] = date('Y-m-d, H.i', $odchod);
                    $akce['dbTimestampPrichod'] = date('Y-m-d H:i:s', $odchod);                    
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
            
            $polePoznamek = array();            
            foreach ($poznamky as $index => $akce) {
                if ($akce['datum'] == $den['datum']) {
                    $polePoznamek[] = $akce;
                    unset($poznamky[$index]);
                }
            }            
    
            $result[] = array(
                'datum' => $den['datum'],
                'svatek' => $den['svatek'],
                'pruchody' => $polePruchodu,
                'preruseni' => $polePreruseni,
                'priplatky' => $polePriplatku,
                'poznamka' => $polePoznamek
            );            
        }
        return $result;
    }
    
    /**
     * Pomocná funkce, která provede SQL dotaz a vrátí oficiální průchody
     * docházky z databáze
     * @return array
     */
    protected function _getOficialniPruchody()
    {
        $select = $this->_adapter->select()
            ->from( array('pr' => 'oficialni_pruchody'),
                    array('id' => 'id_zaznamu', 'prichod' => 'cas_prichod',
                        'odchod' => 'cas_odchod') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = pr.datum',
                    array('datum') )    
            ->where( 'k.datum >= ?', $this->_datumOd)
            ->where( 'k.datum <= ?', $this->_datumDo)
            ->where( 'pr.smazano IS FALSE' )
            ->where( 'pr.id_cipu = ?', $this->_cip )
            ->where( 'pr.id_osoby = ?', $this->_osoba )
            ->order( array('k.datum', 'pr.cas_prichod') );          
        
        return $this->_adapter->fetchAll($select);          
    }
    
    /**
     * Pomocná funkce, která provede SQL dotaz a vrátí oficiální přerušení
     * docházky z databáze
     * @return array
     */
    protected function _getOficialniPreruseni()
    {
        $select = $this->_adapter->select()
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
        
        return $this->_adapter->fetchAll($select);           
    }
    
    /**
     * Pomocná funkce, která provede SQL dotaz a vrátí oficiální příplatky
     * docházky z databáze
     * @return array
     */
    protected function _getOficialniPriplatky()
    {
        $select = $this->_adapter->select()
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
        
        return $this->_adapter->fetchAll($select);         
    }
    
    /**
     * Pomocná funkce, která provede SQL dotaz a vrátí poznámky k oficiální
     * docházce v databázi
     * @return array
     */
    protected function _getOficialniPoznamky()
    {
        $select = $this->_adapter->select()
            ->from( array('po' => 'oficialni_poznamky'),
                    array('id' => 'id_zaznamu', 'text') )
            ->join( array('k' => 'kalendar'),
                    'k.datum = po.datum',
                    array('datum') )
            ->where( 'k.datum >= ?', $this->_datumOd)
            ->where( 'k.datum <= ?', $this->_datumDo)            
            ->where( 'po.id_cipu = ?', $this->_cip )
            ->where( 'po.id_osoby = ?', $this->_osoba )
            ->order( array('k.datum') );          
        
        return $this->_adapter->fetchAll($select);            
    }

    public function getIdDochazky() {
        return $this->_idDochazky;
    }

    public function setIdDochazky($idDochazky) {
        $this->_idDochazky = $idDochazky;
    }

    public function getOsoba() {
        return $this->_osoba;
    }

    public function setOsoba($osoba) {
        $this->_osoba = $osoba;
    }

    public function getCip() {
        return $this->_cip;
    }

    public function setCip($cip) {
        $this->_cip = $cip;
    }

    public function getUzivatel() {
        return $this->_uzivatel;
    }

    public function setUzivatel($uzivatel) {
        $this->_uzivatel = $uzivatel;
    }

    public function getMesic() {
        return $this->_mesic;
    }

    public function setMesic($mesic) {
        $this->_mesic = $mesic;
    }

    public function getRok() {
        return $this->_rok;
    }

    public function setRok($rok) {
        $this->_rok = $rok;
    }

    public function getDatumOd() {
        return $this->_datumOd;
    }

    public function setDatumOd($datumOd) {
        $this->_datumOd = $datumOd;
    }

    public function getDatumDo() {
        return $this->_datumDo;
    }

    public function setDatumDo($datumDo) {
        $this->_datumDo = $datumDo;
    }

    public function getNovaOficialniData() {
        return $this->_novaOficialniData;
    }

    public function setNovaOficialniData($novaOficialniData) {
        $this->_novaOficialniData = $novaOficialniData;
    } 
    
}
