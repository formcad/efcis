<?php

class Vyroba_PoznamkaController extends Zend_Controller_Action
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
        // action body
    }
    
    /**
     * Přidnání nové poznámky k výrobě. Poznámku přidávají konkrétní zaměstnanci
     * k dílům, které vyráběli nebo obecně k výrobě jako takové 
     */
    public function addPoznamkaAction()
    {        
        $form = $this->_getFormPoznamky();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($form->isValid($request->getPost())) {     
                
                $poznamky = new Vyroba_Model_Poznamky();
  
                $poznamky->setIdUser(self::$_session->idOsoby);                
                $poznamky->setText($request->getPost('text'));
                
                if (strlen($request->getPost('pozice')) > 0) {
                    $poznamky->setIdPozice($request->getPost('pozice'));
                }
                
                $poznamky->addPoznamka();
                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'employee', 'default', array('tab' => 1));
            } 
        }
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'home.png',
                'url' => $url->url(array('module' => 'default',
                                            'controller' => 'employee',
                                            'action' => 'index')),
                'text' => 'Domů')
        );             
        
        $this->view->form = $form;
    }
    
    /**
     * Změna výrobní poznámky 
     */
    public function editPoznamkaAction()
    {
        $form = $this->_getFormPoznamky();
        $poznamky = new Vyroba_Model_Poznamky();
        
        $request = $this->getRequest();
        if ($request->isPost()) {
            
            // v případě odeslaného a zvalidovaného formuláře zapíšeme data
            if ($form->isValid($request->getPost())) {     

                $poznamky->setId($request->getPost('idPoznamky'));
                $poznamky->setIdUser(self::$_session->idOsoby);                
                $poznamky->setText($request->getPost('text'));
               
                if (strlen($request->getPost('pozice')) > 0) {
                    $poznamky->setIdPozice($request->getPost('pozice'));
                }
                
                $poznamky->changePoznamka();
                
                // a skočíme zpátky na indexAction
                $this->_helper->redirector('index', 'employee', 'default', array('tab' => 1));
            } 
        } 
        // vyplníme data pro změnu
        else {
            $poznamky->setId($this->getRequest()->get('id'));
            $zaznam = $poznamky->getPoznamka();             
            
            $data = array(
                'idPoznamky' => $this->getRequest()->get('id'),
                'pozice' => $zaznam['id_pozice'],
                'text' => $zaznam['text']
            );
            $form->populate($data);
        }
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'home.png',
                'url' => $url->url(array('module' => 'default',
                                            'controller' => 'employee',
                                            'action' => 'index')),
                'text' => 'Domů')
        );             
        
        $this->view->form = $form;        
    }
    
    /**
     * Vrátí poznámkový formulář - společná funkcionalita přidnání a změny
     * poznámek
     * 
     * @return \Vyroba_Form_Poznamka
     */
    protected function _getFormPoznamky() {

        $session = new Zend_Session_Namespace('formcad');
        
        $vyroba = new Vyroba_Model_AkceVyroby;
        $vyroba->setIdUser($session->idOsoby);
        $vyroba->setDateFrom(date('Y-m-d 0:00:00',strtotime('-5 days')));
        $vyroba->setDateTo(date('Y-m-d 23:59:59'));
        $vyroba->setZapis('libovolny');
        
        Vyroba_Form_Poznamka::$polePozic = $vyroba->getAkce();               
        
        return new Vyroba_Form_Poznamka();                
    }
}
