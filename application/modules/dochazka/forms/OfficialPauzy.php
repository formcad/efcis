<?php

/**
 * Formulář zatím služí pouze pro potvrzení doplnění hromadné pauzy do oficiální
 * docházky
 */
class Dochazka_Form_OfficialPauzy extends Zend_Form
{
    public function init()
    {        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("doplneniHromadnePauzy");        
        $this->setMethod("post");
        $this->setAttrib('id','form-dochazka-doplneniPauzy');                
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitButton');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Provést');                            
            
        /**** Přidání prvků ***************************************************/
        
        $this->addElement($submit); 
    }
}
