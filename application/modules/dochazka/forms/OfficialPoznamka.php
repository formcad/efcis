<?php

/**
 * Formulář pro přidání nebo změnu poznámky u konkrétního dne oficiální docházky
 */
class Dochazka_Form_OfficialPoznamka extends Zend_Form
{
    public function init()
    {        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("parametrySummary");
        $this->setAttrib("id", "form-dochazka-oficialniPoznamka");
        $this->setMethod("post");      
        
        /**** Element - poznámka **********************************************/
        
        $poznamka = new Zend_Form_Element_Text('poznamka');
        $poznamka->setLabel('Poznámka')
            ->setRequired(true)       
            ->setAttrib("id", "form-dochazka-oficialniPoznamka-poznamka")
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty);
        
        /**** Přidání prvku ***************************************************/
        
        $this->addElements(array($poznamka));         
    }
}
