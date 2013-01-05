<?php

/**
 * Formulář pro přidání hromadných přerušení v docházce
 */
class Dochazka_Form_HromadnePreruseni extends ZendX_JQuery_Form
{
    /**
     * Typy přerušení
     * @var array
     */
    public static $typyPreruseni;
    
    public function init()
    {      
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("hromadnePreruseniForm");
        $this->setAttrib("id", "form-dochazka-addHromadnePreruseni");
        $this->setMethod('post');     
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'edit', 
                                  'action'=>'add-hromadne-preruseni'),null, true); 
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
        
        /**** Element - typ přerušení ******************************************/
        
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
        
        /**** Element - délka přerušení ***************************************/
    
        $delkaPreruseni = new Zend_Form_Element_Text('delkaPreruseni');
        $delkaPreruseni->setLabel('Počet hodin')  
           ->setRequired(true)
           ->addFilters(array('StringTrim'))            
           ->addValidator('Float', false, array(Zend_Registry::get('Zend_Locale')))
           ->addValidator(new Zend_Validate_NotEmpty);                 

        /**** Element - opakování akce ****************************************/
        
        $opakovani = new Zend_Form_Element_MultiCheckbox('opakovani');
        $opakovani->setLabel('Opakování přerušení')
            ->setRequired(true)
            ->addMultiOptions(array(
                'všední dny',
                'svátky',
                'víkendy')) 
            ->setValue(0);           
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('hromadnePreruseniSubmit');
        $submit->setRequired(true)
               ->setIgnore(true)
               ->setLabel('Zapsat');            
        
        /**** Přidání elementů ************************************************/
        
        $this->addElements(array($datumSmenyOd,$datumSmenyDo,$preruseni,
                    $delkaPreruseni,$opakovani,$submit));
    }


}
