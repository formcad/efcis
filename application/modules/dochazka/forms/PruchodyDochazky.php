<?php

/**
 * Formulář, který se použije pro docházkový terminál
 */
class Dochazka_Form_PruchodyDochazky extends ZendX_JQuery_Form
{
    /**
     * Typy průchodů
     * @var array
     */
    public static $typyPruchodu;
    
    public function init()
    {    
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("pruchodyDochazky");
        $this->setAttrib("id", "form-dochazka-pruchodyDochazky");
        $this->setMethod('post');     
        
        $url = new Zend_Controller_Action_Helper_Url;
        $action = $url->url(array('module' => 'dochazka',
                                  'controller'=>'terminal', 
                                  'action'=>'index'),null, true); 
        $this->setAction($action);
        
        /**** Element - typ průchodu ******************************************/

        if (!empty(self::$typyPruchodu)) {
            foreach (self::$typyPruchodu as $typ) {
                
                $pruchod = new Zend_Form_Element_Button('pruchodButton'.$typ['id']);
                $pruchod->setName($typ['id']);
                $pruchod->removeDecorator('DtDdWrapper');
                $pruchod->setLabel('<img class="dochazka-pruchodButon-image" src="/images/ico48/'.$typ["ikona"].'.png" /><strong>'.$typ["zkratka"].'</strong>');         
                $pruchod->setAttrib("escape", false);          
                $pruchod->setAttrib('class','dochazka-pruchodButon');
                $this->addElement($pruchod);
            }            
        }
        
        /**** Element - Hidden flag, zda je zvolená akce **********************/
    
        $zvolenaAkce = new Zend_Form_Element_Hidden('zvolenaAkce');
        $zvolenaAkce->setRequired(true)
                ->setName('zvolenaAkce')                
                ->addFilters(array('Digits'))               
                ->addValidator('NotEmpty',true,array('messages' => array(
                        'isEmpty' => 'Chybně zvolená akce'
                )));        
        
        /**** Element - Kód čipu **********************************************/
    
        $kodCipu = new Zend_Form_Element_Text('kodCipu');
        $kodCipu
           ->setRequired(true)
           ->addFilters(array('StringTrim'))            
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->removeDecorator('Description');                   
        
        /**** Přidání elementů ************************************************/
        
        $this->addElements(array($kodCipu,$zvolenaAkce));
    }

}
