<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Repository\MonsterRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Ressource API Platform en lecture seule : la démo est publique, aucune écriture n'est exposée
 * (POST/PUT/PATCH/DELETE → 405). Les données viennent des migrations, rejouées à chaque démarrage.
 */
#[ORM\Entity(repositoryClass: MonsterRepository::class)]
#[ApiResource(operations: [new Get(), new GetCollection()])]
class Monster
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    public ?string $name = null;
}
