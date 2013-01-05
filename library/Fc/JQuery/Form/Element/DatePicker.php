<?php


/**
 * @see ZendX_JQuery_Form_Element_DatePicker
 */
require_once 'ZendX/JQuery/Form/Element/DatePicker.php';

/**
 * Česká lokalizace JQuery Datepickeru a defaultní nastavení jiné než v originálu
 */
class Fc_JQuery_Form_Element_Datepicker extends ZendX_JQuery_Form_Element_DatePicker
{
    public function __construct ($label)
    {
        parent::__construct($label, array('label'=> $label));
                
        $dayNamesMin = array('ne', 'po', 'út', 'st', 'čt', 'pá', 'so'); 
        $monthDaysMin = array('Leden','Únor','Březen','Duben','Květen','Červen',
            'Červenec','Srpen','Září','Říjen','Listopad','Prosinec');     
          
        $this->setJQueryParam("dateFormat", 'dd. mm. yy')
            ->setJQueryParam("dayNamesMin", $dayNamesMin)
            ->setJQueryParam("firstDay", 1)            
            ->setJQueryParam("gotoCurrent", false)
            ->setJQueryParam("autoSize", true) 
            ->setJQueryParam("monthNames", $monthDaysMin)
            ->setJqueryParam("weekHeader", "týd")
            ->setJqueryParam("showWeek", true);
    }
}
