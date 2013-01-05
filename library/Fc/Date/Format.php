<?php

/**
 * Třída pro správné formátování textu data
 */
class Fc_Date_Format {
    
    /**
     * Zformátuje datum typu YYYY-MM-DD 
     * 
     * @param string $date Vstupní datum
     * @return string Formátované výstupní datum
     */
    public function dateFormat($date) {

        return date('d. m. Y', strtotime($date));
    }
    
    /**
     * Zformátuje datum typu YYYY-MM-DD HH:MM:SS
     * 
     * @param string $date Vstupní datum
     * @return string Formátované výstupní datum
     */
    public function dateTimeFormat($dateTime) {
        
        return date('d. m. Y, H.i', strtotime($dateTime));
    }
    
    /**
     * Zformátuje datum typu YYYY-MM-DD HH:MM:SS
     * 
     * @param string $date Vstupní datum
     * @return string Formátované výstupní datum
     */
    public function timeFormat($dateTime) {
        
        return date('H.i', strtotime($dateTime));
    }
    
    /**
     * Zformátuje datum typu DD. MM. YYYY do databázové podoby
     * 
     * @param string $date Vstupní datum
     * @return string Formátované výstupní datum
     */
    public function dbDateFormat($date) {
        
        return date('Y-m-d', strtotime(str_replace(" ","",$date)));
    }
    
    /**
     * Zformátuje datum a čas do databázové podoby
     * 
     * @param string $date Vstupní datum
     * @param string $time Vstupní čas
     * @return string Formátované výstupní datum
     */
    public function dbDateTimeFormat($date,$time) {
        
        $datum = str_replace(" ","",$date);
        $cas = str_replace(" ","",$time);
        
        return date('Y-m-d H:i', strtotime($datum.' '.$cas));
    }    
    
}
