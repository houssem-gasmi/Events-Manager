<?php

namespace App\Entity;

use App\Repository\EventTicketRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventTicketRepository::class)]
#[ORM\Table(name: 'event_tickets')]
#[ORM\UniqueConstraint(name: 'uniq_event_ticket_session', columns: ['stripe_session_id'])]
#[ORM\UniqueConstraint(name: 'uniq_event_ticket_user_event', columns: ['event_id', 'user_id'])]
#[ORM\UniqueConstraint(name: 'uniq_event_ticket_code', columns: ['ticket_code'])]
class EventTicket
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Event $event = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(name: 'stripe_session_id', length: 255)]
    private string $stripeSessionId = '';

    #[ORM\Column(name: 'ticket_code', length: 32)]
    private string $ticketCode = '';

    #[ORM\Column(name: 'confirmation_sent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $confirmationSentAt = null;

    #[ORM\Column(name: 'reminder_sent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $reminderSentAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(Event $event): self
    {
        $this->event = $event;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getStripeSessionId(): string
    {
        return $this->stripeSessionId;
    }

    public function setStripeSessionId(string $stripeSessionId): self
    {
        $this->stripeSessionId = $stripeSessionId;

        return $this;
    }

    public function getTicketCode(): string
    {
        return $this->ticketCode;
    }

    public function setTicketCode(string $ticketCode): self
    {
        $this->ticketCode = $ticketCode;

        return $this;
    }

    public function getConfirmationSentAt(): ?\DateTimeImmutable
    {
        return $this->confirmationSentAt;
    }

    public function setConfirmationSentAt(?\DateTimeImmutable $confirmationSentAt): self
    {
        $this->confirmationSentAt = $confirmationSentAt;

        return $this;
    }

    public function getReminderSentAt(): ?\DateTimeImmutable
    {
        return $this->reminderSentAt;
    }

    public function setReminderSentAt(?\DateTimeImmutable $reminderSentAt): self
    {
        $this->reminderSentAt = $reminderSentAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function __toString(): string
    {
        return $this->ticketCode;
    }
}
