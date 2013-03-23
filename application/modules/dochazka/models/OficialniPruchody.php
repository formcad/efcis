<?php

/**
 * Třída průchodů oficiální docházky
 */
class Dochazka_Model_OficialniPruchody extends Dochazka_Model_OficialniAbstract
{        
    /**
     * ID konkrétního záznamu v tabulce oficialni_pruchody
     * @var Integer
     */
    protected $_idZaznamu = null;
    
    /**
     * Datum a čas příchodu
     * @var String
     */
    protected $_casPrichod = null;
    
    /**
     * Datum a čas odchodu
     * @var String
     */
    protected $_casOdchod = null;
        
    /**
     * Pole o prvcích casOd, casDo, casCil, které slouží pro zaokrouhlování
     * časů příchodů
     * @var Array
     */
    protected $_zaokrouhleni = array();
    
    /**
     * Funkce uloží do databáze jeden řádek s průchodem. Čas změny a 
     * smazáno = false jsou výchozí hodnoty přímo v databázi
     * 
     * @param String $prichod Čas příchodu
     * @param String $oddchod Čas odchodu
     * @return Void
     */
    public function novyPruchod($prichod, $odchod)
    {
        // umožníme kontrolu parametrů
        $this->setCasPrichod($prichod);
        $this->setCasOdchod($odchod);
        
        self::$_adapter->insert( 'oficialni_pruchody', array(
            'id_osoby' => $this->_osoba,
            'id_cipu' => $this->_cip,
            'datum' => $this->_datumSmeny,
            'cas_prichod' => $this->_casPrichod,
            'cas_odchod' => $this->_casOdchod,
            'id_zmenil' => $this->_uzivatel
        ));                                             
    }
    
    /**
     * Výběr příchodů docházky mezi limitními daty (v proměnných _datumOd a
     * _datumDo)
     * 
     * @param String $od Datum, od kterého se vybírají příchody
     * @param String $do Datum, do kterého se vybírají příchody
     * @return Array
     */    
    protected function _ziskejPrichody($od, $do)
    {
        $select = self::$_adapter->select()
            ->from( array('op'=>'oficialni_pruchody'),
                    array('prichod'=>'cas_prichod','id'=>'id_zaznamu'))
            ->where( 'op.datum >= ?', $od)
            ->where( 'op.datum <= ?', $do);       
        
        return self::$_adapter->fetchAll($select);              
    }
    
    /**
     * Provede SQL dotaz a vrátí oficiální průchody docházky z databáze podle
     * nastavených kritérií
     * 
     * @param String $od Počáteční datum rozsahu
     * @param String $do Koncové datum rozsahu
     * @return Array
     */    
    public function ziskejPruchody($od, $do)
    {
        // umožníme kontrolu parametrů
        $this->setDatumOd($od);
        $this->setDatumDo($do);
        
        $select = self::$_adapter->select()
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
        
        return self::$_adapter->fetchAll($select);         
    }
    
    /**
     * Z databáze vybere řádek s id, které se rovná _idZaznamu
     * 
     * @param  Integer $id Id hledaného záznamu
     * @return Array
     */    
    public function ziskejPruchod($id)
    {
        // umožníme kontrolu parametru
        $this->setIdZaznamu($id);
        
        $select = self::$_adapter->select()
            ->from(array('pr' => 'oficialni_pruchody'),
                array('prichod'=>'cas_prichod','odchod'=>'cas_odchod','datum',
                    'id'=>'id_zaznamu'))
            ->where('id_zaznamu = ?',$this->_idZaznamu);
        
        $data = self::$_adapter->fetchRow($select);
        $data['datumSmeny'] = date('d. m. Y',strtotime($data['datum']));
        $data['prichodDen'] = date('d. m. Y',strtotime($data['prichod']));
        $data['odchodDen']  = date('d. m. Y',strtotime($data['odchod']));
        $data['prichodCas'] = date('H:i',strtotime($data['prichod']));
        $data['odchodCas']  = date('H:i',strtotime($data['odchod']));
        
        return $data;        
    }
    
