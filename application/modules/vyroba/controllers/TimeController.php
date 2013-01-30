<?php

/**
 * Controller pro výrobní a režijní časy
 */


class Vyroba_TimeController extends Zend_Controller_Action
{

    /**
     * Session namespace formcad
     * @var
     */
    private static $_session = null;

    /**
     * Uložení identity uživatele
     * @var
     */
    private static $_identity = null;

    public function init()
    {
        self::$_session = new Zend_Session_Namespace('formcad');
        self::$_identity = Zend_Auth::getInstance()->getIdentity();     
    }

    public function indexAction()
    {
        
    }

    /**
     * Zobrazení výrobních směn mezi vybranými daty pro jednoho člověka. Funguje
     * pouze pro uzavřené výrobní bloky.
     */
    public function showSmenyAction()
    {
        $url = $this->_helper->url;
        
        // pokud je zaměstnanec v default modulu employee, skočí zpět na svou 
        // domovskou stránku
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index')),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );               
        }

        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->poleZaznamu = $this->_showAction();
    }

    /**
     * Zobrazení výrobních a režijních prací člověka ve vybraném období.
     * Funguje
     * pouze pro uzavřené výrobní bloky. Aktuální práci (v neuzavřeném
     * výrobním
     * bloku) řeší jiná funkce.
     */
    public function showPraceAction()
    {
        $poleZaznamu = $this->_showAction();

        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->poleZaznamu = $poleZaznamu;        
        
        // pokud je zaměstnanec v default modulu employee, skočí zpět na svou 
        // domovskou stránku
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index')),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );               
        }
    }

    /**
     * Zobrazení režie, výroby a docházky právě probíhající směny pro
     * vybraného
     * zaměstnance 
     */
    public function showAktualniAction()
    {
        /**** VÝJIMKY *********************************************************/
        
        // v session musí být potřebné údaje
        if (!isset(self::$_session->idOsoby)) {            
            throw new Exception('Chyba v nastavení session');
        }
        
        /**** ZÁKLADNÍ NASTAVENÍ **********************************************/

        $vyroba = new Vyroba_Model_AkceVyroby();
        $vyroba->setIdUser(self::$_session->idOsoby);        
        
        $rezie = new Vyroba_Model_AkceRezie();    
        $rezie->setIdUser(self::$_session->idOsoby);
        
        $dochazka = new Dochazka_Model_AkceDochazky();        
        $dochazka->setIdUser(self::$_session->idOsoby);        
        
        /**** PŘÍCHOD DO PRÁCE A DEN SMĚNY ************************************/
        
        $poleDochazky = $this->_ziskejAktualniDochazku();
        $casPrichodu = $poleDochazky['casPrichodu'];
        $denSmeny = $poleDochazky['denSmeny'];             

        /**** DATA O VÝROBĚ ***************************************************/
    
        // pokud uživatel není v práci, nemá smysl pokračovat
        if ($casPrichodu == null) {
            
            $poleZaznamu = null;
            $poleAktualni = null;      
        }
        else {            
            
            /**** ČASOVÁ SESSION **********************************************/

            // přes session se předají parametry dalším funkcím     
            $od = self::$_session->od;
            $do = self::$_session->do;        
            
            self::$_session->od = $denSmeny;
            self::$_session->do = $denSmeny;
            
            /**** POLE ULOŽENÝCH ZÁZNAMŮ **************************************/

            // zde jsou také všechny ručně zapsané záznamy a režijní záznamy
            $poleZaznamu = $this->_showAction();  
            
            // k celkovému času docházky musíme připočítat čas neuložené docházky
            $pracovniBlok = (time()-strtotime($casPrichodu))/3600;  
            $poleZaznamu[0]['sumaDochazky'] += round($pracovniBlok,2);
            

            /**** POLE AKTUÁLNÍCH ZÁZNAMŮ *************************************/
            
            $vyroba->setDateFrom($casPrichodu);
            $vyroba->setDateTo(date('Y-m-d H:i:s')); 
            $vyroba->setZapis('automaticky');
        
            $poleAktualni = $vyroba->getAkce();
                      
            // aktuální záznamy roztřídíme na standardní a dvoustrojové
            $poleStandardni = array();            
            $poleDvoustroj = array();
            
            if (!empty($poleAktualni)) {
                foreach ($poleAktualni as $zaznam) {
                    switch ($zaznam['idTypuPrace']) {
                        case 1: $poleStandardni[] = $zaznam; break;
                        case 2: $poleDvoustroj[] = $zaznam;  break;
                    }                
                }
                $sumaStandardni = $vyroba->sumaVyrobnichAkci($poleStandardni);
                $sumaDvoustroj  = $vyroba->sumaVyrobnichAkci($poleDvoustroj);                                    
            } 
            else {
                $sumaStandardni = 0;
                $sumaDvoustroj  = 0;                                   
            }
            $sumaAktualni = $sumaStandardni + $sumaDvoustroj;       

            // k celkovým výrobním časům musíme připočítat časy za dobu, kdy 
            // není uložená docházka
            $poleZaznamu[0]['sumaVyroby'] += round($sumaAktualni,2);            
            $poleZaznamu[0]['sumaPrace']  += round($sumaAktualni,2);                        
            $poleZaznamu[0]['sumaStandardni'] += round($sumaStandardni,2);
            $poleZaznamu[0]['sumaDvoustroj']  += round($sumaDvoustroj,2);
            
        }
                
        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        
        // pokud je zaměstnanec v default modulu employee, skočí zpět na svou 
        // domovskou stránku
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index')),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );               
        }

        $this->view->uzivatel = self::$_session->uzivatel;   
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->poleZaznamu = $poleZaznamu;
        $this->view->poleAktualni = $poleAktualni;
        
        $this->view->casPrichodu = date('d. m. y, H.i', strtotime($casPrichodu));
        $this->view->casNyni = date('d. m. y, H.i');
        
        /**** OBNOVENÍ SESSION ************************************************/
        
        self::$_session->od = $od;
        self::$_session->do = $do;
    }

    /**
     * Zobrazení kontrolního přehledu výrobních časů
     */
    public function showKontrolaVyrobyAction()
    {         
        $vyroba = new Vyroba_Model_AkceVyroby;
        $vyroba->setIdUser(self::$_session->idOsoby);
        $vyroba->setDateFrom(date('Y-m-d 0:00:00',strtotime(self::$_session->od)));
        $vyroba->setDateTo(date('Y-m-d 23:59:59',strtotime(self::$_session->do)));
        $vyroba->setZapis('libovolny');
        
        $poleZaznamu = $vyroba->getAkce();
                
        $url = $this->_helper->url;
        
        // pokud je zaměstnanec v default modulu employee, skočí zpět na svou 
        // domovskou stránku
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index')),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );               
        }

        $this->view->uzivatel = self::$_session->uzivatel;        
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->poleZaznamu = $poleZaznamu;
    }    
    
    /**
     * Zobrazení kontrolního přehledu režijních časů
     */
    public function showKontrolaRezieAction()
    {         
        $rezie = new Vyroba_Model_AkceRezie();
        $rezie->setIdUser(self::$_session->idOsoby);
        $rezie->setDateFrom(date('Y-m-d 0:00:00',strtotime(self::$_session->od)));
        $rezie->setDateTo(date('Y-m-d 23:59:59',strtotime(self::$_session->do)));
        
        $poleZaznamu = $rezie->getAkce();
                
        $url = $this->_helper->url;
        
        // pokud je zaměstnanec v default modulu employee, skočí zpět na svou 
        // domovskou stránku
        if (self::$_identity->roles['default'] == 'employee') {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'home.png',
                    'url' => $url->url(array('module' => 'default',
                                             'controller' => 'employee',
                                             'action' => 'index')),
                    'text' => 'Domů')
            );             
        } 
        // jinak se skočí na docházku
        else {
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );               
        }

        $this->view->uzivatel = self::$_session->uzivatel;        
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->poleZaznamu = $poleZaznamu;
    }     
    
    /**
     * Zobrazení součtů výrobních operací mezi vybranými daty pro jednoho
     * člověka
     */
    public function showOperaceAction()
    {
        // action body
    }

    /**
     * Provádí zpracování dat pro zobrazení směn nebo práce. De facto zabraňuje
     * redundanci zdrojového kódu
     */
    protected function _showAction()
    {
        /**** VÝJIMKY *********************************************************/
        
        // v session musí být potřebné údaje
        if (!isset(self::$_session->idOsoby)) {            
            throw new Exception('Chyba v nastavení session');
        }
        
        /**** ZÁZNAMY DOCHÁZKY ************************************************/
        
        $poleZaznamu = $this->_ziskejUlozenouDochazku();

        // kontrola integrity
        $souborIntegrity = $this->_helper->integrityCheck($poleZaznamu);
        
        // uložení záznamu o integritě do výsledného pole
        $integritniPoleZaznamu = $this->_helper->integrityAdd($poleZaznamu, $souborIntegrity);
        
        // přidání zkratky dne a formátované verze data do výsledného pole
        $datumovePoleZaznamu = $this->_helper->dateAdd($integritniPoleZaznamu);
             
        // přidání součtu časů docházky, pauzy a přerušení
        $souctovePoleZaznamu = $this->_helper->timeSum($datumovePoleZaznamu);

        // výsledné pole se záznamy
        $kompletniPoleZaznamu = $this->_zpracujPoleZaznamu($souctovePoleZaznamu);        
        
        return $kompletniPoleZaznamu;
    }    
    
    /**
     * Část funkčnosti showAktualniAction, aby se to tam příliš nepletlo. Pro
     * daného uživatele získá datum dne směny a také příchod do práce,
     * případně
     * vrátí pole s null hodnotami, pokud uživatel není zrovna v práci
     *
     * @return array
     */
    protected function _ziskejAktualniDochazku()
    {            
        $modelPruchodu = new Dochazka_Model_TerminalovyPruchod();
        $modelPruchodu->setIdOsoby(self::$_session->idOsoby);
        
        // první čip
        $modelPruchodu->setIdCipu(1);
        $tempPrichodA = $modelPruchodu->vyberTempPrichod();
        $permPrichodA = $modelPruchodu->vyberPermPrichod();

        // druhý čip
        $modelPruchodu->setIdCipu(2);
        $tempPrichodB = $modelPruchodu->vyberTempPrichod();
        $permPrichodB = $modelPruchodu->vyberPermPrichod();
 
        // pokud jsou perm příchody v budoucnu, nebereme je v úvahu
        if ($permPrichodA !== null) {
            if (strtotime($permPrichodA['cas_akce']) > time()) {
                $permPrichodA = null;
            } 
        }
        if ($permPrichodB !== null) {
            if (strtotime($permPrichodB['cas_akce']) > time()) {
                $permPrichodB = null;
            }
        }
        
        // pokud existuje zároveň perm příchod A a perm příchod B, vybereme starší
        if ($permPrichodA !== null and $permPrichodB !== null) {
            $casA = strtotime($permPrichodA['cas_akce']);
            $casB = strtotime($permPrichodB['cas_akce']);
            
            if ($casA > $casB) {
                $casPrichodu = $permPrichodB['cas_akce'];
                $denSmeny = $permPrichodB['datum'];
            } else {
                $casPrichodu = $permPrichodA['cas_akce'];
                $denSmeny = $permPrichodA['datum'];               // tady asi byla chyba v base
            }
        }
        // jinak jestli existuje čas příchodu A, je časem příchodu
        elseif ($permPrichodA !== null) {
            $casPrichodu = $permPrichodA['cas_akce'];
            $denSmeny = $permPrichodA['datum'];
        }
        // jinak jestli existuje čas příchodu B, je časem příchodu
        elseif ($permPrichodB !== null) {
            $casPrichodu = $permPrichodB['cas_akce'];
            $denSmeny = $permPrichodB['datum'];
        }
        // jinak jestli existuje zároveň temp příchod A a tem příchod B, vybereme starší
        elseif ($tempPrichodA !== null and $tempPrichodB !== null) {
            $casA = strtotime($tempPrichodA['cas_akce']);
            $casB = strtotime($tempPrichodB['cas_akce']);
            
            if ($casA > $casB) {
                $casPrichodu = $tempPrichodB['cas_akce'];
                $denSmeny = date('Y-m-d',strtotime($tempPrichodB['cas_akce']));
            } else {
                $casPrichodu = $tempPrichodA['cas_akce'];
                $denSmeny = date('Y-m-d',strtotime($tempPrichodA['cas_akce']));
            }
        }
        // jinak jestli existuje temp příchod A, je časem příchodu 
        elseif ($tempPrichodA !== null) {
            $casPrichodu = $tempPrichodA['cas_akce'];
            $denSmeny = date('Y-m-d',strtotime($tempPrichodA['cas_akce']));
        }
        // jinak jestli existuje temp příchod B, je časem příchodu 
        elseif ($tempPrichodB !== null) {
            $casPrichodu = $tempPrichodB['cas_akce'];
            $denSmeny = date('Y-m-d',strtotime($tempPrichodB['cas_akce']));
        }
        // jinak uživatel není v práci
        else {
            $casPrichodu = null;
            $denSmeny = null;
        }
        
        return array('casPrichodu'=>$casPrichodu, 'denSmeny'=>$denSmeny);
    }

    /**
     * Funkce zpracovávající pole práce - přidání výrobních a režijních časů
     *
     * @param array $polePrace 
     * @return array
     */
    protected function _zpracujPoleZaznamu($polePrace)
    {              
        /**** ZÁKLADNÍ NASTAVENÍ **********************************************/
        
        $vyroba = new Vyroba_Model_AkceVyroby();
        $vyroba->setIdUser(self::$_session->idOsoby);
        
        $rezie = new Vyroba_Model_AkceRezie();    
        $rezie->setIdUser(self::$_session->idOsoby);        

        /**** ZPRACOVNÁNÍ POLE PRÁCE ******************************************/
        
        if (!empty($polePrace)) {
            
            // pro každný den 
            foreach ($polePrace as $index => $den) {            
                
                $i = 0;
                $sumaVyroby = 0;
                $sumaDvoustroj = 0;
     
                /**** VÝROBNÍ ČASY ********************************************/
                
                // za předpokladu integritních časů v daném dni projdeme záznamy o docházce
                if ($den['timeIntegrity']) {
     
                    $poleCipovaneVyroby = array();
                    $vysledneCipovanePole = array();
                    $vyroba->setZapis('automaticky');                   
                    
                    for ($i == 0; $i < count($den['pruchody']); $i = $i+2) {                       
                        
                        // vyhledáme související výrobní záznamy
                        $vyroba->setDateFrom($den['pruchody'][$i]['dbTimestamp']);
                        $vyroba->setDateTo($den['pruchody'][$i+1]['dbTimestamp']);                       
                        
                        $poleCipovaneVyroby = $vyroba->getAkce();
                        $sumaVyroby += $vyroba->sumaVyrobnichAkci($poleCipovaneVyroby);
                        $sumaDvoustroj += $vyroba->sumaDvoustroje($poleCipovaneVyroby);
                        
                        if(is_array($poleCipovaneVyroby)) {
                            $vysledneCipovanePole = array_merge($vysledneCipovanePole,$poleCipovaneVyroby);
                        }
                    }
                }
                
                // přidáme ručně zapsané záznamy
                $vyroba->setDateFrom($den['datum'].' 00:00:00');
                $vyroba->setDateTo($den['datum'].' 23:59:59'); 
                $vyroba->setZapis('rucni');       

                $poleDopsaneVyroby  = $vyroba->getAkce();
                $sumaVyroby += $vyroba->sumaVyrobnichAkci($poleDopsaneVyroby);            
                $sumaDvoustroj += $vyroba->sumaDvoustroje($poleDopsaneVyroby);
                
                /**** REŽIJNÍ ČASY ********************************************/
                
                $rezie->setDateFrom($den['datum'].' 00:00:00');
                $rezie->setDateTo($den['datum'].' 23:59:59');                 
                
                $poleRezie = $rezie->getAkce(); 
                $sumaRezie = $rezie->sumaRezijnichAkci($poleRezie);
    
                $polePrace[$index]['sumaVyroby'] = round($sumaVyroby,2);
                $polePrace[$index]['sumaRezie'] = round($sumaRezie,2);
                $polePrace[$index]['sumaStandardni'] = round($sumaVyroby-$sumaDvoustroj,2);
                $polePrace[$index]['sumaDvoustroj'] = round($sumaDvoustroj,2);
                $polePrace[$index]['sumaPrace'] = round($sumaVyroby + $sumaRezie,2);
                $polePrace[$index]['poleCipovanePrace'] = $vysledneCipovanePole;
                $polePrace[$index]['poleDopsanePrace'] = $poleDopsaneVyroby;
                $polePrace[$index]['poleRezie'] = $poleRezie;
            }
        }     
        return $polePrace; 
    }

    /**
     * Získání zázanmů o docházce
     *
     * Pro minimalizaci chyb se nejprve vyberou záznamy pro jeden čip, následně
     * pro druhý čip a potom se takto získaná pole spojí v jednu sadu záznamů.
     * Tímto postupem se například vyřeší možná nekonzistence dat, když je 
     * nejprve na druhém čipu zaznamenán příchod a teprve potom je na prvním
     * zaznamenán odchod. Funguje to díky skutečnosti, že obě pole mají stejný
     * počet prvků. 
     *
     * @return array
     */
    protected function _ziskejUlozenouDochazku()
    {        
        // vstupní parametry
        $dochazka = new Dochazka_Model_AkceDochazky();        
        $dochazka->setIdUser(self::$_session->idOsoby);
        $dochazka->setDateFrom(self::$_session->od);
        $dochazka->setDateTo(self::$_session->do);       
          
        // docházka na prvním čipu
        $dochazka->setIdChip(1);
        $polePrace = $dochazka->getAkce();

        // docházka na druhém čipu
        $dochazka->setIdChip(2);
        $polePrescasu = $dochazka->getAkce();
  
        // spojení záznamů (průchody a přerušení) z obou čipů
        if (!empty($polePrace)) {            
            foreach ($polePrace as $key => $zaznamPrace) {
               
                if (!empty($polePrescasu[$key]['pruchody'])) {
                    foreach($polePrescasu[$key]['pruchody'] as $pruchod) {
                    
                        $polePrace[$key]['pruchody'][] = $pruchod;
                    }
                }
                if (!empty($polePrescasu[$key]['preruseni'])) {
                    foreach($polePrescasu[$key]['preruseni'] as $preruseni) {
                    
                        $polePrace[$key]['preruseni'][] = $preruseni;
                    }
                }
            }
        }   
        return $polePrace;
    }

}
