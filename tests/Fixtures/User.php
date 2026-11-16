<?php

namespace App\Entity;

use Base\Mapping as ORM;

/**
 * An application's account as a site writes it (its username): App\Entity\User when the bundle is tested alone
 * (tests/bootstrap.php), as Persistence\StatementsParityTest's posts' authors.
 */
#[ORM\Entity]
#[ORM\Table(name: 'userProfile')]
#[\Base\Database\Attribute\DiscriminatorEntry(value: 'user')]
class User extends \Base\Entity\User
{
    #[ORM\Column(length: 180, nullable: true)]
    protected ?string $username = null;

    public function getUsername(): ?string
    {
        return $this->username;
    }
}
