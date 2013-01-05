<?php

/**
 * Formulář pro odstranění konkrétního chybného záznamu docházky
 */
class Dochazka_Form_DeleteError extends Zend_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("deleteErrorForm");
        $this->setAttrib("id", "form-dochazka-deleteErrorForm");
        $this->setMethod('post');            
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'delete-error'),null, true); 
        $this->setAction($action);        
        
        /**** Element - id záznamu ********************************************/
        
        $id = new Zend_Form_Element_Hidden('idZaznamu');
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitDelete');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Odstranit');            
        
        $this->addElements(array($id,$submit));
    }


}
