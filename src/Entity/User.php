<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180)]
    private ?string $username = null;

    #[ORM\Column]
    private ?string $password = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $gender = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $seekingGender = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profilePhoto = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;

    #[ORM\Column(type: 'boolean')]
    private bool $forcePasswordChange = false;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column(length: 45, nullable: true)]
    private ?string $lastIpAddress = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $bannedUntil = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isBanned = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Photo::class, orphanRemoval: true)]
    private Collection $photos;

    #[ORM\ManyToMany(targetEntity: Interest::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_interests')]
    private Collection $interests;

    #[ORM\OneToMany(mappedBy: 'blocker', targetEntity: UserBlock::class, orphanRemoval: true)]
    private Collection $blockedUsers;

    private ?string $plainPassword = null;

    public function __construct()
    {
        $this->photos = new ArrayCollection();
        $this->interests = new ArrayCollection();
        $this->blockedUsers = new ArrayCollection();
        $this->roles = [];
        $this->isActive = true;
        $this->isBanned = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;
        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;
        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(?\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getSeekingGender(): ?string
    {
        return $this->seekingGender;
    }

    public function setSeekingGender(?string $seekingGender): static
    {
        $this->seekingGender = $seekingGender;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getProfilePhoto(): ?string
    {
        return $this->profilePhoto;
    }

    public function setProfilePhoto(?string $profilePhoto): self
    {
        $this->profilePhoto = $profilePhoto;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): self
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function isForcePasswordChange(): bool
    {
        return $this->forcePasswordChange;
    }

    public function setForcePasswordChange(bool $forcePasswordChange): self
    {
        $this->forcePasswordChange = $forcePasswordChange;
        return $this;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    public function setLastIpAddress(?string $lastIpAddress): self
    {
        $this->lastIpAddress = $lastIpAddress;
        return $this;
    }

    public function getBannedUntil(): ?\DateTimeInterface
    {
        return $this->bannedUntil;
    }

    public function setBannedUntil(?\DateTimeInterface $bannedUntil): self
    {
        $this->bannedUntil = $bannedUntil;
        return $this;
    }

    public function isBanned(): bool
    {
        if ($this->bannedUntil === null) {
            return $this->isBanned;
        }
        return $this->isBanned && $this->bannedUntil > new \DateTime();
    }

    public function setIsBanned(bool $isBanned): self
    {
        $this->isBanned = $isBanned;
        if (!$isBanned) {
            $this->bannedUntil = null;
        }
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    /**
     * @return Collection<int, Photo>
     */
    public function getPhotos(): Collection
    {
        return $this->photos;
    }

    public function addPhoto(Photo $photo): self
    {
        if (!$this->photos->contains($photo)) {
            $this->photos->add($photo);
            $photo->setUser($this);
        }

        return $this;
    }

    public function removePhoto(Photo $photo): self
    {
        if ($this->photos->removeElement($photo)) {
            // set the owning side to null (unless already changed)
            if ($photo->getUser() === $this) {
                $photo->setUser(null);
            }
        }

        return $this;
    }

    public function getInterests(): Collection
    {
        return $this->interests;
    }

    public function addInterest(Interest $interest): self
    {
        if (!$this->interests->contains($interest)) {
            $this->interests[] = $interest;
        }
        return $this;
    }

    public function removeInterest(Interest $interest): self
    {
        $this->interests->removeElement($interest);
        return $this;
    }

    public function getBlockedUsers(): Collection
    {
        return $this->blockedUsers;
    }

    public function blockUser(User $user, ?string $reason = null): self
    {
        if (!$this->hasBlockedUser($user)) {
            $block = new UserBlock();
            $block->setBlocker($this)
                ->setBlocked($user)
                ->setReason($reason);
            $this->blockedUsers->add($block);
        }

        return $this;
    }

    public function unblockUser(User $user): self
    {
        $this->blockedUsers->filter(function(UserBlock $block) use ($user) {
            return $block->getBlocked() === $user;
        })->map(function(UserBlock $block) {
            $this->blockedUsers->removeElement($block);
        });

        return $this;
    }

    public function hasBlockedUser(User $user): bool
    {
        foreach ($this->blockedUsers as $block) {
            if ($block->getBlocked() === $user) {
                return true;
            }
        }
        return false;
    }

    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function isEnabled(): bool
    {
        return $this->isActive;
    }
}
