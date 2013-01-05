<?php

/**
 * Formulář pro přidání nebo změnu příplatku v docházce
 */
class Dochazka_Form_Priplatek extends ZendX_JQuery_Form
{
    /**
     * Typy příplatků
     * @var array
     */
    public static $typyPriplatku;
    
    public function init()
    {                
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("priplatekForm");
        $this->setAttrib("id", "form-dochazka-priplatekForm");
        $this->setMethod("post");                       
        
        /**** Element - datum směny *******************************************/
        
        $datumSmeny = new Fc_JQuery_Form_Element_DatePicker('datumSmeny');
        $datumSmeny->setLabel('Datum směny')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     

           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));    
        
        /**** Element - délka přerušení ***************************************/
        
        $delkaPriplatek = new Zend_Form_Element_Text('delkaPriplatku');
        $delkaPriplatek->setLabel('Počet hodin')  
           ->setRequired(true)
           ->addFilters(array('StringTrim'))            
           ->addValidator('Float', false, array(Zend_Registry::get('Zend_Locale')))
           ->addValidator(new Zend_Validate_NotEmpty);         
        
        /**** Element - typ příplatku *****************************************/
        
        $priplatek = new Zend_Form_Element_Select('typ');
        $priplatek->setLabel('Typ příplatku')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_NotEmpty);
               
        if (!empty(self::$typyPriplatku)) {
            foreach (self::$typyPriplatku as $typ) {
                
                // přidáme formulářový prvek Option
                $priplatek->addMultiOption($typ["id"],$typ["nazev"]);                
            }            
        }
        
        /**** Element - id záznamu ********************************************/
        
        $id = new Zend_Form_Element_Hidden('idZaznamu');
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitPriplatek');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Zapsat');        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($datumSmeny,$delkaPriplatek,$priplatek,$id,$submit));       
    }
}