<?php

/**
 * Formulář pro smazání konkrétní akce docházky
 */
class Dochazka_Form_DeleteAction extends Zend_Form
{

    public function init()
    {
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("deleteActiondForm");
        $this->setAttrib("id", "form-dochazka-deleteActionForm");
        $this->setMethod('post');            
        
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

