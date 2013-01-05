<?php

/**
 * Formulář pro ruční zápis výrobních časů
 */
class Vyroba_Form_Vyroba extends ZendX_JQuery_Form
{
    /**
     * Seznam zaměstnanců
     * @var array 
     */
    public static $zamestnanci;
    
    /**
     * Seznam operací
     * @var array 
     */
    public static $operace;
    
    /**
     * Seznam defaultního nastavneí formuláře
     * @var array
     */
    public static $default;
    
    public function init()
    {           
        /**** Inicializace formuláře ******************************************/
        
        $this->setName("form-vyroba-vyrobaForm");
        $this->setAttrib("id", "form-vyroba-vyrobaForm");
        $this->setMethod("post");           

        /**** Element - hidden počet řádků ************************************/
      
        $pocet = new Zend_Form_Element_Hidden('pocetRadku');
        $pocet->setValue(1)
            ->setOrder(49);
        
        /**** Element - hidden ID záznamu *************************************/
      
        $idZaznamu = new Zend_Form_Element_Hidden('idZaznamu');
        $idZaznamu->setOrder(48);               
        
        /**** Element - hidden návratová stránka ******************************/
        
        $navrat = new Zend_Form_Element_Hidden('navrat');
        $navrat->setOrder(53);
        
        /**** Element - button pro přidání pozic ******************************/
        
        $addButton = new Zend_Form_Element_Button('pridejRadek');
        $addButton->setLabel('Přidat políčko')
            ->setOrder(1);
        
        /**** Element - button získání ID pozice ******************************/
        
        $getIdButton = new Zend_Form_Element_Button('zjistiID');
        $getIdButton->setLabel('Neznám ID')
            ->setOrder(2);
                
        
        /**** Element - Pole zaměstnanců **************************************/
       
        $clovek = new Zend_Form_Element_Select('zamestnanec');
        $clovek->setLabel('Zaměstnanec')
            ->setOrder(50)
            ->setValue(self::$default['zamestnanec']);
        
            
        if (!empty(self::$zamestnanci)) {
            foreach (self::$zamestnanci as $zamestnanec) {
               
                if ($zamestnanec['typKarty'] == 1) 
                    $karta = '';
                else $karta = ', druhý stroj';
         
                // přidáme formulářový prvek Option
                $clovek->addMultiOption($zamestnanec['hodnota'],
                    $zamestnanec['prijmeni'].' '.$zamestnanec['jmeno'].$karta);                
            }            
        }
        
        /**** Element - datum směny *******************************************/
        
        $datum = new Fc_JQuery_Form_Element_DatePicker('datum');
        $datum->setLabel('Datum směny')
            ->setJQueryParam('defaultDate', date('d. m. Y'))     
            ->setValue(self::$default['datum'])
            ->setRequired(true)
            ->setOrder(51)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));        
        
        /**** Element - trvání práce ******************************************/
        
        $delka = new Zend_Form_Element_Text('delka');
        $delka->setLabel('Trvání činnosti')
            ->setRequired(true)
            ->setOrder(52)
            ->addFilter('StringTrim')
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator('Float',false,array(Zend_Registry::get('Zend_Locale')));

        /**** Element - Pole operací ******************************************/
       
        $ukony = new Zend_Form_Element_Radio('operace');
        $ukony->setOrder(60)
            ->setValue(self::$default['operace']);
        
        if (!empty(self::$operace)) {
            foreach (self::$operace as $cinnost) {

                // přidáme formulářový prvek Option
                $ukony->addMultiOption($cinnost['id'],$cinnost['nazev']);                                           
            }            
        }        
      
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitVyroba');
        $submit->setRequired(true)
            ->setLabel('Zapsat')
            ->setOrder(65);        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($pocet,$idZaznamu,$addButton,$getIdButton,
            $clovek,$datum,$delka,$ukony,$submit));       
        
        /**** Vytvoření skupin ************************************************/
        
        $this->addDisplayGroup(array(
                $addButton, $getIdButton, $pocet, $idZaznamu
            ), 'dily');
        $this->addDisplayGroup(array(
                $clovek, $datum, $delka, $navrat
            ), 'prace');
        $this->addDisplayGroup(array(
                $ukony, $submit
            ), 'ukony');

    }
    
    
    public function addIdInput($name,$value,$order,$required)
    {
        $element = new Zend_Form_Element_Text($name);
        $element->setBelongsTo('id')
                ->setRequired($required)
                ->setValue($value)
                ->setOrder($order)
                ->addFilter('StringTrim')
                ->addValidator(new Zend_Validate_Between(array('min' => 1,'max' => 1000000)));
        
        return $element;               
    }
}