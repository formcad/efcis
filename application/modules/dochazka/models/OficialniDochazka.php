<?php

/**
 * Třída, která tvoří nadstavbu nad oficiální docházkou - obsahuje funkce, které
 * se nedaly přiřadit do jednotlivých tříd oficiální docházky
 */
class Dochazka_Model_OficialniDochazka extends Fc_Model_DatabaseAbstract
{   
    /**
     * ID zaměstnance
     * @var Integer 
     */
    protected $_osoba = null; 
    
    /**
     * ID čipu
     * @var Integer
     */
    protected $_cip = null;
    
    /**
     * ID osoby měnící a zapisující data
     * @var Integer 
     */
    protected $_uzivatel = null;

    /**
     * Začátek časového rozsahu
     * @var String
     */
    protected $_datumOd = null;
    
    /**
     * Konec časového rozsahu
     * @var String
     */
    protected $_datumDo = null;    
    
    /**
     * Konstruktor třídy
     * 
     * @param Integer $osoba    ID zaměstnance
     * @param Integer $cip      ID čipu
     * @param Integer $uzivatel ID osoby měnící a zapisující data
     * @param String $datumOd   Začátek časového rozsahu
     * @param String $datumDo   Konec časového rozsahu 
     */
    function __construct($osoba, $cip, $uzivatel, $datumOd = null, $datumDo = null) 
    {
        // konstruktor Fc_Model_DatabaseAbstract zajistí DB adaptér
        parent::__construct();                
        
        $this->_osoba = $osoba;
        $this->_cip = $cip;
        $this->_uzivatel = $uzivatel;
        $this->_datumOd = $datumOd;
        $this->_datumDo = $datumDo;
    }


    
    /**
     * Získání pole oficiální docházky mezi časovými limity $this->_datumOda a 
     * $this->_datumDo
     * 
     * @return Array
     */
    public function getAkce()
    {
        $result = array();
        
        $kalendarInstance = new Application_Model_Kalendar($this->_datumOd,$this->_datumDo);
                
        $pruchodyInstance = new Dochazka_Model_OficialniPruchody($this->_osoba, 
            $this->_cip, $this->_uzivatel);
        
        $priplatkyInstance = new Dochazka_Model_OficialniPriplatky($this->_osoba, 
            $this->_cip, $this->_uzivatel);
        
        $preruseniInstance = new Dochazka_Model_OficialniPreruseni($this->_osoba, 
            $this->_cip, $this->_uzivatel);
        
        $poznamkyInstance = new Dochazka_Model_OficialniPoznamky($this->_osoba, 
            $this->_cip, $this->_uzivatel);
        
        $kalendar = $kalendarInstance->ziskejKalendar();
        $pruchody  = $pruchodyInstance->ziskejPruchody($this->_datumOd, $this->_datumDo);
        $preruseni = $preruseniInstance->ziskejPreruseniObdobi($this->_datumOd, $this->_datumDo);
        $priplatky = $priplatkyInstance->ziskejPriplatkyObdobi($this->_datumOd, $this->_datumDo);
        $poznamky  = $poznamkyInstance->ziskejPoznamkyObdobi($this->_datumOd, $this->_datumDo);
        
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
     * Pro nastavené období (_datumOd až _datumDo), id člověka (_osoba) a jeho 
     * konkrétní čip (_cip) vybere z oficiální docházky časy průchodů, spočítá
     * jejich rozdíl a v případě, že je průchodů za den víc, sečte všechny
     * průchody v dni do jedné sumy.
     * 
     * @return Array
     */
    public function sumaCasuDochazky()
    {
        $result = array();
       
        $kalendarInstance = new Application_Model_Kalendar(
                                            $this->_datumOd,$this->_datumDo);
    
        $pruchodyInstance = new Dochazka_Model_OficialniPruchody($this->_osoba, 
            $this->_cip, $this->_uzivatel);

        $preruseniInstance = new Dochazka_Model_OficialniPreruseni($this->_osoba, 
            $this->_cip, $this->_uzivatel);        
        
        $kalendar = $kalendarInstance->ziskejKalendar(); 
        $pruchody = $pruchodyInstance->ziskejPruchody($this->_datumOd,$this->_datumDo);
        $preruseni = $preruseniInstance->ziskejPreruseniObdobi($this->_datumOd,$this->_datumDo);
        
        foreach ($kalendar as $den)
        {
            $tempPruchody = 0;
            $maxPruchod = 0;
            
            foreach ($pruchody as $index => $zaznam) {
          
                if ($zaznam['datum'] == $den['datum']) {
               
                    if (!empty($zaznam['odchod'])) {
                        
                        $tempPruchod = strtotime($zaznam['odchod']) - strtotime($zaznam['prichod']);
                        $tempPruchody += $tempPruchod;
                        
                        // nastavíme nejdelší zaznamenaný průchod
                        if ($maxPruchod < $tempPruchod) {
                            $maxPruchod = $tempPruchod;
                        }
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
                'cistaDochazka' => round(($tempPruchody - $tempPreruseni)/3600,2),
                'nejdelsiPruchod' => round($maxPruchod/3600,2),
                'datum' => $den['datum']
            );
        }
        return $result;
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
    
}
