<?php

/**
 * Formulář pro ruční zápis režijních časů
 */
class Vyroba_Form_Rezie extends ZendX_JQuery_Form
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
        
        $this->setName("form-vyroba-rezieForm");
        $this->setAttrib("id", "form-vyroba-rezieForm");
        $this->setMethod("post");           
        
        /**** Element - hidden ID záznamu *************************************/
      
        $idZaznamu = new Zend_Form_Element_Hidden('idZaznamu');
        $idZaznamu->setOrder(48);
                        
        /**** Element - hidden návratová stránka ******************************/
        
        $navrat = new Zend_Form_Element_Hidden('navrat');
        $navrat->setOrder(54);                
        
        /**** Element - Pole zaměstnanců **************************************/
       
        $clovek = new Zend_Form_Element_Select('zamestnanecRezie');
        $clovek->setLabel('Zaměstnanec')
            ->setOrder(50)
            ->setValue(self::$default['zamestnanec']);
        
            
        if (!empty(self::$zamestnanci)) {
            foreach (self::$zamestnanci as $zamestnanec) {

                // přidáme formulářový prvek Option
                $clovek->addMultiOption($zamestnanec['hodnota'], 
                        $zamestnanec['prijmeni'].' '.$zamestnanec['jmeno']);                
            }            
        }
        
        /**** Element - datum směny *******************************************/
        
        $datum = new Fc_JQuery_Form_Element_DatePicker('datumRezie');
        $datum->setLabel('Datum směny')
            ->setJQueryParam('defaultDate', date('d. m. Y'))     
            ->setValue(self::$default['datum'])
            ->setRequired(true)
            ->setOrder(51)
            ->addFilters(array('StringTrim'))
            ->addValidator(new Zend_Validate_NotEmpty)             
            ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));        
        
        /**** Element - trvání práce ******************************************/
        
        $delka = new Zend_Form_Element_Text('delkaRezie');
        $delka->setLabel('Trvání činnosti')
            ->setRequired(true)
            ->setOrder(52)
            ->addFilter('StringTrim')
            ->addValidator(new Zend_Validate_NotEmpty)
            ->addValidator('Float',false,array(Zend_Registry::get('Zend_Locale')));
        
        /**** Element - poznámka **********************************************/
        
        $poznamka = new Zend_Form_Element_Text('poznamkaRezie');
        $poznamka->setLabel('Poznámka')
            ->setRequired(true)
            ->setAttrib('class', 'vyroba-sirokyInput')
            ->setOrder(53)
            ->addFilter('StringTrim');

        /**** Element - Pole operací ******************************************/
       
        $ukony = new Zend_Form_Element_Radio('operaceRezie');
        $ukony->setOrder(55)
            ->setValue(self::$default['operace']);
        
        if (!empty(self::$operace)) {
            foreach (self::$operace as $cinnost) {

                // přidáme formulářový prvek Option
                $ukony->addMultiOption($cinnost['id'],$cinnost['nazev']);                                           
            }            
        }        
      
        
        /**** Element - odeslání formuláře ************************************/
        
        $submit = new Zend_Form_Element_Submit('submitRezie');
        $submit->setRequired(true)
            ->setLabel('Zapsat')
            ->setOrder(56);        
        
        /**** Přidání prvků ***************************************************/
        
        $this->addElements(array($idZaznamu,$clovek,$datum,$delka,$poznamka,
            $ukony,$submit));       
        
        /**** Vytvoření skupin ************************************************/
        
        $this->addDisplayGroup(array(
                $clovek, $datum, $delka, $poznamka, $idZaznamu, $navrat
            ), 'prace');
        $this->addDisplayGroup(array(
                $ukony, $submit
            ), 'ukony');

    }
}