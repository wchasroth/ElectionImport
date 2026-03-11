DROP   TABLE IF EXISTS v4incumbents;

CREATE TABLE           v4incumbents (
   org       varchar(10)   NOT NULL DEFAULT '',   index (org),
   office    varchar(20)   NOT NULL DEFAULT '',   index (office),
   district  varchar(10)   NOT NULL DEFAULT '',   index (district),
   subdist   tinyint       NOT NULL DEFAULT  0,   index (subdist),
   seatnum   tinyint       NOT NULL DEFAULT  0,
   primary key (org, office, district, subdist, seatnum),

   seatmax   tinyint       NOT NULL DEFAULT  0,
   termlen   tinyint       NOT NULL DEFAULT  0,
   termcycle smallint      NOT NULL DEFAULT  0,
   role      varchar(16)   NOT NULL DEFAULT '',
   elected   date          NOT NULL DEFAULT '2000-01-01',
   party     char(1)       NOT NULL DEFAULT '',
   votes_C   int           NOT NULL DEFAULT  0,
   votes_D   int           NOT NULL DEFAULT  0,
   votes_R   int           NOT NULL DEFAULT  0,
   votes_O   int           NOT NULL DEFAULT  0,
   votes_T   int           NOT NULL DEFAULT  0,
   web       varchar(200)  NOT NULL DEFAULT '',
   email     varchar(100)  NOT NULL DEFAULT '',
   phone     varchar(36)   NOT NULL DEFAULT '',
   address   varchar(240)  NOT NULL DEFAULT '',
   num2elect tinyint       NOT NULL DEFAULT  0,
   county    tinyint       NOT NULL DEFAULT  0,
   resigned  char(1)       NOT NULL DEFAULT '',
   partial   tinyint       NOT NULL DEFAULT  0,
   headshot  varchar(160)  NOT NULL DEFAULT '',
   status    varchar(20)   NOT NULL DEFAULT ''
);
