<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
class Version20151028110227 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE place_point ADD wd1 VARCHAR(255) NOT NULL, ADD wd2 VARCHAR(255) NOT NULL, ADD wd3 VARCHAR(255) NOT NULL, ADD wd4 VARCHAR(255) NOT NULL, ADD wd5 VARCHAR(255) NOT NULL, ADD wd6 VARCHAR(255) NOT NULL, ADD wd7 VARCHAR(255) NOT NULL');

        $this->addSql('UPDATE place_point SET wd1=CONCAT_WS("-", wd1_start, wd1_end), wd2=CONCAT_WS("-", wd2_start, wd2_end), wd3=CONCAT_WS("-", wd3_start, wd3_end), wd4=CONCAT_WS("-", wd4_start, wd4_end), wd5=CONCAT_WS("-", wd5_start, wd5_end), wd6=CONCAT_WS("-", wd6_start, wd6_end), wd7=CONCAT_WS("-", wd7_start, wd7_end)');

        $this->addSql('CREATE TABLE place_point_work_time (id INT AUTO_INCREMENT NOT NULL, place_point INT DEFAULT NULL, created_by INT DEFAULT NULL, edited_by INT DEFAULT NULL, deleted_by INT DEFAULT NULL, week_day SMALLINT NOT NULL, start_hour SMALLINT NOT NULL, start_min SMALLINT NOT NULL, end_hour SMALLINT NOT NULL, end_min SMALLINT NOT NULL, INDEX IDX_78A26861ED3E807 (place_point), INDEX search_idx (week_day, start_hour, start_min, end_hour, end_min), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');

        $placePointCollection = $this->connection->fetchAll('SELECT * FROM place_point');
        foreach ($placePointCollection as $placePoint) {
            for ($i = 1; $i <= 7; $i++) {
                $startHour = preg_replace(array('/:[\d]+/', '/[^\d]/'), '', $placePoint['wd'.$i.'_start']);
                $startMin = preg_replace(array('/[\d]+:/', '/[^\d]/'), '', $placePoint['wd'.$i.'_start']);
                $endHour = preg_replace(array('/:[\d]+/', '/[^\d]/'), '', $placePoint['wd'.$i.'_end']);
                $endMin = preg_replace(array('/[\d]+:/', '/[^\d]/'), '', $placePoint['wd'.$i.'_end']);
                if ($startHour != '' && $startMin != '' && $endHour != '' && $endMin != '') {
                    if ($endHour < $startHour || $endHour == $startHour && $endMin < $startMin) {
                        $this->addSql('INSERT INTO place_point_work_time (place_point, week_day, start_hour, start_min, end_hour, end_min) VALUES('.$placePoint['id'].', '.$i.', '.$startHour.', '.$startMin.', 0, 0)');
                        $this->addSql('INSERT INTO place_point_work_time (place_point, week_day, start_hour, start_min, end_hour, end_min) VALUES('.$placePoint['id'].', '.($i < 7 ? $i + 1: 1).', 0, 0, '.$endHour.', '.$endMin.')');
                    } else {
                        $this->addSql('INSERT INTO place_point_work_time (place_point, week_day, start_hour, start_min, end_hour, end_min) VALUES('.$placePoint['id'].', '.$i.', '.$startHour.', '.$startMin.', '.$endHour.', '.$endMin.')');
                    }
                }
            }
        }
        $this->addSql('ALTER TABLE place_point DROP wd1_start, DROP wd1_end, DROP wd2_start, DROP wd2_end, DROP wd3_start, DROP wd3_end, DROP wd4_start, DROP wd4_end, DROP wd5_start, DROP wd5_end, DROP wd6_start, DROP wd6_end, DROP wd7_start, DROP wd7_end, DROP wd1_end_long, DROP wd2_end_long, DROP wd3_end_long, DROP wd4_end_long, DROP wd5_end_long, DROP wd6_end_long, DROP wd7_end_long');
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE place_point_work_time');
        $this->addSql('ALTER TABLE place_point ADD wd1_start VARCHAR(5) NOT NULL, ADD wd1_end VARCHAR(5) NOT NULL, ADD wd2_start VARCHAR(5) NOT NULL, ADD wd2_end VARCHAR(5) NOT NULL, ADD wd3_start VARCHAR(5) NOT NULL, ADD wd3_end VARCHAR(5) NOT NULL, ADD wd4_start VARCHAR(5) NOT NULL, ADD wd4_end VARCHAR(5) NOT NULL, ADD wd5_start VARCHAR(5) NOT NULL, ADD wd5_end VARCHAR(5) NOT NULL, ADD wd6_start VARCHAR(5) NOT NULL, ADD wd6_end VARCHAR(5) NOT NULL, ADD wd7_start VARCHAR(5) NOT NULL, ADD wd7_end VARCHAR(5) NOT NULL, ADD wd1_end_long VARCHAR(5) DEFAULT NULL, ADD wd2_end_long VARCHAR(5) DEFAULT NULL, ADD wd3_end_long VARCHAR(5) DEFAULT NULL, ADD wd4_end_long VARCHAR(5) DEFAULT NULL, ADD wd5_end_long VARCHAR(5) DEFAULT NULL, ADD wd6_end_long VARCHAR(5) DEFAULT NULL, ADD wd7_end_long VARCHAR(5) DEFAULT NULL');
//        $this->addSql('UPDATE place_point SET wd1=CONCAT_WS("-", wd1_start, wd1_end), wd2=CONCAT_WS("-", wd2_start, wd2_end), wd3=CONCAT_WS("-", wd3_start, wd3_end), wd4=CONCAT_WS("-", wd4_start, wd4_end), wd5=CONCAT_WS("-", wd5_start, wd5_end), wd6=CONCAT_WS("-", wd6_start, wd6_end), wd7=CONCAT_WS("-", wd7_start, wd7_end)');

        $this->addSql('ALTER TABLE place_point DROP wd1, DROP wd2, DROP wd3, DROP wd4, DROP wd5, DROP wd6, DROP wd7');
    }
}
