<?php

class Dochazka_OfficialController extends Zend_Controller_Action
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
    
    /**
     * Proměnná, kam se ukládá pauza vznikající při redkukci pole záznamů
     * příchodů a odchodů
     * @var float
     */
    private $_tempPreruseni;
    
    public function init()
    {
        self::$_session = new Zend_Session_Namespace('formcad');
        self::$_identity = Zend_Auth::getInstance()->getIdentity();     
    }
    
    public function indexAction()
    {
        // action body
    }

    public function novyVykazAction()
    {   
        // do view přidáme monthpicker JS
        $this->view->headScript()->appendFile(
            '/js/jquery/jquery-ui-monthpicker.js',
            'text/javascript'
        );        
                
        // měsíce, pro které lze vytvořit výkaz docházky
        $this->view->form = new Dochazka_Form_MesicOficialniDochazky();
        

        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );
        $this->view->uzivatel = self::$_session->uzivatel;  
    }

    public function tvorbaVykazuAction()
    {
        $datum = $this->getRequest()->getPost('mesicDochazky');
        $mesic = substr($datum,0,strpos($datum,'/'));
        $rok = substr($datum,strpos($datum,'/')+1);
        
        /*** VÝJIMKY **********************************************************/
        
        try {
            // zjistíme, zda pro tento měsíc už výkaz není vytvořen
            $dochazka = new Dochazka_Model_DochazkaOficialni();
            $dochazka->setOsoba(self::$_session->idOsoby);
            $dochazka->setCip(self::$_session->idCipu);
            $dochazka->setUzivatel(self::$_identity->id);
            $dochazka->setMesic($mesic);
            $dochazka->setRok($rok);

            // výjimka v případě, že už je pro měsíc vytvořená oficiální docházka
            if ($dochazka->overExistenci() == true) {
                throw new Exception('Pro daný měsíc je už oficiální docházka zaměstnance vytvořená');        
            }

            // podíváme se na chyby v docházce (tabulka chyb)        
            $akce = new Dochazka_Model_AkceDochazky();
            $akce->setIdUser(self::$_session->idOsoby);
            $akce->setIdChip(self::$_session->idCipu);   
            $akce->setDateFrom('01-'.$mesic.'-'.$rok.' 00:00:00');
            $akce->setDateTo(cal_days_in_month(CAL_GREGORIAN, $mesic, $rok).'-'.$mesic.'-'.$rok.' 23:59:59');
        
            $poleError = $akce->getErrorAkce(); 
            if (!empty($poleError)) {
                throw new Exception('V tabulce chyb jsou v daném měsíci zaznamenané chyby - nejprve je odstraňte'); 
            }

            // podíváme se na chyby do struktury dat
            $poleZaznamu = $akce->getAkce();

            if (count($poleZaznamu) > 0) {

                $souborIntegrity = $this->_helper->integrityCheck($poleZaznamu);            
                foreach ($souborIntegrity as $zaznam) {
                    // v případě, že je tam neintegritní vstup, jde o chybu
                    if ($zaznam == false) {
                        throw new Exception('V tabulce záznamů jsou v daném měsíci zaznamenané chyby (červené řádky) - nejprve je odstraňte'); 
                        break;
                    }
                }
            }
        }
        catch (Exception $e) {
            $this->view->exceptionMessage = $e->getMessage();            
        }
        
        /**** ZPRACOVÁNÍ DAT **************************************************/       

        $data = array();
        if (count($poleZaznamu) > 0) {
        foreach ($poleZaznamu as $index => $den) {
            
            /**** INICIALIZACE PROMĚNNÝCH *************************************/
            
            $data[$index]['polePruchodu'] = array();    // výsledné pole průchodů
            $tempPruchody = array();                    // průběžné pole průchodů
            $data[$index]['sumaPreruseni'] = 0;         // výsledná suma přerušení   
            $sumaPreruseni = 0;                         // průběžná suma přerušení        
            $this->_tempPreruseni = 0;                  // přenos přerušení z funce redukující docházku
            
            /**** PRŮCHODY ****************************************************/

            $pocetPruchodu = count($den['pruchody']);

            // nejprve si uděláme pole průchodů
            $i = 0;
            for ($i == 0; $i < $pocetPruchodu; $i = $i+2 ) {

                // za předpokladu, že záznam je více než 3 minuty ho budeme dál zpracovávat
                if (strtotime($den['pruchody'][$i+1]['timestamp']) - strtotime($den['pruchody'][$i]['timestamp']) > 180) {                    
                    $tempPruchody[] = array(
                        'prichod' => $den['pruchody'][$i]['dbTimestamp'],
                        'odchod' => $den['pruchody'][$i+1]['dbTimestamp']
                    );
                }                        
            }  
            
            // pokud je dvojic příchod-odchod víc, zkusíme jejich počet zredukovat
            if (count($tempPruchody) > 1) {
                do {                
                    $puvodniPocet = count($tempPruchody);
                    $tempPruchody = $this->_redukujPole($tempPruchody);
                    $novyPocet = count($tempPruchody);
                }
                while ($puvodniPocet > $novyPocet);               
            }
            
            // vzniklé výsledné pole si uložíme 
            foreach ($tempPruchody as $pruchod) {
                $data[$index]['polePruchodu'][] = array(
                    'prichod' => $pruchod['prichod'],
                    'odchod' => $pruchod['odchod']
                );
            }
            
            /**** PŘERUŠENÍ ***************************************************/
            
            foreach ($den['preruseni'] as $preruseni) {                        
                $sumaPreruseni += $preruseni['delka'];
            }    
            // k sumě přerušení přičteme přerušení vzniklá redukcí průchodů
            $sumaPreruseni += ($this->_tempPreruseni / 3600); 
   
            $data[$index]['sumaPreruseni'] = round($sumaPreruseni,2); 
            
            /**** PŘÍPLATKY ***************************************************/
            
            foreach ($den['priplatky'] as $priplatek) {
                
                $data[$index]['sumaPriplatku'][$priplatek['idPriplatku']] += $priplatek['delka'];              
            }
            
            /**** OSTATNÍ *****************************************************/
            
            $data[$index]['datum'] = $den['datum'];
        }
       
        /**** ULOŽENÍ DO DATABÁZE *********************************************/
        
        // zapíšeme do oficialni_dochazka založení docházky
        $idDochazky = $dochazka->zalozDochazku();        
        
        // do modelu přehrajeme data
        $dochazka->setNovaOficialniData($data);
        
        // uložíme oficiální průchody
        $dochazka->ulozNoveOficialniPruchody();
        
        // uložíme oficiální přerušení
        $dochazka->ulozNoveOficialniPreruseni();
        
        // uložíme oficiální příplatky
        $dochazka->ulozNoveOficialniPriplatky();
        
        // a přesměrujeme pryč
        $redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('Redirector');
        $redirector->gotoUrlAndExit('/dochazka/official/zmena-vykazu/id/'.$idDochazky);        
        
        }
        // jinak se vytvoří view 
        else {
        
            /**** DATA DO VIEW ****************************************************/

            $url = $this->_helper->url;
            $this->view->leftNavigation = array(
                array(
                    'img' => 'modul-dochazka.png',
                    'url' => $url->url(array('module' => 'dochazka',
                                             'controller' => 'show',
                                             'action' => 'index')),
                    'text' => 'Docházka')
            );
            $this->view->uzivatel = self::$_session->uzivatel;     
        
        }
    }
    
    public function zmenaVykazuAction()
    {
        // zjistíme ID výkazu
        $idVykazu = $this->getRequest()->getParam('id');
        
        // získáme rok a měsíc výkazu
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);
        $dochazkaOficialni->setIdDochazky($idVykazu);
        
        // měsíc a rok oficiální docházky
        $rozsah = $dochazkaOficialni->getRozsahDochazky();

        // nastavíme časové limitní hodnoty docházky
        $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
        $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

        // získáme pole oficiální docházky
        $poleZaznamu = $dochazkaOficialni->getAkce();
       
        // získáme typy příplatků
        $modelPriplatku = new Dochazka_Model_TypyPriplatku();       
        $modelPriplatku->setPlatnost($rozsah['rok'].'-'.$rozsah['mesic'].'-01');     

        /**** DATA DO VIEW ****************************************************/
        
        $this->view->data = $poleZaznamu;
        $this->view->idVykazu = $idVykazu;
        $this->view->typyPriplatku = $modelPriplatku->getTypy();  
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index'),null,true),
                'text' => 'Docházka')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;  
    }

    
    public function vyberVykazuAction()
    {
        $poleDochazky = array();
        
        $dochazka = new Dochazka_Model_DochazkaOficialni();
        $dochazka->setOsoba(self::$_session->idOsoby);
        $dochazka->setCip(self::$_session->idCipu);
        
        $poleZaznamu = $dochazka->getOficialniDochazka();
        
        // pole záznamů musíme přeskupit do jiné podoby
        foreach ($poleZaznamu as $zaznam) {
            $poleDochazky[$zaznam['rok']][] = array(
                'mesic' => $zaznam['mesic'].'/'.$zaznam['rok'],
                'id' => $zaznam['id']
            );
        }        
        $this->view->poleDochazky = $poleDochazky;
        
        /**** DATA DO VIEW ****************************************************/
 
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'show',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;          
    }
    
    
    
    /**
     * Relativně hloupá, ale užitečná funkce. Pokud rozdíl příchodu a odchodu
     * v jednotlivých záznamech vstupního pole je < 1 hodina, potom se tyto dva 
     * záznamy spojí. Funkce tak zredukuje jeden řádek pole záznamů. Aby byla
     * redukce úplná, je potřeba z Controlleru volat funkci dokud nebude počet
     * vstupních záznamů pole roven počtu výstupů z pole. Pauza, která vznikne
     * redukcí záznamů se ukládá do proměnné třídy, odkud k ní přistupuje
     * controller.
     * 
     * @param array $poleDochazky
     * @return array 
     */
    protected function _redukujPole($poleDochazky) 
    {
        $vyslednePole = array();              
        $hotovo = false;
        
        // pro každý řádek pole docházky
        $j = 0; 
        for ($j == 0; $j < count($poleDochazky); $j++ ) {
             
            $odchod = strtotime($poleDochazky[$j]['odchod']);
            $prichod = strtotime($poleDochazky[$j+1]['prichod']);          
            
            // řádek pole docházky kopírujeme rovnou do výsledného pole, když:
            // A. už se provedlo zredukování pole o jeden řádek
            // B. jsme v posledním cyklu a není tak možné určit příchod $j+1
            // C. když mezi příchodem a odchodem uplynula víc než hodina
            if ( $hotovo or $j == (count($poleDochazky)-1) or ($prichod - $odchod) > 3600 ) {
                $vyslednePole[] = $poleDochazky[$j];
            }            
            // pokud mezi odchodem a příchodem uplynula méně než hodina,
            // zaznamená se to jako pauza a docházka je o řádek redukovaná          
            else {
                
                $this->_tempPreruseni += $prichod - $odchod;
                              
                // spojíme původní pole a redukované pole a to tak, aby na sebe 
                // záznamy časově navazovaly --> bude scházet akorát jeden index
                $vyslednePole[] = array(
                    'prichod' => $poleDochazky[$j]['prichod'],
                    'odchod' => $poleDochazky[$j+1]['odchod']
                );
                
                // zredukovaný řádek neprocházíme
                $j++;
                // a kvůli nechtěnému nepořádku v indexech další řádky neredukujeme
                $hotovo = true;
            } 
        } 
        return $vyslednePole;   
    }
    
    /**
     * Funkce spočítá rozdíly mezi příchody a odchody, v jednotlivých dnech
     * provede součty těchto rozdílů a vrátí je
     */
    public function ajaxPocitaniCasuAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $idVykazu = $this->getRequest()->getParam('id');
        
        // získáme rok a měsíc výkazu
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);
        $dochazkaOficialni->setIdDochazky($idVykazu);
        
        // měsíc a rok oficiální docházky
        $rozsah = $dochazkaOficialni->getRozsahDochazky();

        // nastavíme časové limitní hodnoty docházky
        $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
        $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

        $this->view->dataCasu = $dochazkaOficialni->sumaCasuDochazky();
        
    }

    /**
     * Smazání konkrétního záznamu oficiální docházky -> zobrazení podrobností
     * o vybraném záznamu
     */
    public function ajaxMazaniZaznamuAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $id = $this->getRequest()->getParam('id');
         
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setIdPruchodu($id);
        $this->view->data = $dochazkaOficialni->ziskejPruchod();
    }
    
    /**
     * Smazání konkrétního záznamu oficiální docházky -> provedení smazání
     */
    public function ajaxMazaniPruchoduAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $id = $this->getRequest()->getParam('id');

        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setMeni(self::$_identity->id);
        $dochazkaOficialni->setIdPruchodu($id);    
        
        $dochazkaOficialni->smazZaznam();        
    }
    
    /**
     * Změna konkrétního průchodu oficiální docházky
     */
    public function zmenaPruchoduAction()
    {
        $idZaznamu = $this->getRequest()->getParam('idZaznamu');
        $idVykazu = $this->getRequest()->getParam('id');
        
        $url = $this->_helper->url;
        
        $form = new Dochazka_Form_ZmenaCasu;
        $form->setAction($url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-pruchodu',
                                         'id' => $idVykazu,
                                         'idZaznamu' => $idZaznamu),null,true));
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {        
        
           // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($form->isValid($request->getPost())) {         
           
                $datum = date('Y-m-d',strtotime(str_replace(' ','',$request->getPost('datumSmeny'))));
                $prichod = date('Y-m-d H:i',strtotime(str_replace(' ','',$request->getPost('prichodDen')).' '.$request->getPost('prichodCas')));
                $odchod = date('Y-m-d H:i',strtotime(str_replace(' ','',$request->getPost('odchodDen')).' '.$request->getPost('odchodCas')));

                $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
                    
                $dochazkaOficialni->setUpdateDatum($datum);
                $dochazkaOficialni->setUpdateCasPrichod($prichod);
                $dochazkaOficialni->setUpdateCasOdchod($odchod);
                $dochazkaOficialni->setIdPruchodu($idZaznamu);    
                $dochazkaOficialni->setUzivatel(self::$_identity->id);
                
                $dochazkaOficialni->zmenZaznam();                
                
                // skok zpátky na oficiální docházku
                $this->_helper->redirector('zmena-vykazu', 'official', 'dochazka', array('id'=>$idVykazu));
            }           
        }
        else {            
            // vyplníme hodnoty            
            $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
            $dochazkaOficialni->setIdPruchodu($idZaznamu);
            $data = $dochazkaOficialni->ziskejPruchod();
            
            $form->populate($data);   
        }
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->form = $form;                
        $this->view->leftNavigation = array(
            array(
                'img' => '',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;          
        
    }
    
    /**
     *  přidání průchodu do požadovaného dne
     */
    public function pridaniPruchoduAction()
    {
        $den = $this->getRequest()->getParam('den');
        $idVykazu = $this->getRequest()->getParam('id');
        
        $url = $this->_helper->url;
        
        $form = new Dochazka_Form_ZmenaCasu;
        $form->setAction($url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'pridani-pruchodu',
                                         'id' => $idVykazu,
                                         'den' => $den),null,true));        
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {        
        
           // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($form->isValid($request->getPost())) {          
                
                // uložení dat
                $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
                                
                $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
                $dochazkaOficialni->setCip(self::$_session->idCipu);
                $dochazkaOficialni->setUpdateDatum(date('Y-m-d',strtotime(str_replace(' ','',$request->getPost('datumSmeny')))));             
                $dochazkaOficialni->setUpdateCasPrichod(date('Y-m-d H:i',strtotime(str_replace(' ','',$request->getPost('prichodDen')).' '.$request->getPost('prichodCas'))));
                $dochazkaOficialni->setUpdateCasOdchod(date('Y-m-d H:i',strtotime(str_replace(' ','',$request->getPost('odchodDen')).' '.$request->getPost('odchodCas'))));
                $dochazkaOficialni->setUzivatel(self::$_identity->id);
                
                $dochazkaOficialni->ulozNovyOficialniPruchod();
                
                // skok zpátky na oficiální docházku
                $this->_helper->redirector('zmena-vykazu', 'official', 'dochazka', array('id'=>$idVykazu));
            }
        }     
        else {            
            // vyplníme hodnoty
            $elDatumSmeny = $form->getElement('datumSmeny');
            $elDatumSmeny->setValue(date('d. m. Y',strtotime($den)));
            $elDatumPrichod = $form->getElement('prichodDen');
            $elDatumPrichod->setValue(date('d. m. Y',strtotime($den)));
            $elDatumOdchod = $form->getElement('odchodDen');
            $elDatumOdchod->setValue(date('d. m. Y',strtotime($den)));
            $elCasPrichod = $form->getElement('prichodCas');
            $elCasPrichod->setValue('06:00');
            $elCasOdchod = $form->getElement('odchodCas');
            $elCasOdchod->setValue('14:30');
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->form = $form;                
        $this->view->leftNavigation = array(
            array(
                'img' => '',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;      
    }
    
    /**
     * Funkce v dané oficiální docházce projede časy všech příchodů a podle
     * nastavených kritérií je zaokrouhlí a uloží
     */    
    public function zaokrouhleniPrichoduAction()
    {
        $idVykazu = $this->getRequest()->getParam('id');
        $data = $this->getRequest()->getParams();
        
        $url = $this->_helper->url;
        
        // formulář pro zaokrouhlení časů
        $form = new Dochazka_Form_ZaokrouhleniCasu;
        $form->setAction($url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zaokrouhleni-prichodu',
                                         'id' => $idVykazu),null,true));      
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {        
        
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            // partial validace je kvůli možným disabled inputům
            if ($form->isValidPartial($request->getPost())) {                  
        
                // získáme rok a měsíc výkazu
                $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
                $dochazkaOficialni->setIdDochazky($idVykazu);

                // měsíc a rok oficiální docházky
                $rozsah = $dochazkaOficialni->getRozsahDochazky();

                // nastavíme časové limitní hodnoty docházky
                $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
                $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

                // budeme zaokrouhlovat ranní směnu
                if (strlen($data['ranniCil']) <> 0) {
                    $dochazkaOficialni->setUpdateCasOd($data['ranniOd']);
                    $dochazkaOficialni->setUpdateCasDo($data['ranniDo']);
                    $dochazkaOficialni->setUpdateCasCil($data['ranniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();
                }
                // budeme zaokrouhlovat odpolední  směnu
                if (strlen($data['odpoledniCil']) <> 0) {
                    $dochazkaOficialni->setUpdateCasOd($data['odpoledniOd']);
                    $dochazkaOficialni->setUpdateCasDo($data['odpoledniDo']);
                    $dochazkaOficialni->setUpdateCasCil($data['odpoledniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();            
                }
                // budeme zaokrouhlovat noční směnu
                if (strlen($data['nocniCil']) <> 0) {
                    $dochazkaOficialni->setUpdateCasOd($data['nocniOd']);
                    $dochazkaOficialni->setUpdateCasDo($data['nocniDo']);
                    $dochazkaOficialni->setUpdateCasCil($data['nocniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();
                }        
            
                // skok zpátky na oficiální docházku
                $this->_helper->redirector('zmena-vykazu', 'official', 'dochazka', array('id'=>$idVykazu));                
            }
        }     
                    
        $this->view->form = $form;            
                
        $this->view->leftNavigation = array(
            array(
                'img' => '',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;             
    }
}