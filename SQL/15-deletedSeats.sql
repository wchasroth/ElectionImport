DROP   TABLE IF EXISTS v4deletedSeats;
CREATE TABLE           v4deletedSeats LIKE v4seats;
ALTER  TABLE           v4deletedSeats ADD COLUMN whendid datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP;
ALTER  TABLE           v4deletedSeats ADD COLUMN whodid  varchar(60) NOT NULL DEFAULT '';

DROP   TABLE IF EXISTS v4deletedIncumbents;
CREATE TABLE           v4deletedIncumbents LIKE v4incumbents;
