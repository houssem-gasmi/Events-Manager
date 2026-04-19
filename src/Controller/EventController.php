<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventTicket;
use App\Entity\User;
use App\Form\EventType;
use App\Repository\EventRepository;
use App\Service\StripeGateway;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('/events')]
class EventController extends AbstractController
{
    #[Route('', name: 'app_event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findBy([], ['eventDate' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $event = new Event();
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();

            if ($user instanceof User) {
                $event->setOrganizer($user);
            }

            $entityManager->persist($event);
            $entityManager->flush();

            $this->addFlash('success', 'Event created successfully.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'page_title' => 'Create event',
        ]);
    }

    #[Route('/{id}', name: 'app_event_show', methods: ['GET'])]
    public function show(Event $event): Response
    {
        return $this->render('event/show.html.twig', [
            'event' => $event,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $event->getOrganizer()?->getId() !== $user->getId()) {
            $this->addFlash('danger', 'Only the organizer can edit this event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'Event updated successfully.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->render('event/form.html.twig', [
            'form' => $form,
            'event' => $event,
            'page_title' => 'Edit event',
        ]);
    }

    #[Route('/{id}/delete', name: 'app_event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User || $event->getOrganizer()?->getId() !== $user->getId()) {
            $this->addFlash('danger', 'Only the organizer can delete this event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($this->isCsrfTokenValid('delete' . $event->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Event deleted.');
        }

        return $this->redirectToRoute('app_event_index');
    }

    #[Route('/{id}/register', name: 'app_event_register', methods: ['POST'])]
    public function register(Request $request, Event $event, EntityManagerInterface $entityManager, StripeGateway $stripeGateway): Response
    {
        if (!$this->isCsrfTokenValid('register' . $event->getId(), (string) $request->request->get('_token'))) {
            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        if ($event->getOrganizer()?->getId() === $user->getId()) {
            $this->addFlash('danger', 'You cannot join your own event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->hasEnded()) {
            $this->addFlash('danger', 'This event has already ended. Joining is closed.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->hasAttendee($user)) {
            $this->addFlash('info', 'You are already registered for this event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->isFull()) {
            $this->addFlash('danger', 'This event is full.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if (!$event->isPaid()) {
            $event->addAttendee($user);
            $entityManager->flush();
            $this->addFlash('success', 'You are registered for this event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        try {
            $successUrl = $this->generateUrl('app_event_payment_success', [
                'id' => $event->getId(),
            ], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}';

            $checkoutSession = $stripeGateway->createCheckoutSession(
                $event,
                $user,
                $successUrl,
                $this->generateUrl('app_event_show', ['id' => $event->getId()], UrlGeneratorInterface::ABSOLUTE_URL)
            );
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Unable to start the payment session: ' . $exception->getMessage());

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $checkoutUrl = $checkoutSession['url'] ?? null;

        if (!is_string($checkoutUrl) || '' === $checkoutUrl) {
            $this->addFlash('danger', 'Unable to start the payment session: Stripe did not return a checkout URL.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        return $this->redirect($checkoutUrl, 303);
    }

    #[Route('/{id}/checkout/success', name: 'app_event_payment_success', methods: ['GET'])]
    public function paymentSuccess(Request $request, Event $event, EntityManagerInterface $entityManager, StripeGateway $stripeGateway, MailerInterface $mailer): Response
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            return $this->redirectToRoute('app_login');
        }

        $sessionId = (string) $request->query->get('session_id', '');

        if ('' === $sessionId) {
            $this->addFlash('danger', 'Missing Stripe session reference.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        try {
            $checkoutSession = $stripeGateway->retrieveCheckoutSession($sessionId);
        } catch (\Throwable $exception) {
            $this->addFlash('danger', 'Unable to verify the payment: ' . $exception->getMessage());

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $metadataEventId = (int) ($checkoutSession['metadata']['event_id'] ?? 0);
        $metadataUserId = (int) ($checkoutSession['metadata']['user_id'] ?? 0);

        if (($checkoutSession['payment_status'] ?? null) !== 'paid' || $metadataEventId !== $event->getId() || $metadataUserId !== $user->getId()) {
            $this->addFlash('danger', 'Payment verification failed.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->getOrganizer()?->getId() === $user->getId()) {
            $this->addFlash('danger', 'You cannot join your own event.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if ($event->hasEnded()) {
            $this->addFlash('danger', 'This event has already ended. Joining is closed.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        if (!$event->hasAttendee($user) && $event->isFull()) {
            $this->addFlash('danger', 'This event is full and no more registrations are available.');

            return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
        }

        $ticketRepository = $entityManager->getRepository(EventTicket::class);
        $ticket = $ticketRepository->findOneBy(['stripeSessionId' => $sessionId]);

        if (!$ticket instanceof EventTicket) {
            $ticket = (new EventTicket())
                ->setEvent($event)
                ->setUser($user)
                ->setStripeSessionId($sessionId)
                ->setTicketCode($this->generateTicketCode());

            $entityManager->persist($ticket);
        }

        if (!$event->hasAttendee($user) && !$event->isFull()) {
            $event->addAttendee($user);
        }

        $entityManager->flush();

        if (null === $ticket->getConfirmationSentAt()) {
            $this->sendPaymentConfirmationEmail($mailer, $user, $event, $ticket);

            $ticket->setConfirmationSentAt(new \DateTimeImmutable());
            $entityManager->flush();
        }

        $this->addFlash('success', 'Payment confirmed and your ticket has been sent by email.');

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    #[Route('/{id}/unregister', name: 'app_event_unregister', methods: ['POST'])]
    public function unregister(Request $request, Event $event, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('unregister' . $event->getId(), (string) $request->request->get('_token'))) {
            $user = $this->getUser();

            if ($user instanceof User) {
                if ($event->isPaid()) {
                    $this->addFlash('danger', 'Paid events cannot be unregistered. No refunds after payment.');

                    return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
                }

                $event->removeAttendee($user);
                $entityManager->flush();
                $this->addFlash('success', 'You are unregistered from this event.');
            }
        }

        return $this->redirectToRoute('app_event_show', ['id' => $event->getId()]);
    }

    private function sendPaymentConfirmationEmail(MailerInterface $mailer, User $user, Event $event, EventTicket $ticket): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('no-reply@example.com', 'Event Manager'))
            ->to($user->getEmail())
            ->subject(sprintf('Payment confirmed for %s', $event->getTitle()))
            ->htmlTemplate('event/payment_confirmation_email.html.twig')
            ->context([
                'firstName' => $user->getFirstName(),
                'event' => $event,
                'ticketCode' => $ticket->getTicketCode(),
            ]);

        $mailer->send($email);
    }

    private function generateTicketCode(): string
    {
        return 'TICKET-' . strtoupper(bin2hex(random_bytes(5)));
    }
}
