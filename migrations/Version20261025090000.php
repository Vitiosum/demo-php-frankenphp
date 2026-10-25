<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261025090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Données de démo : quelques monstres de plus pour l\'API en lecture seule.';
    }

    public function up(Schema $schema): void
    {
        foreach (['Elephpant', 'Caddy', 'Mercure', 'Le Worker', 'Opcache', 'Composer'] as $name) {
            $this->addSql('INSERT INTO monster (name) VALUES (?)', [$name]);
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM monster WHERE name IN ('Elephpant', 'Caddy', 'Mercure', 'Le Worker', 'Opcache', 'Composer')");
    }
}
