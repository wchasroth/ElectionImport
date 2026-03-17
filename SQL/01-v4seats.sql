DROP   TABLE IF EXISTS v4seats;

CREATE TABLE           v4seats (
   id        int           NOT NULL AUTO_INCREMENT,   primary key(id),
   org       varchar(10)   NOT NULL DEFAULT '',   index (org),
   office    varchar(20)   NOT NULL DEFAULT '',   index (office),
   district  varchar(10)   NOT NULL DEFAULT '',   index (district),
   subdist   tinyint       NOT NULL DEFAULT  0,   index (subdist),
   seatnum   tinyint       NOT NULL DEFAULT  0,
   seatmax   tinyint       NOT NULL DEFAULT  0,
   termlen   tinyint       NOT NULL DEFAULT  0,
   termcycle smallint      NOT NULL DEFAULT  0
);
