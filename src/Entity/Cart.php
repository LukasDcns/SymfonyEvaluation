<?php

namespace App\Entity;

use App\Repository\CartRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=CartRepository::class)
 */
class Cart
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="date")
     */
    private $purchase_date;

    /**
     * @ORM\Column(type="boolean")
     */
    private $paid;

    /**
     * @ORM\OneToOne(targetEntity=CartContent::class, mappedBy="cart")
     */
    private $cartContent;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="carts")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPurchaseDate(): ?\DateTimeInterface
    {
        return $this->purchase_date;
    }

    public function setPurchaseDate(\DateTimeInterface $purchase_date): self
    {
        $this->purchase_date = $purchase_date;

        return $this;
    }

    public function getPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): self
    {
        $this->paid = $paid;

        return $this;
    }

    public function getCartContent(): ?CartContent
    {
        return $this->cartContent;
    }

    public function setCartContent(CartContent $cartContent): self
    {
        // set the owning side of the relation if necessary
        if ($cartContent->getCart() !== $this) {
            $cartContent->setCart($this);
        }

        $this->cartContent = $cartContent;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }
}
