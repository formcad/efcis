<?php

/**
 * Formulář pro přidání nebo změnu přerušení v docházce
 */
class Dochazka_Form_Preruseni extends ZendX_JQuery_Form
{
    /**
     * Typy přerušení
     * @var array
     */
    public static $typyPreruseni;

    public function init()
    {           
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("preruseniForm");
        $this->setAttrib("id", "form-dochazka-preruseniForm");
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
    
        $delkaPreruseni = new Zend_Form_Element_Text('delkaPreruseni');
        $delkaPreruseni->setLabel('Počet hodin')  
           ->setRequired(true)
           ->addFilters(array('StringTrim'))            
           ->addValidator('Float', false, array(Zend_Registry::get('Zend_Locale')))
           ->addValidator(new Zend_Validate_NotEmpty);                 

        
        /**** Element - typ přerušení *****************************************/
        
        $preruseni = new Zend_Form_Element_Select('typ');
        $preruseni->setLabel('Typ přerušení')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_NotEmpty);

        if (!empty(self::$typyPreruseni)) {
            foreach (self::$typyPreruseni as $typ) {
                
                // přidáme formulářový prvek Option
                $preruseni->addMultiOption($typ["id"],$typ["nazev"]);                
            }            
        }
        
        /**** Element - id záznamu ********************************************/
        
        $id = new Zend_Form_Element_Hidden('idZaznamu');
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitPreruseni');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Zapsat');        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($datumSmeny,$delkaPreruseni,$preruseni,$id,$submit));       
    }
}