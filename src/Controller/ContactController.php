<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $email = (new Email())
                ->from('noreply@chateau-belleville.fr')
                ->to('chateaudebelleville@gmail.com')
                ->replyTo($data['email'])
                ->subject(sprintf('[Contact] %s - %s %s', $data['sujet'], $data['prenom'], $data['nom']))
                ->text(sprintf(
                    "Nouveau message de contact\n\n"
                    . "Nom : %s %s\n"
                    . "Email : %s\n"
                    . "Téléphone : %s\n"
                    . "Sujet : %s\n\n"
                    . "Message :\n%s",
                    $data['prenom'],
                    $data['nom'],
                    $data['email'],
                    $data['telephone'] ?? 'Non renseigné',
                    $data['sujet'],
                    $data['message'],
                ));

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
