<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'events')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $title = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank]
    private string $description = '';

    #[ORM\Column(type: 'datetime_immutable')]
    #[Assert\NotNull]
    #[Assert\GreaterThanOrEqual('now', message: 'Event date must be in the future.')]
    private \DateTimeImmutable $eventDate;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 180)]
    private string $location = '';

    #[ORM\Column]
    #[Assert\Positive]
    private int $participantLimit = 1;

    #[ORM\Column]
    #[Assert\PositiveOrZero]
    private int $price = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Category $category = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $organizer = null;

    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'registeredEvents')]
    #[ORM\JoinTable(name: 'event_participants')]
    private Collection $attendees;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->attendees = new ArrayCollection();
        $this->eventDate = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getEventDate(): \DateTimeImmutable
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeImmutable $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    public function setLocation(string $location): self
    {
        $this->location = $location;

        return $this;
    }

    public function getParticipantLimit(): int
    {
        return $this->participantLimit;
    }

    public function setParticipantLimit(int $participantLimit): self
    {
        $this->participantLimit = $participantLimit;

        return $this;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function isPaid(): bool
    {
        return $this->price > 0;
    }

    public function hasEnded(): bool
    {
        return $this->eventDate <= new \DateTimeImmutable();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getOrganizer(): ?User
    {
        return $this->organizer;
    }

    public function setOrganizer(?User $organizer): self
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getAttendees(): Collection
    {
        return $this->attendees;
    }

    public function addAttendee(User $user): self
    {
        if (!$this->attendees->contains($user)) {
            $this->attendees->add($user);
        }

        return $this;
    }

    public function hasAttendee(User $user): bool
    {
        return $this->attendees->contains($user);
    }

    public function removeAttendee(User $user): self
    {
        $this->attendees->removeElement($user);

        return $this;
    }

    public function getAttendeesCount(): int
    {
        return $this->attendees->count();
    }

    public function getAvailableSeats(): int
    {
        return max(0, $this->participantLimit - $this->getAttendeesCount());
    }

    public function isFull(): bool
    {
        return $this->getAvailableSeats() === 0;
    }

    public function __toString(): string
    {
        return $this->title;
    }
}
