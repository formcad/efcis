<?php

class Dochazka_SummaryController extends Zend_Controller_Action
{

    public function init()
    {
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $form = new Dochazka_Form_SummaryParameters();
        $this->view->form = $form;
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'index',
                                         'action' => 'index')),
                'text' => 'Docházka')
        );
    }

    public function viewAction()
    {
        $this->view->datum = $this->getRequest()->getParam('datum');
        
        $url = $this->_helper->url;
        $this->view->leftNavigation = array(
            array(
                'img' => 'modul-dochazka.png',
                'url' => $url->url(array('module' => 'dochazka',
                                         'controller' => 'summary',
                                         'action' => 'index')),
                'text' => 'Sumář')
        );        
    }

}

