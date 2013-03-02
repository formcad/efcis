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
    private $_tempPreruseni = null;

    public function init()
    {
        self::$_session = new Zend_Session_Namespace('formcad');
        self::$_identity = Zend_Auth::getInstance()->getIdentity();     
    }

    public function indexAction()
    {

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
            $vykazy = new Dochazka_Model_VykazyDochazky( null,
                    self::$_session->idOsoby, self::$_session->idCipu,
                    self::$_identity->id, $mesic, $rok);

            // výjimka v případě, že už je pro měsíc vytvořená oficiální docházka
            if ($vykazy->overExistenci() == true) {
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
        $idDochazky = $vykazy->zalozDochazku();        
        
        $dochazka = new Dochazka_Model_DochazkaOficialni();
        $dochazka->setOsoba(self::$_session->idOsoby);
        $dochazka->setCip(self::$_session->idCipu);
        $dochazka->setUzivatel(self::$_identity->id);
        $dochazka->setMesic($mesic);
        $dochazka->setRok($rok);                
        
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
        
        // měsíc a rok oficiální docházky
        $vykazy = new Dochazka_Model_VykazyDochazky( $idVykazu );
        $rozsah = $vykazy->zjistiRozsahDochazky();

        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);                
        
        // nastavíme časové limitní hodnoty docházky
        $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
        $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

        // získáme pole oficiální docházky
        $poleZaznamu = $dochazkaOficialni->getAkce();
       
        // získáme typy příplatků
        $modelPriplatku = new Dochazka_Model_TypyPriplatku();       
        $modelPriplatku->setPlatnost($rozsah['rok'].'-'.$rozsah['mesic'].'-01');  

        // formuláře pro změnu a přidání průcodu (dvojice příchod-odchod)
        Dochazka_Form_ZmenaCasu::$typ = 'zmena';
        $zmenaPruchoduForm = new Dochazka_Form_ZmenaCasu();
        
        Dochazka_Form_ZmenaCasu::$typ = 'pridani';
        $pridaniPrucoduForm = new Dochazka_Form_ZmenaCasu();
   
        // formulář pro změnu poznámky
        $poznamkaForm = new Dochazka_Form_OfficialPoznamka();
        $this->view->zmenaPoznamkyForm = $poznamkaForm;
        
        /**** DATA DO VIEW ****************************************************/
                                       
        $this->view->data = $poleZaznamu;
        $this->view->idVykazu = $idVykazu;
        $this->view->idOsoby = (self::$_session->idOsoby);
        $this->view->idCipu = (self::$_session->idCipu);
        $this->view->typyPriplatku = $modelPriplatku->getTypy();  
        $this->view->pridaniPruchoduForm = $pridaniPrucoduForm;
        $this->view->zmenaPruchoduForm = $zmenaPruchoduForm;
 
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
        
        $vykazy = new Dochazka_Model_VykazyDochazky(null,
                self::$_session->idOsoby, self::$_session->idCipu,
                self::$_identity->id);        
        
        $poleZaznamu = $vykazy->ziskejDochazku();
        
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
     * @param  array $poleDochazky Vstupní pole
     * @return array               Redukované výstupní pole
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
    public function ajaxPocitaniCasuDochazkyAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $idVykazu = $this->getRequest()->getParam('id');
        
        // měsíc a rok oficiální docházky
        $vykazy = new Dochazka_Model_VykazyDochazky( $idVykazu );
        $rozsah = $vykazy->zjistiRozsahDochazky();

        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);        
        
        // nastavíme časové limitní hodnoty docházky
        $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
        $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

        // získáme data
        $poleDochazky = $dochazkaOficialni->sumaCasuDochazky();
        
        // upravíme formát dat
        foreach ($poleDochazky as $index=>$zaznam) {
            if ($zaznam['dochazka'] == 0) {$poleDochazky[$index]['dochazka'] = '';}
            if ($zaznam['pauza'] == 0) {$poleDochazky[$index]['pauza'] = '';}
            if ($zaznam['cistaDochazka'] == 0) {$poleDochazky[$index]['cistaDochazka'] = '';}
        }
                
        $this->view->dataCasu = json_encode($poleDochazky);        
    }

    /**
     * Funkce spočítá rozdíly mezi příchody a odchody, v daném dni
     * provede součty těchto rozdílů a vrátí je
     */
    public function ajaxPocitaniCasuDneAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $datum = $this->getRequest()->getParam('datum');
        
        // získáme rok a měsíc výkazu
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);        
        
        // nastavíme časové limitní hodnoty docházky
        $dochazkaOficialni->setDatumOd($datum);
        $dochazkaOficialni->setDatumDo($datum);

        // získáme data
        $this->view->dataCasu = json_encode($dochazkaOficialni->sumaCasuDochazky());
    }
    
    /**
     * Smazání konkrétního záznamu oficiální docházky -> zobrazení
     * podrobností o vybraném záznamu
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
        $dochazkaOficialni->setUzivatel(self::$_identity->id);
        $dochazkaOficialni->setIdPruchodu($id);    
        
        $dochazkaOficialni->smazZaznam();        
    }

    /**
     * Změna konkrétního průchodu oficiální docházky
     */
    public function ajaxZmenaPruchoduAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
       
        $data = $this->getRequest()->getParam('data');

        // uzpůsobíme data pro použití
        foreach ($data as $radek) {
            $formData[$radek['name']] = $radek['value'];
        }
        
        $datum = date('Y-m-d',strtotime(str_replace(' ','',$formData['datumSmeny'])));
        $prichod = date('Y-m-d H:i',strtotime(str_replace(' ','',$formData['prichodDen']).' '.$formData['prichodCas']));
        $odchod = date('Y-m-d H:i',strtotime(str_replace(' ','',$formData['odchodDen']).' '.$formData['odchodCas']));        
        
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();

        $dochazkaOficialni->setDatumSmeny($datum);
        $dochazkaOficialni->setCasPrichod($prichod);
        $dochazkaOficialni->setCasOdchod($odchod);
        $dochazkaOficialni->setIdPruchodu($formData['idZaznamu']);    
        $dochazkaOficialni->setUzivatel(self::$_identity->id);

        $dochazkaOficialni->zmenZaznam();
    }
    
    /**
     * Provedení kontroly formuláře před samotnou změnou průchodu
     */
    public function ajaxValidaceZmenyPruchoduAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $data = $this->getRequest()->getParam('data');
        
        // uzpůsobíme data pro použití ve formuláři
        foreach ($data as $radek) {
            $formData[$radek['name']] = $radek['value'];
        }
        
        $form = new Dochazka_Form_ZmenaCasu;
    
        if ($form->isValid($formData)) {
            $this->view->chyba = false;
        }
        else {
            $this->view->chyba = true;
        }          
    }

    /**
     * Získání podrobností záznamu konkrétní dvojice průchodů
     */
    public function ajaxPodrobnostiZaznamuAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $idZaznamu = $this->getRequest()->getParam('idZaznamu');
 
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
        $dochazkaOficialni->setIdPruchodu($idZaznamu);
        $this->view->data = json_encode($dochazkaOficialni->ziskejPruchod());
    }    
    
    /**
     * Získání zapsané poznámky u dne oficiální docházky
     */
    public function ajaxZjistiPoznamkuAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $datum = $this->getRequest()->getParam('datum');
 
        $poznamky = new Dochazka_Model_OficialniPoznamky(
            self::$_session->idOsoby, self::$_session->idCipu, $datum);
        
        $this->view->poznamka = $poznamky->ziskejPoznamku();        
    }    
        
    /**
     * Změna zapsané poznámky u dne oficiální docházky
     */
    public function ajaxZmenaPoznamkyAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $datum = $this->getRequest()->getParam('datum');
        $poznamka = $this->getRequest()->getParam('poznamka');
        
        $poznamky = new Dochazka_Model_OficialniPoznamky(
            self::$_session->idOsoby, self::$_session->idCipu, $datum, $poznamka);
        
        $poznamky->zapisPoznamku();
    }
    
    /**
     *  Přidání průchodu do požadovaného dne
     */
    public function ajaxPridaniPruchoduAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
       
        $data = $this->getRequest()->getParam('data');

        // uzpůsobíme data pro použití
        foreach ($data as $radek) {
            $formData[$radek['name']] = $radek['value'];
        }
        
        $datum = date('Y-m-d',strtotime(str_replace(' ','',$formData['datumSmeny'])));
        $prichod = date('Y-m-d H:i',strtotime(str_replace(' ','',$formData['prichodDen']).' '.$formData['prichodCas']));
        $odchod = date('Y-m-d H:i',strtotime(str_replace(' ','',$formData['odchodDen']).' '.$formData['odchodCas']));        
        
        $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();

        $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
        $dochazkaOficialni->setCip(self::$_session->idCipu);
        $dochazkaOficialni->setDatumSmeny($datum);
        $dochazkaOficialni->setCasPrichod($prichod);
        $dochazkaOficialni->setCasOdchod($odchod);

        $dochazkaOficialni->ulozNovyOficialniPruchod();
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

                // měsíc a rok oficiální docházky
                $vykazy = new Dochazka_Model_VykazyDochazky( $idVykazu );
                $rozsah = $vykazy->zjistiRozsahDochazky();

                // nastavíme časové limitní hodnoty docházky
                $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
                $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
                $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );

                // budeme zaokrouhlovat ranní směnu
                if (strlen($data['ranniCil']) <> 0) {
                    $dochazkaOficialni->setCasOd($data['ranniOd']);
                    $dochazkaOficialni->setCasDo($data['ranniDo']);
                    $dochazkaOficialni->setCasCil($data['ranniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();
                }
                // budeme zaokrouhlovat odpolední  směnu
                if (strlen($data['odpoledniCil']) <> 0) {
                    $dochazkaOficialni->setCasOd($data['odpoledniOd']);
                    $dochazkaOficialni->setCasDo($data['odpoledniDo']);
                    $dochazkaOficialni->setCasCil($data['odpoledniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();            
                }
                // budeme zaokrouhlovat noční směnu
                if (strlen($data['nocniCil']) <> 0) {
                    $dochazkaOficialni->setCasOd($data['nocniOd']);
                    $dochazkaOficialni->setCasDo($data['nocniDo']);
                    $dochazkaOficialni->setCasCil($data['nocniCil']);            
                    $dochazkaOficialni->zaokrouhliPrichodyDochazky();
                }        
            
                // skok zpátky na oficiální docházku
                $this->_helper->redirector('zmena-vykazu', 'official', 'dochazka', array('id'=>$idVykazu));                
            }
        }     
                    
        $this->view->form = $form;            
                
        $this->view->leftNavigation = array(
            array(
                'img' => 'clovek.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;             
    }

    /**
     * Hromadné doplnění pauzy oficiální docházky
     */
    public function doplneniPauzyAction()
    {
        $idVykazu = $this->getRequest()->getParam('id');
        $url = $this->_helper->url;
        
        $form = new Dochazka_Form_OfficialPauzy(); 
        
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'official', 
                                  'action'=>'doplneni-pauzy',
                                  'id'=>$idVykazu),null, true); 
        $form->setAction($action);         
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {

            // měsíc a rok oficiální docházky
            $vykazy = new Dochazka_Model_VykazyDochazky( $idVykazu );
            $rozsah = $vykazy->zjistiRozsahDochazky();

            // nastavíme časové limitní hodnoty docházky
            $dochazkaOficialni = new Dochazka_Model_DochazkaOficialni();
            $dochazkaOficialni->setDatumOd($rozsah['rok'].'-'.$rozsah['mesic'].'-01');
            $dochazkaOficialni->setDatumDo($rozsah['rok'].'-'.$rozsah['mesic'].'-'.cal_days_in_month(CAL_GREGORIAN,$rozsah['mesic'],$rozsah['rok']) );
            $dochazkaOficialni->setCip(self::$_session->idCipu);
            $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
                        
            $data = $dochazkaOficialni->sumaCasuDochazky();
            
            foreach ($data as $den) {
                
                // pokud máme alespoň jeden průchod
                if ($den['cistaDochazka'] > 0) {
                    
                    // a celková doba v práci aspoň u jednoho průchodu je větš než 6 hodin
                    if ($den['nejdelsiPruchod'] > 6) {
                        
                        // a celková doba pauzy je menší než 0,5 hodiny
                        if ($den['pauza'] < 0.5) {

                            $dochazkaOficialni->setDatumSmeny($den['datum']);
                            $dochazkaOficialni->setUzivatel(self::$_identity->id);  
                            $dochazkaOficialni->setOsoba(self::$_session->idOsoby);
                            $dochazkaOficialni->setCip(self::$_session->idCipu);
                            $dochazkaOficialni->setTrvani(0.5);
                            
                            // doplníme dobu pauzu na 0,5 hodiny     
                            $dochazkaOficialni->doplnPauzu();
                        }                     
                    }                                        
                }
            }
                                    
            // skok zpátky na oficiální docházku
            $this->_helper->redirector('zmena-vykazu', 'official', 'dochazka', array('id'=>$idVykazu));                   
        }              
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->form = $form;
        $this->view->leftNavigation = array(
            array(
                'img' => 'clovek.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;            
    }

    /**
     * Hromadné doplnění příplatků oficiální docházky
     */
    public function doplneniPriplatkuAction()
    {
        $idVykazu = $this->getRequest()->getParam('id');
        $url = $this->_helper->url;
        
        $this->view->leftNavigation = array(
            array(
                'img' => 'clovek.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'official',
                                         'action' => 'zmena-vykazu',
                                         'id' => $idVykazu),null,true),
                'text' => 'Výkaz docházky')
        );    
        $this->view->uzivatel = self::$_session->uzivatel;            
    }

    /**
     * Pro kombinaci dne směny a id výkazu docházky vypíše podrobnosti dne
     */
    public function ajaxPodrobnostiDnePauzaAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();

        /**** Informační tabulka **********************************************/
        
        $datum = $this->getRequest()->getParam('datum');
        $idOsoby = $this->getRequest()->getParam('osoba');
        $idCipu = $this->getRequest()->getParam('cip');
        
        $dochazka = new Dochazka_Model_DochazkaOficialni();
        $dochazka->setDatumOd($datum);
        $dochazka->setDatumDo($datum);
        $dochazka->setOsoba($idOsoby);
        $dochazka->setCip($idCipu);
        
        $data = $dochazka->getAkce();
  
        /**** Změnový formulář ************************************************/
        
        $form = new Dochazka_Form_OfficialPauzaEdit();
        
        // pokud existuje nějaká suma přerušení, doplníme ji do formuláře
        if (count($data[0]['preruseni'])>0) {            
            $filter = new Zend_Filter_NormalizedToLocalized(); 
            $delka = $form->getElement('delkaPauzy');
            $delka->setValue($filter->filter($data[0]['preruseni'][0]['delka']));
        }
        
        /**** Data do view ****************************************************/
        
        $this->view->data = $data[0];
        $this->view->form = $form;        
    }

    /**
     * Slouží pouze pro validaci číselného formátu doplňované délky pauzy,
     * přičemž pokud není formát validní, vrátí funkce error div, který se
     * pomocí Java Scriptu vypíše do stránky 
     */
    public function ajaxValidaceDelkyPauzyAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        
        $data = array('delkaPauzy' => $this->getRequest()->getParam('delka'));

        $form = new Dochazka_Form_OfficialPauzaEdit();

        if ($form->isValid($data)) {
            $this->view->chyba = false;
        }
        else {
            $this->view->chyba = true;
        }  
    }
    
    /**
     * Provede doplnění délky pauzy do databáze pro konkrétního člověka (ID
     * osoby a ID čipu) v konkrétním dni směny. Délka pauzy už je předešlou
     * funkcí zvalidována
     */
    public function ajaxZmenaPauzyAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $filter = new Zend_Filter_LocalizedToNormalized();
        
        $dochazka = new Dochazka_Model_DochazkaOficialni();
        $dochazka->setOsoba($this->getRequest()->getParam('osoba'));
        $dochazka->setCip($this->getRequest()->getParam('cip'));
        $dochazka->setDatumSmeny($this->getRequest()->getParam('datum'));        
        $dochazka->setUzivatel(self::$_identity->id);
        $dochazka->setTrvani($filter->filter($this->getRequest()->getParam('delka')));
                
        $dochazka->doplnPauzu();
    }   
}

