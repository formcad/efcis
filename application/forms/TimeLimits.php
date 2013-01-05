<?php

class Application_Form_TimeLimits extends ZendX_JQuery_Form
{

    public function init()
    {
        $this->setName("timeLimits");
        $this->setAttrib("id", "form-timeLimits");
        $this->setMethod('post');
          
        $limitFrom = new Fc_JQuery_Form_Element_DatePicker('limitFrom');
        $limitFrom->setLabel('Od')
           ->setJQueryParam('defaultDate', date('d. m. Y', strtotime("-1 week")))     
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));    

        $limtTo = new Fc_JQuery_Form_Element_DatePicker('limitTo');
        $limtTo->setLabel('Do')
           ->setJQueryParam('defaultDate', date('d. m. Y'))     
           ->setRequired(true)
           ->addFilters(array('StringTrim'))
           ->addValidator(new Zend_Validate_NotEmpty)             
           ->addValidator(new Zend_Validate_Date(array('format' => 'dd. mm. yy')));            

        $this->addElements(array($limitFrom,$limtTo));      
    }
}
