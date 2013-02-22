<?php

class Dochazka_Form_OfficialPauzaEdit extends Zend_Form
{
    public function init()
    {        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("parametryDochazky");
        $this->setAttrib('id','form-dochazka-zapisOficialniPauzy');                
        $this->setMethod("post");                
        
        /**** Element - délka pauzy *******************************************/
    
        $delkaPreruseni = new Zend_Form_Element_Text('delkaPauzy');
        $delkaPreruseni->setLabel('Celková délka pauzy')  
           ->setRequired(true)
           ->addFilters(array('StringTrim'))            
           ->addValidator('Float', false, array(Zend_Registry::get('Zend_Locale')))
           ->addValidator(new Zend_Validate_NotEmpty);                 
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElement($delkaPreruseni); 
    }
}