    /**
     * Pomocná funkce provádějící změnu konkrétního času příchodu oficiální 
     * docházky
     * 
     * @param  Integer $id      Id hledaného záznamu
     * @param  String  $prichod Platné datum a čas příchodu 
     * @return Void
     */
    protected function _zmenPrichod($id, $prichod) 
    { 
        self::$_adapter->update(
            'oficialni_pruchody',
            array('cas_prichod' => $prichod),
            array('id_zaznamu = ?' => $id)
        );
    }    
    
    /**
     * V tabulce oficialni_pruchody změní celý řádek se zadaným _idZaznamu
     * 
     * @param  Integer $id         Id měněného záznamu
     * @param  String  $casPrichod Datum a čas příchodu
     * @param  String  $casOdchod  Datum a čas odchodu
     * @return Void
     */
    public function zmenPruchod($id, $casPrichod, $casOdchod)
    {
        // umožníme kontrolu parametrů
        $this->setIdZaznamu($id);
        $this->setCasPrichod($casPrichod);
        $this->setCasOdchod($casOdchod);
        
        self::$_adapter->update(
            'oficialni_pruchody',
            array(
                'cas_prichod' => $this->_casPrichod,
                'cas_odchod' => $this->_casOdchod,
                'datum' => $this->_datumSmeny,
                'id_zmenil' => $this->_uzivatel,
            ),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
            )
        );        
    }
    
    /**
     * V tabulce oficialni_pruchody změní řádek se zadaným _idZaznamu - nastaví
     * příznak smazano na hodnotu TRUE
     * 
     * @param  Integer $id Id mazaného záznamu
     * @return Void
     */
    public function smazPruchod($id)
    {
        // umožníme kontrolu parametru
        $this->setIdZaznamu($id);
        
        self::$_adapter->update(
            'oficialni_pruchody',
            array(
                'smazano' => true,
                'id_zmenil' => $this->_uzivatel,
            ),
            array(
                'id_zaznamu = ?' => $this->_idZaznamu
            )
        );
    }    
    
    /**
     * Projede časy oficiální docházky mezi _datumOd a _datumDo, přičemž
     * příchody mezi _zaokrouhleni['casOd'] a _zaokrouhleni['casDo'] změní na 
     * hodnotu _zaokrouhleni['casCil']
     * 
     * @param  Array $zaokrouhleni Hodnoty zaokrouhlení
     * @return Void
     */    
    public function zaokrouhliPrichody($zaokrouhleni)
    {  
        // umožníme kontrolu parametru
        $this->setZaokrouhleni($zaokrouhleni);
        
        // získáme pole příchodů
        $polePrichodu = $this->_ziskejPrichody($this->_datumOd,$this->_datumDo);    

        // příchody vyhovující některému z nastavených kritérií změníme
        foreach ($polePrichodu as $pruchod) {
            
            $datumPrichod = date('Y-m-d',strtotime($pruchod['prichod']));
            $timePrichod = strtotime($pruchod['prichod']);
            
            $updateOd = strtotime($datumPrichod.' '.$this->_zaokrouhleni['casOd']);
            $updateDo = strtotime($datumPrichod.' '.$this->_zaokrouhleni['casDo']);
                
            // pokud je čas příchodu mezi limity pro změnu
            if ($timePrichod >= $updateOd and $timePrichod <= $updateDo) {

                // čas změníme                
                $this->_zmenPrichod($pruchod['id'], $datumPrichod.' '.$this->_zaokrouhleni['casCil']);
            }                                            
        }         
    }    

    public function getIdZaznamu() {
        return $this->_idZaznamu;
    }

    public function setIdZaznamu($idZaznamu) {
        $this->_idZaznamu = $idZaznamu;
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

    public function getZaokrouhleni() {
        return $this->_zaokrouhleni;
    }

    public function setZaokrouhleni($zaokrouhleni) {
        $this->_zaokrouhleni = $zaokrouhleni;
    }

}