<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260630120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial schema PostgreSQL cleaned';
    }

    public function up(Schema $schema): void
    {
        // USERS
        $this->addSql('
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                name TEXT,
                firstname TEXT,
                email VARCHAR(255),
                password TEXT,
                birthdate INT,
                phone VARCHAR(20),
                role VARCHAR(50),
                subscribe_id INT
            )
        ');

        // CAT
        $this->addSql('
            CREATE TABLE cat (
                id SERIAL PRIMARY KEY,
                specie TEXT,
                color TEXT,
                speciality TEXT
            )
        ');

        // SUBSCRIBE
        $this->addSql('
            CREATE TABLE subscribe (
                id SERIAL PRIMARY KEY,
                name TEXT,
                reservation_date TIMESTAMP,
                expiration_date TIMESTAMP,
                price_month VARCHAR(255),
                address TEXT
            )
        ');

        // GIFTCARD
        $this->addSql('
            CREATE TABLE giftcard (
                id SERIAL PRIMARY KEY,
                name TEXT,
                amount VARCHAR(255),
                expiration_date TIMESTAMP,
                starting_date TEXT,
                message TEXT
            )
        ');

        // SERVICE
        $this->addSql('
            CREATE TABLE service (
                id SERIAL PRIMARY KEY,
                title TEXT,
                description TEXT,
                duration VARCHAR(255),
                price INT
            )
        ');

        // LOCATION
        $this->addSql('
            CREATE TABLE location (
                id SERIAL PRIMARY KEY,
                address TEXT,
                country TEXT,
                city TEXT,
                manager_id INT
            )
        ');

        // MANAGER
        $this->addSql('
            CREATE TABLE manager (
                id SERIAL PRIMARY KEY,
                is_admin BOOLEAN
            )
        ');

        // EXTRA
        $this->addSql('
            CREATE TABLE extra (
                id SERIAL PRIMARY KEY,
                name TEXT,
                price VARCHAR(255)
            )
        ');

        // RESERVATION
        $this->addSql('
            CREATE TABLE reservation (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                service_id INT NOT NULL,
                location_id INT NOT NULL,
                service TEXT,
                cat TEXT,
                date TIMESTAMP,
                hour VARCHAR(255),
                reservation_date TIMESTAMP,
                duration VARCHAR(255),
                price INT
            )
        ');

        // REVIEW
        $this->addSql('
            CREATE TABLE review (
                id SERIAL PRIMARY KEY,
                user_id INT NOT NULL,
                reservation_id INT NOT NULL,
                rate INT,
                comment TEXT
            )
        ');

        // MANY TO MANY

        $this->addSql('
            CREATE TABLE reservation_cat (
                reservation_id INT NOT NULL,
                cat_id INT NOT NULL,
                PRIMARY KEY (reservation_id, cat_id)
            )
        ');

        $this->addSql('
            CREATE TABLE reservation_extra (
                reservation_id INT NOT NULL,
                extra_id INT NOT NULL,
                PRIMARY KEY (reservation_id, extra_id)
            )
        ');

        $this->addSql('
            CREATE TABLE service_cat (
                service_id INT NOT NULL,
                cat_id INT NOT NULL,
                PRIMARY KEY (service_id, cat_id)
            )
        ');

        // FOREIGN KEYS

        $this->addSql('ALTER TABLE users ADD CONSTRAINT fk_users_subscribe FOREIGN KEY (subscribe_id) REFERENCES subscribe(id)');

        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_user FOREIGN KEY (user_id) REFERENCES users(id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_service FOREIGN KEY (service_id) REFERENCES service(id)');
        $this->addSql('ALTER TABLE reservation ADD CONSTRAINT fk_reservation_location FOREIGN KEY (location_id) REFERENCES location(id)');

        $this->addSql('ALTER TABLE review ADD CONSTRAINT fk_review_user FOREIGN KEY (user_id) REFERENCES users(id)');
        $this->addSql('ALTER TABLE review ADD CONSTRAINT fk_review_reservation FOREIGN KEY (reservation_id) REFERENCES reservation(id)');

        $this->addSql('ALTER TABLE reservation_cat ADD CONSTRAINT fk_rc_res FOREIGN KEY (reservation_id) REFERENCES reservation(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_cat ADD CONSTRAINT fk_rc_cat FOREIGN KEY (cat_id) REFERENCES cat(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE reservation_extra ADD CONSTRAINT fk_re_res FOREIGN KEY (reservation_id) REFERENCES reservation(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservation_extra ADD CONSTRAINT fk_re_extra FOREIGN KEY (extra_id) REFERENCES extra(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE service_cat ADD CONSTRAINT fk_sc_service FOREIGN KEY (service_id) REFERENCES service(id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE service_cat ADD CONSTRAINT fk_sc_cat FOREIGN KEY (cat_id) REFERENCES cat(id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE location ADD CONSTRAINT fk_location_manager FOREIGN KEY (manager_id) REFERENCES manager(id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS reservation_extra');
        $this->addSql('DROP TABLE IF EXISTS reservation_cat');
        $this->addSql('DROP TABLE IF EXISTS service_cat');
        $this->addSql('DROP TABLE IF EXISTS review');
        $this->addSql('DROP TABLE IF EXISTS reservation');
        $this->addSql('DROP TABLE IF EXISTS extra');
        $this->addSql('DROP TABLE IF EXISTS location');
        $this->addSql('DROP TABLE IF EXISTS manager');
        $this->addSql('DROP TABLE IF EXISTS service');
        $this->addSql('DROP TABLE IF EXISTS cat');
        $this->addSql('DROP TABLE IF EXISTS giftcard');
        $this->addSql('DROP TABLE IF EXISTS users');
        $this->addSql('DROP TABLE IF EXISTS subscribe');
    }
}