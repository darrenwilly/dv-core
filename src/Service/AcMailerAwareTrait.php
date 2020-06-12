<?php
namespace DV\Service;

use AcMailer\Service\MailService;
use DV\Mvc\Service\ServiceLocatorFactory;


trait AcMailerAwareTrait
{
    protected $mailer ;

    /**
     * Fetch the mail server that was configured on Engine Method
     * @return MailService
     */
    public function getMailer()
    {
        if(null == $this->mailer)    {
            $mailer = $this->getEngine() ;
            $this->setMailer($mailer) ;
        }
        return $this->mailer;
    }
    public function setMailer($mailer)
    {
        $this->mailer = $mailer ;
        return $this;
    }

    /**
     * @return MailService|\Laminas\ServiceManager\ServiceManager
     * @throws \Exception
     */
    protected function getEngine($service='acmailer.mailservice.default')
    {
        return ServiceLocatorFactory::getLocator(sprintf('%s' , $service));
    }

    /*
     * @return \LaminasTwig\Renderer\TwigRenderer
     */
    public function getTemplateRenderer()
    {
        return ServiceLocatorFactory::getLocator('mailviewrenderer') ;
    }
}