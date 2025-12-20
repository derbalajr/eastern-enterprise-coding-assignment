<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251219221333 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create countries table with currency embeddable';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('
            CREATE TABLE countries (
                id INT AUTO_INCREMENT NOT NULL,
                uuid VARCHAR(36) NOT NULL,
                name VARCHAR(255) NOT NULL,
                region VARCHAR(255) DEFAULT NULL,
                sub_region VARCHAR(255) DEFAULT NULL,
                demonym VARCHAR(255) DEFAULT NULL,
                population BIGINT DEFAULT NULL,
                independent TINYINT(1) DEFAULT NULL,
                flag VARCHAR(500) DEFAULT NULL,
                currency_name VARCHAR(255) DEFAULT NULL,
                currency_symbol VARCHAR(10) DEFAULT NULL,
                created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\',
                UNIQUE INDEX UNIQ_5D66EBADD17F50A6 (uuid),
                PRIMARY KEY(id)
            ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE countries');
    }
}
