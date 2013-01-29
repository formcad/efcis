<?php

class Dochazka_EditController extends Zend_Controller_Action
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
        /**** VÝJIMKY *********************************************************/
        
        // v session musí být potřebné údaje
        if (!isset(self::$_session->idOsoby)) {            
            throw new Exception('Chyba v nastavení session');
        }
        
        // Interval je větší než jeden den
        if (strtotime(self::$_session->do) - strtotime(self::$_session->od) < 86400) {                 
            throw new Exception('Malý časový rozsah');        
        }
     
        /**** ZÁKLADNÍ NASTAVENÍ **********************************************/       
      
        $dochazka = new Dochazka_Model_AkceDochazky();
        $dochazka->setIdUser(self::$_session->idOsoby);
        $dochazka->setIdChip(self::$_session->idCipu);                    
        
        /**** ZÁZNAMY V DOCHAZKA_TEMP *****************************************/
        
        $poleTemp = $dochazka->getTempAkce();
        
        /**** ZÁZNAMY V DOCHAZKA_ERROR ****************************************/
        
        $poleError = $dochazka->getErrorAkce(); 
        
        /**** ZÁZNAMY V DOCHAZKA_PRUCHODY *************************************/
        
        $dochazka->setDateFrom(self::$_session->od);
        $dochazka->setDateTo(self::$_session->do);                
        
        // kompletní pole záznamů
        $poleZaznamu = $dochazka->getAkce();
     
        // kontrola integrity
        $souborIntegrity = $this->_helper->integrityCheck($poleZaznamu);
        
        // uložení záznamu o integritě do výsledného pole
        $integritniPole = $this->_helper->integrityAdd($poleZaznamu, $souborIntegrity);
        
        // přidání zkratky dne a formátované verze data do výsledného pole
        $vyslednePole = $this->_helper->dateAdd($integritniPole);             
      
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
        
        $this->view->temp = $poleTemp;
        $this->view->errors = $poleError;
        $this->view->zaznamy = $vyslednePole;              
    }

    public function addPruchodAction()
    {
        /**** TVORBA FORMULÁŘE ************************************************/

        $typyPruchodu = new Dochazka_Model_TypyPruchodu();    
        
        // v závislosti na typu průchodu zkonstruujeme formulář  
        switch ($this->getRequest()->getParam('type')) {
            case 'prichod':
                $typyPruchodu->setTyp('prichod');
                Dochazka_Form_PruchodPrichod::$typyPruchodu = $typyPruchodu->getKonkretniTypy();                
                $pruchodForm = new Dochazka_Form_PruchodPrichod();
                break;
            case 'odchod':
                $typyPruchodu->setTyp('odchod');
                Dochazka_Form_PruchodOdchod::$typyPruchodu = $typyPruchodu->getKonkretniTypy();                
                $pruchodForm = new Dochazka_Form_PruchodOdchod();
                break;
        }        
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'add-pruchod',
                                  'type' => $this->getRequest()->getParam('type')),
                            null, true);     
        
        $pruchodForm->setAction($action);   
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {
             
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($pruchodForm->isValid($request->getPost())) {     
                
                $pruchody = new Dochazka_Model_PruchodyDochazky();
                $datum = new Fc_Date_Format();
                
                $casAkce = $datum->dbDateTimeFormat($request->getPost('datumPruchodu'), 
                                                $request->getPost('casPruchodu'));
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
                
                $pruchody->setCasAkce($casAkce);
                $pruchody->setDatum($datumSmeny);
                $pruchody->setIdAkce($request->getPost('typ'));
                $pruchody->setIdOsoby(self::$_session->idOsoby);
                $pruchody->setIdCipu(self::$_session->idCipu);
                $pruchody->setIdZmenil(self::$_identity->id);
                $pruchody->setCasZmeny(date('Y-m-d H:i:s'));
                $pruchody->addPruchod();
                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');
            } 
        }     
        else {
            // v případě neodeslaného formuláře musíme vyplnit den
            $den = $this->getRequest()->getParam('day');    
            $cas = date('H:i');
                        
            $datumSmenyElement = $pruchodForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($den)));                      
                   
            $datumPruchoduElement = $pruchodForm->getElement('datumPruchodu');
            $datumPruchoduElement->setValue(date('d. m. Y', strtotime($den)));            
            
            $casPruchoduElement = $pruchodForm->getElement('casPruchodu');
            $casPruchoduElement->setValue($cas);               
        }
               
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->pruchodForm = $pruchodForm;
    }

    public function editPruchodAction()
    {
        /**** TVORBA FORMULÁŘE ************************************************/
        
        // v závislosti na typu průchodu zkonstruujeme formulář
        $pruchody = new Dochazka_Model_PruchodyDochazky();            
        $pruchody->setIdZaznamu($this->getRequest()->get('id'));

        $zaznam = $pruchody->getPruchod();        
        
        $typyPruchodu = new Dochazka_Model_TypyPruchodu();    
        
        // v závislosti na typu průchodu zkonstruujeme formulář  
        switch ($zaznam['typ']) {
            case 'prichod':
                $typyPruchodu->setTyp('prichod');
                Dochazka_Form_PruchodPrichod::$typyPruchodu = $typyPruchodu->getKonkretniTypy();                
                $pruchodForm = new Dochazka_Form_PruchodPrichod;
                break;
            case 'odchod':
                $typyPruchodu->setTyp('odchod');
                Dochazka_Form_PruchodOdchod::$typyPruchodu = $typyPruchodu->getKonkretniTypy();                
                $pruchodForm = new Dochazka_Form_PruchodOdchod();
                break;
        }               
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller' => 'edit', 
                                  'action' => 'edit-pruchod',
                                  'id' => $this->getRequest()->get('id')),null, true);     
        
        $pruchodForm->setAction($action);         
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
          
        $request = $this->getRequest();
        if ($request->isPost()) {        
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($pruchodForm->isValid($request->getPost())) {  
                                
                $datum = new Fc_Date_Format();
                
                $casAkce = $datum->dbDateTimeFormat($request->getPost('datumPruchodu'), 
                                $request->getPost('casPruchodu'));
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
             
                $pruchody->setIdZaznamu($this->getRequest()->get('id'));
                $pruchody->setCasAkce($casAkce);
                $pruchody->setDatum($datumSmeny);
                $pruchody->setIdAkce($request->getPost('typ'));
                $pruchody->setIdZmenil(self::$_identity->id);
                $pruchody->editPruchod();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                
            }   
        }
        else {           
            // v případě neodeslaného formuláře musíme vyplnit hodnoty z DB  

            $datumSmenyElement = $pruchodForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($zaznam['datum'])));                      
            
            $datumPruchoduElement = $pruchodForm->getElement('datumPruchodu');
            $datumPruchoduElement->setValue(date('d. m. Y', strtotime($zaznam['casAkce'])));            
            
            $casPruchoduElement = $pruchodForm->getElement('casPruchodu');
            $casPruchoduElement->setValue(date('H:i', strtotime($zaznam['casAkce'])));   
            
            $typPruchoduElement = $pruchodForm->getElement('typ');
            $typPruchoduElement->setValue($zaznam['idAkce']);
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->pruchodForm = $pruchodForm;     
    }

    public function deletePruchodAction()
    {
        $deleteForm = new Dochazka_Form_DeleteAction();
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-pruchod'),null, true);     
        
        $deleteForm->setAction($action);            
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $pruchody = new Dochazka_Model_PruchodyDochazky();
                
                $pruchody->setIdZaznamu($request->getPost('idZaznamu'));
                $pruchody->setIdAkce($request->getPost('typ'));
                $pruchody->setIdZmenil(self::$_identity->id);
                $pruchody->deletePruchod();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        }
        else {
            
            $datum = new Fc_Date_Format;

            $id = $this->getRequest()->get('id');        

            $pruchody = new Dochazka_Model_PruchodyDochazky();            
            $pruchody->setIdZaznamu($id);

            $zaznam = $pruchody->getPruchod();
            $zaznam['datumSmeny'] = $datum->dateFormat($zaznam['datum']);
            $zaznam['timeAkce'] = $datum->dateTimeFormat($zaznam['casAkce']);

            $idZaznamu = $deleteForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);        

            $this->view->zaznam = $zaznam;
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->deleteForm = $deleteForm;           
    }

    public function addPreruseniAction()
    {
        /**** TVORBA FORMULÁŘE ************************************************/
        
        $typyPreruseni = new Dochazka_Model_TypyPreruseni(); 
        Dochazka_Form_Preruseni::$typyPreruseni = $typyPreruseni->getTypy();
        
        $preruseniForm = new Dochazka_Form_Preruseni();
 
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'add-preruseni'),null, true);     
        
        $preruseniForm->setAction($action);   
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($preruseniForm->isValid($request->getPost())) {     
                
                $preruseni = new Dochazka_Model_PreruseniDochazky();
                $datum = new Fc_Date_Format();
                $filter = new Zend_Filter_LocalizedToNormalized();
                
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
                
                $preruseni->setDelka($filter->filter($request->getPost('delkaPreruseni')));
                $preruseni->setDatum($datumSmeny);
                $preruseni->setIdPreruseni($request->getPost('typ'));
                $preruseni->setIdOsoby(self::$_session->idOsoby);
                $preruseni->setIdCipu(self::$_session->idCipu);
                $preruseni->setIdZmenil(self::$_identity->id);
                $preruseni->setCasZmeny(date('Y-m-d H:i:s'));
                $preruseni->addPreruseni();
                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');
            } 
        }     
        else {
            // v případě neodeslaného formuláře musíme vyplnit den
            $den = $this->getRequest()->getParam('day');    
            
            $datumSmenyElement = $preruseniForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($den)));                                             
        }
               
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->preruseniForm = $preruseniForm;
    }

    public function editPreruseniAction()
    {
        /**** TVORBA FORMULÁŘE ************************************************/
        
        $typyPreruseni = new Dochazka_Model_TypyPreruseni(); 
        Dochazka_Form_Preruseni::$typyPreruseni = $typyPreruseni->getTypy();                
        
        $preruseniForm = new Dochazka_Form_Preruseni();
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'edit-preruseni'),null, true);     
        
        $preruseniForm->setAction($action);         
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
          
        $request = $this->getRequest();
        if ($request->isPost()) {        
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($preruseniForm->isValid($request->getPost())) {  
                                
                $preruseni = new Dochazka_Model_PreruseniDochazky(); 
                $datum = new Fc_Date_Format();
                $filter = new Zend_Filter_LocalizedToNormalized();
                
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
                
                $preruseni->setDelka($filter->filter($request->getPost('delkaPreruseni')));                
                $preruseni->setIdZaznamu($request->getPost('idZaznamu'));
                $preruseni->setDatum($datumSmeny);
                $preruseni->setIdPreruseni($request->getPost('typ'));
                $preruseni->setIdZmenil(self::$_identity->id);
                $preruseni->editPreruseni();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                
            }   
        }
        else {
            // v případě neodeslaného formuláře musíme vyplnit hodnoty z DB
            $id = $this->getRequest()->get('id');
            
            $filter = new Zend_Filter_NormalizedToLocalized(); 
            $preruseni = new Dochazka_Model_PreruseniDochazky();                  
            $preruseni->setIdZaznamu($id);
            
            $zaznam = $preruseni->getPreruseni();
            
            $idZaznamu = $preruseniForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);
            
            $datumSmenyElement = $preruseniForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($zaznam['datum'])));                                             
            
            $delkaPreruseni = $preruseniForm->getElement('delkaPreruseni');
            $delkaPreruseni->setValue($filter->filter($zaznam['delka'])); 
            
            $typPreruseniElement = $preruseniForm->getElement('typ');
            $typPreruseniElement->setValue($zaznam['idPreruseni']);
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->preruseniForm = $preruseniForm;             
    }

    public function deletePreruseniAction()
    {
        $deleteForm = new Dochazka_Form_DeleteAction();
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-preruseni'),null, true);     
        
        $deleteForm->setAction($action);            
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $preruseni = new Dochazka_Model_PreruseniDochazky();
                
                $preruseni->setIdZaznamu($request->getPost('idZaznamu'));
                $preruseni->setIdPreruseni($request->getPost('typ'));
                $preruseni->setIdZmenil(self::$_identity->id);
                $preruseni->deletePreruseni();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        }
        else {
            
            $datum = new Fc_Date_Format;

            $id = $this->getRequest()->get('id');        

            $preruseni = new Dochazka_Model_PreruseniDochazky();            
            $preruseni->setIdZaznamu($id);

            $zaznam = $preruseni->getPreruseni();
            $zaznam['datumSmeny'] = $datum->dateFormat($zaznam['datum']);

            $idZaznamu = $deleteForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);        

            $this->view->zaznam = $zaznam;
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->deleteForm = $deleteForm;    
    }

    public function addPriplatekAction()
    {
        /**** DATUM PLATNOSTI FORMULÁŘE ***************************************/
                
        $request = $this->getRequest();
                  
        // v případě odeslaného a formuláře je datem platnosti datum z formuláře
        if ($request->isPost()) {
            $datum = new Fc_Date_Format();
            $den = $datum->dbDateFormat($request->getPost('datumSmeny'));
        }
        // jinak je datem platnosti datum z URL
        else {
            $den = $this->getRequest()->getParam('day');
        }
        
        /**** TVORBA FORMULÁŘE ************************************************/               
        
        $typyPriplatku = new Dochazka_Model_TypyPriplatku();
        $typyPriplatku->setPlatnost($den);
        
        Dochazka_Form_Priplatek::$typyPriplatku = $typyPriplatku->getTypy();                           
        
        $priplatekForm = new Dochazka_Form_Priplatek();
 
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'add-priplatek'),null, true);     
        
        $priplatekForm->setAction($action);   
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/

        if ($request->isPost()) {
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($priplatekForm->isValid($request->getPost())) {     
                
                $priplatek = new Dochazka_Model_PriplatkyDochazky();                
                $filter = new Zend_Filter_LocalizedToNormalized();
                               
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
                
                $priplatek->setDelka($filter->filter($request->getPost('delkaPriplatku')));
                $priplatek->setDatum($datumSmeny);
                $priplatek->setIdTypuPriplatku($request->getPost('typ'));
                $priplatek->setIdOsoby(self::$_session->idOsoby);
                $priplatek->setIdCipu(self::$_session->idCipu);
                $priplatek->setIdZmenil(self::$_identity->id);
                $priplatek->setCasZmeny(date('Y-m-d H:i:s'));
                $priplatek->addPriplatek();
                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');
            } 
        }     
        else {
            // v případě neodeslaného formuláře musíme vyplnit den                       
            $datumSmenyElement = $priplatekForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($den)));                                             
        }
               
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->priplatekForm = $priplatekForm;
    }

    public function editPriplatekAction()
    {
        /**** DATUM PLATNOSTI FORMULÁŘE ***************************************/
                
        $request = $this->getRequest();
        $priplatek = new Dochazka_Model_PriplatkyDochazky();  
                  
        // v případě odeslaného a formuláře je datem platnosti datum z formuláře
        if ($request->isPost()) {
            $datum = new Fc_Date_Format();
            $den = $datum->dbDateFormat($request->getPost('datumSmeny'));
        }
        // jinak je datem platnosti datum z uloženého záznamu v databázi
        else {
            $id = $this->getRequest()->get('id');
            $priplatek->setIdZaznamu($id);     
            
            $zaznam = $priplatek->getPriplatek(); 
            $den = date('Y-m-d', strtotime($zaznam['datum']));
        }
                
        
        /**** TVORBA FORMULÁŘE ************************************************/
        
        $typyPriplatku = new Dochazka_Model_TypyPriplatku(); 
        $typyPriplatku->setPlatnost($den);
        
        Dochazka_Form_Priplatek::$typyPriplatku = $typyPriplatku->getTypy();           
        
        $priplatekForm = new Dochazka_Form_Priplatek();
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'edit-priplatek'),null, true);     
        
        $priplatekForm->setAction($action);         
        
        /**** ZPRACOVÁNÍ OBSAHU ***********************************************/
          
        if ($request->isPost()) {        
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($priplatekForm->isValid($request->getPost())) {  
                                
                $filter = new Zend_Filter_LocalizedToNormalized();
                
                $datumSmeny = $datum->dbDateFormat($request->getPost('datumSmeny'));
             
                $priplatek->setDelka($filter->filter($request->getPost('delkaPriplatku')));
                $priplatek->setIdZaznamu($request->getPost('idZaznamu'));
                $priplatek->setDatum($datumSmeny);
                $priplatek->setIdTypuPriplatku($request->getPost('typ'));
                $priplatek->setIdZmenil(self::$_identity->id);
                $priplatek->editPriplatek();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                
            }   
        }
        else {
            // v případě neodeslaného formuláře musíme vyplnit hodnoty z DB
            
            
            $filter = new Zend_Filter_NormalizedToLocalized();
                      
                              
            
            $idZaznamu = $priplatekForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);
            
            $datumSmenyElement = $priplatekForm->getElement('datumSmeny');
            $datumSmenyElement->setValue(date('d. m. Y', strtotime($zaznam['datum'])));                                             
            
            $delkaPriplatku = $priplatekForm->getElement('delkaPriplatku');
            $delkaPriplatku->setValue($filter->filter($zaznam['delka'])); 
            
            $typPriplatkuElement = $priplatekForm->getElement('typ');
            $typPriplatkuElement->setValue($zaznam['idPriplatku']);
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->priplatekForm = $priplatekForm;     
    }

    public function deletePriplatekAction()
    {
        $deleteForm = new Dochazka_Form_DeleteAction();
        
        $url = $this->_helper->url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-priplatek'),null, true);     
        
        $deleteForm->setAction($action);            
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $priplatek = new Dochazka_Model_PriplatkyDochazky();
                
                $priplatek->setIdZaznamu($request->getPost('idZaznamu'));
                $priplatek->setIdTypuPriplatku($request->getPost('typ'));
                $priplatek->setIdZmenil(self::$_identity->id);
                $priplatek->deletePriplatek();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        }
        else {
            
            $datum = new Fc_Date_Format;

            $id = $this->getRequest()->get('id');        

            $priplatek = new Dochazka_Model_PriplatkyDochazky();            
            $priplatek->setIdZaznamu($id);

            $zaznam = $priplatek->getPriplatek();
            $zaznam['datumSmeny'] = $datum->dateFormat($zaznam['datum']);

            $idZaznamu = $deleteForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);    
            
            $this->view->zaznam = $zaznam;
        }
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;            
        $this->view->deleteForm = $deleteForm;   
    }

    public function addHromadnePruchodyAction()
    {
        $typyPruchodu = new Dochazka_Model_TypyPruchodu;
        Dochazka_Form_HromadnePruchody::$typyPruchodu = $typyPruchodu->getTypy();            
        
        $hromadnyForm = new Dochazka_Form_HromadnePruchody();
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře vložíme záznamy
            if ($hromadnyForm->isValid($request->getPost())) {       
                
                $datum = new Fc_Date_Format();     
                
                $datumOd = $datum->dbDateFormat($request->getPost('datumSmenyOd'));    
                $datumDo = $datum->dbDateFormat($request->getPost('datumSmenyDo'));                
                
                $kalendar = new Application_Model_Kalendar;
                $kalendar->setDateFrom($datumOd);
                $kalendar->setDateTo($datumDo);                
                
                $dny = $kalendar->getVikendovyKalendar();       
                foreach ($dny as $den) {

                    $zapiseme = $this->_rozhodniZapsani($den,$request->getPost('opakovani'));
                    if ($zapiseme) {

                        $pruchod = new Dochazka_Model_PruchodyDochazky();

                        // vytvoříme den průchodu
                        switch ($request->getPost('denPruchodu')) {
                            case 0: 
                                $day = date('Y-m-d',strtotime($den['datum'])); 
                                $smena = $day;
                                break;
                            case 1:                                
                                $day = date('Y-m-d',strtotime('+1 day',strtotime($den['datum']))); 
                                $smena = date('Y-m-d',strtotime($den['datum']));
                                break;
                        }
                        
                        $pruchod->setIdAkce($request->getPost('typ'));
                        $pruchod->setCasAkce($day.' '.$request->getPost('casPruchodu'));
                        $pruchod->setIdCipu(self::$_session->idCipu);
                        $pruchod->setIdOsoby(self::$_session->idOsoby);
                        $pruchod->setSmazano(false);
                        $pruchod->setDatum($smena);
                        $pruchod->setIdZmenil(self::$_identity->id);
                        $pruchod->setCasZmeny(date('Y-m-d H:i:s'));
                        $pruchod->addPruchod();
                    }
                }                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        } 
        else {
            $smenaOd = $hromadnyForm->getElement('datumSmenyOd');
            $smenaOd->setValue(date('d. m. Y', strtotime(self::$_session->od)));

            $smenaDo = $hromadnyForm->getElement('datumSmenyDo');
            $smenaDo->setValue(date('d. m. Y', strtotime(self::$_session->do)));
        }
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->hromadnyForm = $hromadnyForm;           
    }

    public function addHromadnePreruseniAction()
    {
        $typyPreruseni = new Dochazka_Model_TypyPreruseni;
        Dochazka_Form_HromadnePreruseni::$typyPreruseni = $typyPreruseni->getTypy();
        
        $hromadnyForm = new Dochazka_Form_HromadnePreruseni();
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
           // v případě odeslaného a zvalidovaného formuláře vložíme záznamy
            if ($hromadnyForm->isValid($request->getPost())) {       
                
                $datum = new Fc_Date_Format();     
                $filter = new Zend_Filter_LocalizedToNormalized();
                
                $datumOd = $datum->dbDateFormat($request->getPost('datumSmenyOd'));    
                $datumDo = $datum->dbDateFormat($request->getPost('datumSmenyDo'));                
                
                $kalendar = new Application_Model_Kalendar;
                $kalendar->setDateFrom($datumOd);
                $kalendar->setDateTo($datumDo);                
                
                $dny = $kalendar->getVikendovyKalendar();       
                foreach ($dny as $den) {

                    $zapiseme = $this->_rozhodniZapsani($den,$request->getPost('opakovani'));
                    if ($zapiseme) {

                        $preruseni = new Dochazka_Model_PreruseniDochazky();
                        
                        $preruseni->setIdPreruseni($request->getPost('typ'));
                        $preruseni->setDelka($filter->filter($request->getPost('delkaPreruseni')));
                        $preruseni->setIdCipu(self::$_session->idCipu);
                        $preruseni->setIdOsoby(self::$_session->idOsoby);
                        $preruseni->setSmazano(false);
                        $preruseni->setDatum(date('Y-m-d',strtotime($den['datum'])));
                        $preruseni->setIdZmenil(self::$_identity->id);
                        $preruseni->setCasZmeny(date('Y-m-d H:i:s'));
                        $preruseni->addPreruseni();
                    }
                }                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                                   
        } 
        else {
            $smenaOd = $hromadnyForm->getElement('datumSmenyOd');
            $smenaOd->setValue(date('d. m. Y', strtotime(self::$_session->od)));

            $smenaDo = $hromadnyForm->getElement('datumSmenyDo');
            $smenaDo->setValue(date('d. m. Y', strtotime(self::$_session->do)));
        }
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->hromadnyForm = $hromadnyForm;        
    }

    public function addHromadnePriplatkyAction()
    {
        /**** DATUM PLATNOSTI FORMULÁŘE ***************************************/
                
        $request = $this->getRequest();
                  
        // v případě odeslaného a formuláře je datem platnosti první datum z formuláře
        if ($request->isPost()) {
            $datum = new Fc_Date_Format();
            $den = $datum->dbDateFormat($request->getPost('datumSmenyOd'));
        }
        // jinak je datem platnosti dnešní datum
        else {
            $den = date('Y-m-d');
        }
        
        /**** TVORBA FORMULÁŘE ************************************************/               
        
        $typyPriplatku = new Dochazka_Model_TypyPriplatku();
        $typyPriplatku->setPlatnost($den);

        Dochazka_Form_HromadnePriplatky::$typyPriplatku = $typyPriplatku->getTypy();        
        
        $hromadnyForm = new Dochazka_Form_HromadnePriplatky();
                   
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře vložíme záznamy
            if ($hromadnyForm->isValid($request->getPost())) {       
                                             
                $datum = new Fc_Date_Format();     
                $filter = new Zend_Filter_LocalizedToNormalized();
                
                $datumOd = $datum->dbDateFormat($request->getPost('datumSmenyOd'));    
                $datumDo = $datum->dbDateFormat($request->getPost('datumSmenyDo'));                
                
                $kalendar = new Application_Model_Kalendar;
                $kalendar->setDateFrom($datumOd);
                $kalendar->setDateTo($datumDo);                
                
                $dny = $kalendar->getVikendovyKalendar();       
                foreach ($dny as $den) {

                    $zapiseme = $this->_rozhodniZapsani($den,$request->getPost('opakovani'));
                    if ($zapiseme) {

                        $preruseni = new Dochazka_Model_PriplatkyDochazky();
                        
                        $preruseni->setIdTypuPriplatku($request->getPost('typ'));
                        $preruseni->setDelka($filter->filter($request->getPost('delkaPriplatku')));
                        $preruseni->setIdCipu(self::$_session->idCipu);
                        $preruseni->setIdOsoby(self::$_session->idOsoby);
                        $preruseni->setSmazano(false);
                        $preruseni->setDatum(date('Y-m-d',strtotime($den['datum'])));
                        $preruseni->setIdZmenil(self::$_identity->id);
                        $preruseni->setCasZmeny(date('Y-m-d H:i:s'));
                        $preruseni->addPriplatek();
                    }
                }                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                                    
            }                   
        } 
        else {
            $smenaOd = $hromadnyForm->getElement('datumSmenyOd');
            $smenaOd->setValue(date('d. m. Y', strtotime(self::$_session->od)));

            $smenaDo = $hromadnyForm->getElement('datumSmenyDo');
            $smenaDo->setValue(date('d. m. Y', strtotime(self::$_session->do)));
        }
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->hromadnyForm = $hromadnyForm;      
    }

    public function deleteErrorAction()
    {
        $deleteForm = new Dochazka_Form_DeleteError();
        $error = new Dochazka_Model_ErroryDochazky();
        
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $error->setIdZaznamu($request->getPost('idZaznamu'));
                $error->deleteError();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        } 
        else {
            $id = $this->getRequest()->get('id');        
            $error->setIdZaznamu($id);

            $idZaznamu = $deleteForm->getElement('idZaznamu');
            $idZaznamu->setValue($id);    
            
            $this->view->zaznam = $error->getError();
        }
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->form = $deleteForm;        
    }

    public function deleteUserErrorsAction()
    {
        $deleteForm = new Dochazka_Form_DeleteErrors();
     
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $error = new Dochazka_Model_ErroryDochazky();
                
                $error->setIdUser(self::$_session->idOsoby);
                $error->setIdChip(self::$_session->idCipu);
                $error->deleteUserErrors();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        } 
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->form = $deleteForm;
    }

    public function deleteTempAction()
    {
        $deleteForm = new Dochazka_Form_DeleteTemp();
     
        $request = $this->getRequest();                
        if ($request->isPost()) {   
            
            // v případě odeslaného a zvalidovaného formuláře smažeme záznam
            if ($deleteForm->isValid($request->getPost())) {       
                
                $temp = new Dochazka_Model_AkceDochazky();
                      
                $temp->setIdUser(self::$_session->idOsoby);
                $temp->setIdChip(self::$_session->idCipu);
                $temp->deleteTempActions();                
                     
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'edit', 'dochazka');                     
            }                   
        } 
        
        /**** DATA DO VIEW ****************************************************/
        
        $this->view->uzivatel = self::$_session->uzivatel;    
        $this->view->form = $deleteForm;        
    }
    
    /**
     * Na základě vstupních kritérií a určeného dne určí, zda bude záznam 
     * při hromadném zápisu uložen do DB
     * 
     * @param array $den Záznam o dni
     * @return boolean
     */
    protected function _rozhodniZapsani($den,$opakovani) 
    {
        
        $zapiseme = false;
        
        switch ($den['vikend']) {
            
            // den je víkend
            case true:                    
                // chceme zapsat víkendy
                if (in_array(2,$opakovani)) { $zapiseme = true; }
                break;

            // den je všední den
            case false:
                // chceme zapsat všední dny
                if (in_array(0,$opakovani)) { $zapiseme = true; }
                break;
        }
            
        // pokud jde náhodou o svátek
        if ($den['svatek']) {
            // chceme zapsat svátky
            if (in_array(1,$opakovani)) {
                $zapiseme = true;
            } else {
                $zapiseme = false;
            }                
        }    
        return $zapiseme;        
    }
}
