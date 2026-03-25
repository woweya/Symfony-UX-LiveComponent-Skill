<?php
// ============================================================
// Entity Broadcasting with Mercure - Common Patterns
// ============================================================

// === 1. Basic Entity Broadcasting ===

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity]
#[Broadcast]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $content;

    #[ORM\Column(length: 100)]
    private string $author;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    // Constructor
    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // Getters and setters...
    public function getId(): ?int { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $content): static { $this->content = $content; return $this; }
    public function getAuthor(): string { return $this->author; }
    public function setAuthor(string $author): static { $this->author = $author; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}


// === 2. Advanced Broadcasting with Custom Topics ===

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

// Multiple broadcast configurations for different views
#[Broadcast(
    topics: ['@="book_detail_" ~ entity.getId()'],
    template: 'broadcast/book_detail.stream.html.twig',
    private: true
)]
#[Broadcast(
    topics: ['books_catalog'],
    template: 'broadcast/book_catalog.stream.html.twig'
)]
#[ORM\Entity]
class Book
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(type: 'text')]
    private string $description;

    // Getters/setters...
    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }
    public function getDescription(): string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }
}


// === 3. Programmatic Mercure Publishing ===

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Attribute\Route;

class NotificationController extends AbstractController
{
    #[Route('/send-notification', name: 'send_notification', methods: ['POST'])]
    public function send(HubInterface $hub): Response
    {
        // Publish a Turbo Stream update to all subscribers of the 'notifications' topic
        $update = new Update(
            'notifications',
            $this->renderView('notification/stream.html.twig', [
                'message' => 'System maintenance scheduled for tonight.',
                'type' => 'warning',
            ])
        );

        $hub->publish($update);

        return $this->json(['status' => 'published']);
    }

    #[Route('/chat/{roomId}/message', name: 'chat_message', methods: ['POST'])]
    public function chatMessage(int $roomId, HubInterface $hub): Response
    {
        // Private update — only authorized subscribers receive it
        $update = new Update(
            ["chat_room_{$roomId}"],
            $this->renderView('chat/message_stream.html.twig', [
                'message' => 'Hello from the server!',
                'roomId' => $roomId,
            ]),
            true // private: requires authorization
        );

        $hub->publish($update);

        return $this->json(['status' => 'sent']);
    }
}