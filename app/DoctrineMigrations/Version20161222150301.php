<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20161222150301 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('UPDATE dish SET name_to_nav=name');
        $this->addSql('UPDATE dish_option SET name_to_nav=name');
        $this->addSql('UPDATE dish_unit SET name_to_nav=name');
        $this->addSql('UPDATE order_details SET name_to_nav=CONCAT_WS(\' \', dish_name, dish_unit_name)');
        $this->addSql('UPDATE order_details_options SET name_to_nav=dish_name');

    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs

    }
}
