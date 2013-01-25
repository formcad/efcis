<?php

/**
 * Zákldní informace o oficiální docházce
 */
class Dochazka_Model_DochazkaOficialni extends Fc_Model_DatabaseAbstract
{
    /**
     * Pole průchodů v docházce používané při zaokrouhlování docházky
     * @var array 
     */
    protected static $_polePruchodu = array();
    
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
     * Proměnná pro nastavení počátečního času, který je použit při zaokrouhlení
     * docházky
     * @var string 
     */
    protected $_casOd = null;
    
    /**
     * Proměnná pro nastavení koncového času, který je použit při zaokrouhlení
     * docházky
     * @var string 
     */
    protected $_casDo = null;
    
    /**
     * Proměnná pro nastavení času, na který se zaokrouhluje docházka mezi
     * počátečním a koncovým časem     
     * @var string 
     */
    protected $_casCil = null;    
    
    /**
     * ID průchodu oficiální docházky
     * @var integer
     */
    protected $_idPruchodu = null;

    /**
     * Proměnná času příchodu použitá při změně záznamu oficiálního průchodu a
     * při zadávání nového průchodu
     * @var string
     */
    protected $_casPrichod = null;
    
    /**
     * Proměnná času odchodu použitá při změně záznamu oficiálního průchodu a
     * při zadávání nového průchodu
     * @var string
     */
    protected $_casOdchod = null;
    
