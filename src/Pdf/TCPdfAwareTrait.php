<?php
namespace DV\Pdf;

use TCPDF ;

if (!defined('K_PATH_FONTS')) {
    define ('K_PATH_FONTS', APPLICATION_DATA_PATH.'/fonts/');
}

trait TCPdfAwareTrait 
{

    public function getEngine()
    {
        return new TCPDF() ;        
    }


    public function getEngineVendorDir()
    {
        return VENDOR_PATH.DIRECTORY_SEPARATOR.'tecnickcom'.DIRECTORY_SEPARATOR.'tcpdf' ;
    }
    
    public function setProperties(TCPDF &$pdf , $options=[])
    {
        $engine_vendor_dir = $this->getEngineVendorDir() ;
        require_once $engine_vendor_dir.DIRECTORY_SEPARATOR.'tcpdf_autoconfig.php' ;
        
        if(! isset($options['creator']))    {
            $options['creator'] = PDF_CREATOR ;
        }
        
        if(! isset($options['author']))    {
            $options['author'] = PROJECT_CLIENT ;
        }
        
        if(! isset($options['subject']))    {
            $options['subject'] = PROJECT_DESCRIPTION ;
        }
        
        if(! isset($options['title']))    {
            $options['title'] = PROJECT_DESCRIPTION ;
        }

        ##set document information
        $pdf->SetCreator($options['creator']);
        $pdf->SetAuthor($options['author']);
        $pdf->SetTitle($options['title']);
        $pdf->SetSubject($options['subject']);
        $pdf->SetKeywords($this->getDefaultKeywords($options));
    }
    
    public function setLang(TCPDF $pdf)
    {
        $lang_dir = $this->getEngineVendorDir().DIRECTORY_SEPARATOR.'examples'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'eng.php' ;
        ##set some language-dependent strings (optional)
        if (@file_exists($lang_dir)) {
            require_once($lang_dir);
            ##
            if(! isset($l))    {
                $l['a_meta_charset'] = 'UTF-8';
                $l['a_meta_dir'] = 'ltr';
                $l['a_meta_language'] = 'en'; 
                $l['w_page'] = 'page';
            }
            $pdf->setLanguageArray($l);
        }
    }

    public function getDefaultKeywords($options)
    {
        $keywords = 'NIA, BGM, 2017 , architect, nigeria , year, institute' ;
        ##
        if(isset($options['keywords']))    {
            $keywords .= $options['keywords'] ;
        }
        return $keywords ;
    }

    public function installFonts()
    {
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/arial.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/arial.ttf') ;
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/arialnb.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/arialnb.ttf') ;
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/arial_narrow.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/arial_narrow.ttf') ;
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/impact_regular.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/impact_regular.ttf') ;
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/calibri.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/calibri.ttf') ;
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/bebasNeue_bold.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/bebasNeue_bold.ttf');
        }
        if(! file_exists(APPLICATION_DATA_PATH.'/fonts/bebasNeue_book.php'))    {
            \TCPDF_FONTS::addTTFfont(APPLICATION_DATA_PATH.'/fonts/bebasNeue_book.ttf');
        }
    }
}