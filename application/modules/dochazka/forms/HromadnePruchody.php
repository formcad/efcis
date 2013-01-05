<?php

/**
 * Formulář pro přidání hromadných průchodů v docházce
 */
class Dochazka_Form_HromadnePruchody extends ZendX_JQuery_Form
{
    /**
     * Typy průchodů
     * @var array
     */
    public static $typyPruchodu;
    
    public function init()
    {
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("hromadnePruchodyForm");
        $this->setAttrib("id", "form-dochazka-addHromadnePruchody");
        $this->setMethod('post');     
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'add-hromadne-pruchody'),null, true); 
        $this->setAction($action);
        
        /**** Element - datum směny od ****************************************/
        
        $datumSmenyOd = new Fc_JQuery_Form_Element_DatePicker('datumSmenyOd');
        $datumSmenyOd->setLabel('Počáteční datum směny')
            ->setJQueryParam('defaultDate', date('d. m. Y'))     
            ->setValue(date('d. m. Y', strtotime('-1 month')))
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));           
        
        /**** Element - datum směny do ****************************************/
        
        $datumSmenyDo = new Fc_JQuery_Form_Element_DatePicker('datumSmenyDo');
        $datumSmenyDo->setLabel('Koncové datum směny')
            ->setJQueryParam('defaultDate', date('d. m. Y'))     
            ->setValue(date('d. m. Y'))
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));              
        
        /**** Element - typ průchodu ******************************************/
        
        $pruchod = new Zend_Form_Element_Select('typ');
        $pruchod->setLabel('Typ průchodu')
                ->setRequired(true)
                ->addValidator(new Zend_Validate_NotEmpty);
               
        if (!empty(self::$typyPruchodu)) {
            foreach (self::$typyPruchodu as $typ) {
                
                // přidáme formulářový prvek Option
                $pruchod->addMultiOption($typ["id"],$typ["nazev"]);                
            }            
        }        
        
        /**** Element - čas průchodu ******************************************/
                
        $cas = new Zend_Form_Element_Text('casPruchodu');
        $cas->setLabel('Čas průchodu')       
            ->setRequired(true)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array("format" => 'H:i')));
        
        /**** Element - den průchodu ******************************************/
        
        $den = new Zend_Form_Element_Radio('denPruchodu');
        $den->setLabel('Den průchodu')
            ->setRequired(true)
            ->addMultiOptions(array(
                'stejný den jako je den směny',
                'následující den po dnu směny')) 
            ->setValue(0);             
        
        /**** Element - opakování akce ****************************************/
        
        $opakovani = new Zend_Form_Element_MultiCheckbox('opakovani');
        $opakovani->setLabel('Opakování průchodu')
            ->setRequired(true)
            ->addMultiOptions(array(
                'všední dny',
                'svátky',
                'víkendy')) 
            ->setValue(0);           
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('hromadnePruchodySubmit');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Zapsat');            
        
        /**** Přidání elementů ************************************************/
        
        $this->addElements(array($datumSmenyOd,$datumSmenyDo,$pruchod,$cas,$den,
                    $opakovani,$submit));
    }


}
