DROP   TABLE IF EXISTS v4deletedSeats;
CREATE TABLE           v4deletedSeats LIKE v4seats;
ALTER  v4deletedSeats ADD COLUMN when datetime    NOT NULL DEFAULT '2000-01-01';
ALTER  v4deletedSeats ADD COLUMN who  varchar(60) NOT NULL DEFAULT '';

DROP   TABLE IF EXISTS v4deletedIncumbents;
CREATE TABLE           v4deletedIncumbents LIKE v4incumbents;
