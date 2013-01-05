<?php

/**
 * Formulář pro přidání nebo změnu příchodu v docházce
 */
class Dochazka_Form_PruchodPrichod extends ZendX_JQuery_Form
{
    /**
     * Typy průchodů
     * @var array
     */
    public static $typyPruchodu;    
    
    public function init()
    {        
        /**** Obecné nastavení ************************************************/ 
        
        $typyPruchodu = new Dochazka_Model_TypyPruchodu();       
        
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("pruchodForm");
        $this->setAttrib("id", "form-dochazka-pruchodForm");
        $this->setMethod('post');                       
        
        /**** Element - datum směny *******************************************/
        
        $datumSmeny = new Fc_JQuery_Form_Element_DatePicker('datumSmeny');
        $datumSmeny->setLabel('Datum směny')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));    
        
        /**** Element - datum průchodu ****************************************/
        
        $datumPruchodu = new Fc_JQuery_Form_Element_DatePicker('datumPruchodu');
        $datumPruchodu->setLabel('Datum průchodu')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));                 
        
        /**** Element - čas průchodu ******************************************/
        
        $cas = new Zend_Form_Element_Text('casPruchodu');
        $cas->setLabel('Čas průchodu')       
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        /**** Element - typ průchodu (příchod) ********************************/
        
        $pruchodPrichod = new Zend_Form_Element_Select('typ');
        $pruchodPrichod->setLabel('Typ průchodu')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_NotEmpty);
               
        if (!empty(self::$typyPruchodu)) {
            foreach (self::$typyPruchodu as $typ) {
                
                // přidáme formulářový prvek Option
                $pruchodPrichod->addMultiOption($typ["id"],$typ["nazev"]);                
            }            
        }

        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitPruchod');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Zapsat');        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($datumSmeny,$datumPruchodu,$cas,$pruchodPrichod,$submit));       
    }
}