<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(Request $request, MailerInterface $mailer, RateLimiterFactoryInterface $contactLimiter): Response
    {
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $limiter = $contactLimiter->create($request->getClientIp() ?? '0.0.0.0');
            if (!$limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'Trop de messages envoyés. Veuillez patienter avant de réessayer.');
                return $this->redirectToRoute('app_contact');
            }
            $data = $form->getData();

            $email = (new TemplatedEmail())
                ->from(new Address('noreply@chateau-belleville.fr', 'Château de Belleville'))
                ->to('chateaudebelleville@gmail.com')
                ->replyTo($data['email'])
                ->subject(sprintf('[Contact] %s - %s %s', $data['sujet'], $data['prenom'], $data['nom']))
                ->htmlTemplate('email/contact.html.twig')
                ->context([
                    'prenom'    => $data['prenom'],
                    'nom'       => $data['nom'],
                    'senderEmail' => $data['email'],
                    'telephone' => $data['telephone'] ?? null,
                    'sujet'     => $data['sujet'],
                    'message'   => $data['message'],
                ]);

            try {
                $mailer->send($email);
                $this->addFlash('success', 'Votre message a bien été envoyé. Nous vous répondrons dans les plus brefs délais.');
            } catch (TransportExceptionInterface) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi. Veuillez réessayer ou nous contacter par téléphone.');
            }

            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form,
        ]);
    }
}
