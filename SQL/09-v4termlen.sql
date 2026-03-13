DROP   TABLE IF EXISTS v4termlen;

CREATE TABLE           v4termlen (
   org       varchar(10)   NOT NULL DEFAULT '',   index (org),
   office    varchar(20)   NOT NULL DEFAULT '',   index (office),
   district  varchar(10)   NOT NULL DEFAULT '',   index (district),
   subdist   tinyint       NOT NULL DEFAULT  0,   index (subdist),
   termlen   tinyint       NOT NULL DEFAULT  0,
   primary key (org, office, district, subdist)
);
