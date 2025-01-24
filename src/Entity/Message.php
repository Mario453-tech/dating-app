<?php

namespace App\Entity;

use App\Repository\MessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MessageRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Message
{
    public const STATUS_SENT = 'sent';
    public const STATUS_DELIVERED = 'delivered';
    public const STATUS_READ = 'read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $sender = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $receiver = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    private ?string $status = self::STATUS_SENT;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private bool $isDeletedBySender = false;

    #[ORM\Column]
    private bool $isDeletedByReceiver = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $encryptionNonce = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSender(): ?User
    {
        return $this->sender;
    }

    public function setSender(?User $sender): self
    {
        $this->sender = $sender;

        return $this;
    }

    public function getReceiver(): ?User
    {
        return $this->receiver;
    }

    public function setReceiver(?User $receiver): self
    {
        $this->receiver = $receiver;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        if (!in_array($status, [self::STATUS_SENT, self::STATUS_DELIVERED, self::STATUS_READ])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;

        return $this;
    }

    public function markAsRead(): self
    {
        if ($this->status !== self::STATUS_READ) {
            $this->status = self::STATUS_READ;
        }
        return $this;
    }

    public function markAsDelivered(): self
    {
        if ($this->status === self::STATUS_SENT) {
            $this->status = self::STATUS_DELIVERED;
        }
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function isDeletedBySender(): bool
    {
        return $this->isDeletedBySender;
    }

    public function setIsDeletedBySender(bool $isDeletedBySender): self
    {
        $this->isDeletedBySender = $isDeletedBySender;

        return $this;
    }

    public function isDeletedByReceiver(): bool
    {
        return $this->isDeletedByReceiver;
    }

    public function setIsDeletedByReceiver(bool $isDeletedByReceiver): self
    {
        $this->isDeletedByReceiver = $isDeletedByReceiver;

        return $this;
    }

    public function getEncryptionNonce(): ?string
    {
        return $this->encryptionNonce;
    }

    public function setEncryptionNonce(?string $nonce): self
    {
        $this->encryptionNonce = $nonce;
        return $this;
    }
}
