<?php

/**
 * Controller používaný pro obsluhu požadavků uživatelů systému, kteří
 * mají pro
 * defalut module nastavené oprávnění "employee". Ve Formcadu tak jde o lidi
 * z výroby.
 */

class EmployeeController extends Zend_Controller_Action
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
        self::$_identity = Zend_Auth::getInstance()->getIdentity();             
        self::$_session = new Zend_Session_Namespace('formcad');
        
        if (!isset(self::$_session->idOsoby))
            self::$_session->idOsoby = self::$_identity->id;
        if (!isset(self::$_session->uzivatel))
            self::$_session->uzivatel = self::$_identity->jmeno." ".self::$_identity->prijmeni;
        if (!isset(self::$_session->od))
            self::$_session->od = date('Y-m-d', strtotime('-2 days'));
        if (!isset(self::$_session->do))
            self::$_session->do = date('Y-m-d');
    }
    
    /**
     * Index Action - základní rozhraní informačního systému pro zaměstnance
     */
    public function indexAction()
    {
        /**** Do jakého listu se dostaneme při návratu ************************/

        if (null !== $this->getRequest()->getParam('tab')) {
            $tab = $this->getRequest()->getParam('tab');
        } else {
            $tab = '0';
        }        

        /**** Formulář časových mezí ******************************************/
        
        $form = new Application_Form_TimeLimits();

        $from = $form->getElement('limitFrom');
        $from->setValue(date('d. m. Y', strtotime(self::$_session->od)));
        
        $to = $form->getElement('limitTo');
        $to->setValue(date('d. m. Y', strtotime(self::$_session->do)));        

        /**** Záznamy o rozpracované práci ************************************/
        
        $vyroba = new Vyroba_Model_AkceVyroby();
        $vyroba->setIdUser(self::$_session->idOsoby);
        $aktualniPrace = $vyroba->getAktualniZaznamy();
               
        /**** Výrobní poznámky ************************************************/
        
        $poznamky = new Vyroba_Model_Poznamky();
        $poznamky->setIdUser(self::$_session->idOsoby);
        $nedavnePoznamky = $poznamky->getNedavnePoznamky();
        
        /**** Data do view ****************************************************/
        
        $this->view->idUzivatele = self::$_identity->id;
        $this->view->tab = $tab;
        $this->view->limitForm = $form;
        $this->view->pracovniZaznamy = $aktualniPrace;
        $this->view->poznamky = $nedavnePoznamky;
    }

    /**
     * Změna hesla uživatele do systému
     */
    public function zmenaHeslaAction()
    {
        $form = new Application_Form_PasswordChange;
        $url = $this->_helper->url;
                
        $request = $this->getRequest();
        if ($request->isPost()) {
                     
            // v případě odeslaného a zvalidovaného formuláře
            if ($form->isValid($request->getPost())) {     
                
                // shoduje se heslo s hashem z databáze?
                
                
            }         
        }     
        else {           
            $this->view->form = $form;
        }        

        $this->view->leftNavigation = array(
            array(
                'img' => 'home.png',
                'url' => $url->url(array('module' => 'default',
                                         'controller' => 'employee',
                                         'action' => 'index')),
                'text' => 'Domovská stránka')
        );        
    }    
    
    /**
     * Ajaxové nastavování data směny
     */
    public function ajaxDatumAction()
    {
        $this->_helper->getHelper('layout')->disableLayout();
        $datum = new Fc_Date_Format();
        
        switch ($this->getRequest()->getPost('typ')) {
         
            case 'od':
                self::$_session->od = $datum->dbDateFormat($this->getRequest()->getPost('datum'));
                break;
            
            case 'do':
                self::$_session->do = $datum->dbDateFormat($this->getRequest()->getPost('datum'));
                break;
        
        }
    }


}

