<?php

/**
 * Formulář pro smazání temporary akcí konkrétního zaměstnance
 */
class Dochazka_Form_DeleteTemp extends Zend_Form {
    
    public function init() {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("deleteTempForm");
        $this->setAttrib("id", "form-dochazka-deleteTemp");
        $this->setMethod('post');            
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-temp'),null, true); 
        $this->setAction($action);        

        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitDelete');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Smazat');            
        
        $this->addElement($submit);        
    }
}