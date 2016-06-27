<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Controller\RestController;
use Eoko\AWSMailBundle\Entity\Mail;

class DefaultController extends RestController
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(/*UserEntity $user, Validator , */Request $request)
    {
        return $this->render('AppBundle::contact.html.twig');
    }
    
    
    /**
     * @Route("/send", name="contact_sub")
     * @Method({"POST"})
     */
    public function contactAction(Request $request)
    {
        $model     = $this->deserialize($request->getContent(), Mail::class);
        $validator = $this->getValidator();
        $errors    = $validator->validate($model);
        
        if (count($errors) <= 0) {
            try {
                $mailer = $this->get('eoko_aws_mail_service');
                $mail   = $mailer->addTemplatedMail('Support', 'romain.dary@eoko.fr',
                                                    'Demande de contact', 'AppBundle::mail.html.twig', [ 'mail' => $model ]);
                $mailer->sendOne($mail);
                
                return $this->renderRest([
                    'success' => true
                ]);
            } catch(\Exception $e) {
                return $this->renderRest(null);
            }
            
        } else {
            return $this->renderRestErrors($errors);
        }
    }
}
