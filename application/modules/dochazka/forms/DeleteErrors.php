<?php

/**
 * Formulář pro smazání chyb v docázce konkrétního zaměstnance
 */
class Dochazka_Form_DeleteErrors extends Zend_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("deleteErrorsForm");
        $this->setAttrib("id", "form-dochazka-deleteErrorsForm");
        $this->setMethod('post');            
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-user-errors'),null, true); 
        $this->setAction($action);        

        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitDelete');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Smazat');            
        
        $this->addElement($submit);
    }


}