    /**
     * Proměnná data směny použitá při změně záznamu oficiálního průchodu a
     * při zadávání nového průchodu
     * @var string
     */
    protected $_datumSmeny = null;

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
     * Zaslání dat _sqlUlozOficialniPruchod
     */
    public function ulozNovyOficialniPruchod()
    {
        $data = array(
            'osoba' => $this->_osoba,
            'cip' => $this->_cip,
            'datum' => $this->_datumSmeny,
            'prichod' => $this->_casPrichod,
            'odchod' => $this->_casOdchod,
            'zmenil' => $this->_uzivatel                   
        );
        $this->_sqlUlozOficialniPruchod($data);
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
        
        return $this->_adapter->fetchRow($select);
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
                    $prichod = strtotime($akce['prichod']);
                    $akce['casPrichod'] = date('d. m. y, H.i', $prichod);
                    $akce['timestampPrichod'] = date('Y-m-d, H.i', $prichod);
                    $akce['dbTimestampPrichod'] = date('Y-m-d H:i:s', $prichod);
                    $akce['shortCasPrichod'] = date('H:i', $prichod);
                    
                    // upravíme časové údaje do čitelnějších podob
                    $odchod = strtotime($akce['odchod']);
                    $akce['casOdchod'] = date('d. m. y, H.i', $odchod);
                    $akce['timestampOdchod'] = date('Y-m-d, H.i', $odchod);
                    $akce['dbTimestampOdchod'] = date('Y-m-d H:i:s', $odchod);         
                    $akce['shortCasOdchod'] = date('H:i', $odchod);
                    
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
                'datum' => date('d. m. y', strtotime($den['datum'])),
                'dbDatum' => $den['datum'],
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
     * Pro nastavené období, id člověka a jeho konkrétní čip vybere z oficiální
     * docházky časy průchodů, spočítá jejich rozdíl a v případě, že je průchodů
     * za den víc, sečte všechny průchody v dni do jedné sumy. Data funkce vrátí
     * @return array
     */
    public function sumaCasuDochazky()
    {
        $result = array();
        
        $kalendarInstance = new Application_Model_Kalendar();
        $kalendarInstance->setDateFrom($this->_datumOd);
        $kalendarInstance->setDateTo($this->_datumDo);
    
        $kalendar = $kalendarInstance->getKalendar();        
        $pruchody = $this->_getOficialniPruchody();
        $preruseni = $this->_getOficialniPreruseni();
        
        foreach ($kalendar as $den)
        {
            $tempPruchody = 0;
            foreach ($pruchody as $index => $zaznam) {
          
                if ($zaznam['datum'] == $den['datum']) {
               
                    if (!empty($zaznam['odchod'])) {
                        $tempPruchody += strtotime($zaznam['odchod']) - strtotime($zaznam['prichod']);
                    }
                    // ve zdrojovém poli dál tento záznam nebude potřeba
                    unset($pruchody[$index]);
                }                
            } 
            
            $tempPreruseni = 0;
            foreach ($preruseni as $index => $zaznam) {
           
                if ($zaznam['datum'] == $den['datum']) {                    
                    $tempPreruseni = $zaznam['delka']*3600;
                    unset($preruseni[$index]);
                }         
            }
                                    
            $result[] = array (
                'dochazka' => round($tempPruchody/3600,2),
                'pauza' => round($tempPreruseni/3600,2),
                'cistaDochazka' => round(($tempPruchody - $tempPreruseni)/3600,2)
            );
        }
        return json_encode($result);
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
    
    /**
     * Projede časy oficiální docházky mezi _datumOd a _datumDo, přičemž
     * příchody mezi _casOd a _casDo změní na hodnotu _casCil
     */
    public function zaokrouhliPrichodyDochazky()
    {
        // pokud nemáme uložené pole průchodů, získáme ho
        if (empty(self::$_polePruchodu)) {
            self::$_polePruchodu = $this->_vyberPrichodyDochazky();
        }
        
        // příchody vyhovující některému z nastavených kritérií změníme
        foreach (self::$_polePruchodu as $pruchod) {
            
            $datumPrichod = date('Y-m-d',strtotime($pruchod['prichod']));
            $timePrichod = strtotime($pruchod['prichod']);
            
            $updateOd = strtotime($datumPrichod.' '.$this->_casOd);
            $updateDo = strtotime($datumPrichod.' '.$this->_casDo);
                
            // pokud je čas příchodu mezi limity pro změnu
            if ($timePrichod >= $updateOd and $timePrichod <= $updateDo) {

                // čas změníme
                $this->_zmenCasPrichodu($pruchod['id'],$datumPrichod.' '.$this->_casCil);
            }                                            
        }
         
    }
    
    /**
     * Výběr příchodů docházky mezi limitními daty (v proměnných _datumOd a
     * _datumDo)
     * @return array
     */
    protected function _vyberPrichodyDochazky() 
    {
        $select = $this->_adapter->select()
            ->from( array('op'=>'oficialni_pruchody'),
                    array('prichod'=>'cas_prichod','id'=>'id_zaznamu'))
            ->where( 'op.datum >= ?', $this->_datumOd)
            ->where( 'op.datum <= ?', $this->_datumDo);       
        
        return $this->_adapter->fetchAll($select);             
    }
   
    /**
     * Pomocná funkce provádějící změnu konkrétního času příchodu oficiální 
     * docházky
     * @param integer $idZaznamu ID záznamu v tabulce oficialni_pruchody
     * @param string $casPrichodu Přesný datum a čas příchodu
     */
    protected function _zmenCasPrichodu($idZaznamu,$casPrichodu) 
    {
        $this->_adapter->update(
            'oficialni_pruchody',
            array('cas_prichod' => $casPrichodu),
            array('id_zaznamu = ?' => $idZaznamu)
        );
    }
    
    /**
     * Z databáze vybere řádek s id, které se rovná _idPruchodu
     * @return array
     */
    public function ziskejPruchod()
    {
        $select = $this->_adapter->select()
            ->from(array('pr' => 'oficialni_pruchody'),
                array('prichod'=>'cas_prichod','odchod'=>'cas_odchod','datum',
                    'id'=>'id_zaznamu'))
            ->where('id_zaznamu = ?',$this->_idPruchodu);
        
        $data = $this->_adapter->fetchRow($select);
        $data['datumSmeny'] = date('d. m. Y',strtotime($data['datum']));
        $data['prichodDen'] = date('d. m. Y',strtotime($data['prichod']));
        $data['odchodDen']  = date('d. m. Y',strtotime($data['odchod']));
        $data['prichodCas'] = date('H:i',strtotime($data['prichod']));
        $data['odchodCas']  = date('H:i',strtotime($data['odchod']));
        
        return $data;
    }
    
    /**
     * V tabulce oficialni_dochazka změní řádek se zadaným _idPruchodu
     */
    public function zmenZaznam()
    {
        $this->_adapter->update(
            'oficialni_pruchody',
            array(
                'cas_prichod' => $this->_casPrichod,
                'cas_odchod' => $this->_casOdchod,
                'datum' => $this->_datumSmeny,
                'id_zmenil' => $this->_uzivatel,
            ),
            array(
                'id_zaznamu = ?' => $this->_idPruchodu
            )
        );
    }

    /**
     * V tabulce oficialni_dochazka změní řádek se zadaným _idPruchodu - nastaví
     * příznak smazano na hodnotu TRUE
     */
    public function smazZaznam()
    {
        $this->_adapter->update(
            'oficialni_pruchody',
            array(
                'smazano' => true,
                'id_zmenil' => $this->_uzivatel,
            ),
            array(
                'id_zaznamu = ?' => $this->_idPruchodu
            )
        );
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

    public function getIdPruchodu() {
        return $this->_idPruchodu;
    }

    public function setIdPruchodu($idPruchodu) {
        $this->_idPruchodu = $idPruchodu;
    }
    
    public function getCasOd() {
        return $this->_casOd;
    }

    public function setCasOd($casOd) {
        $this->_casOd = $casOd;
    }

    public function getCasDo() {
        return $this->_casDo;
    }

    public function setCasDo($casDo) {
        $this->_casDo = $casDo;
    }

    public function getCasCil() {
        return $this->_casCil;
    }

    public function setCasCil($casCil) {
        $this->_casCil = $casCil;
    }    
    
    public function getCasPrichod() {
        return $this->_casPrichod;
    }

    public function setCasPrichod($casPrichod) {
        $this->_casPrichod = $casPrichod;
    }

    public function getCasOdchod() {
        return $this->_casOdchod;
    }

    public function setCasOdchod($casOdchod) {
        $this->_casOdchod = $casOdchod;
    }

    public function getDatumSmeny() {
        return $this->_datumSmeny;
    }

    public function setDatumSmeny($datumSmeny) {
        $this->_datumSmeny = $datumSmeny;
    }
    
}
