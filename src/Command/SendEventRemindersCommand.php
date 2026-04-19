<?php

namespace App\Command;

use App\Entity\EventTicket;
use App\Repository\EventTicketRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Send reminder emails for events happening soon.'
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private readonly EventTicketRepository $eventTicketRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly string $appDefaultTimezone,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'minutes',
            null,
            InputOption::VALUE_REQUIRED,
            'Send reminders for events starting within the next N minutes.',
            '1'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $minutes = max(1, (int) $input->getOption('minutes'));
        $timezone = new \DateTimeZone($this->appDefaultTimezone);
        $now = new \DateTimeImmutable('now', $timezone);
        $windowEnd = $now->modify(sprintf('+%d minutes', $minutes));

        $tickets = $this->eventTicketRepository->findTicketsForReminder($now, $windowEnd);

        if ([] === $tickets) {
            $output->writeln(sprintf('No reminders to send for the next %d minutes.', $minutes));

            return Command::SUCCESS;
        }

        $progressBar = new ProgressBar($output, count($tickets));
        $progressBar->start();

        foreach ($tickets as $ticket) {
            if (!$ticket instanceof EventTicket) {
                continue;
            }

            $event = $ticket->getEvent();
            $user = $ticket->getUser();

            if (null === $event || null === $user) {
                continue;
            }

            if (null !== $ticket->getReminderSentAt()) {
                $progressBar->advance();
                continue;
            }

            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@example.com', 'Event Manager'))
                ->to($user->getEmail())
                ->subject(sprintf('Reminder: %s starts soon', $event->getTitle()))
                ->htmlTemplate('event/reminder_email.html.twig')
                ->context([
                    'firstName' => $user->getFirstName(),
                    'event' => $event,
                    'ticketCode' => $ticket->getTicketCode(),
                ]);

            $this->mailer->send($email);

            $ticket->setReminderSentAt(new \DateTimeImmutable('now', $timezone));
            $this->entityManager->flush();

            $progressBar->advance();
        }

        $progressBar->finish();
        $output->writeln('');
        $output->writeln('Reminder emails sent successfully.');

        return Command::SUCCESS;
    }
}
