DROP   TABLE IF EXISTS v4elections;

CREATE TABLE           v4elections (
   id         int         AUTO_INCREMENT PRIMARY KEY,
   year       date        NOT NULL DEFAULT '2000-01-01',  index(year),
   county     tinyint     NOT NULL DEFAULT 0,             index(county),
   region     varchar(40) NOT NULL DEFAULT '', 
   voteFor    tinyint     NOT NULL DEFAULT 0,
   name       varchar(60) NOT NULL DEFAULT '',
   party      char(1)     NOT NULL DEFAULT '',
   votes_C    mediumint   NOT NULL DEFAULT 0,
   votes_D    mediumint   NOT NULL DEFAULT 0,
   votes_R    mediumint   NOT NULL DEFAULT 0,
   votes_O    mediumint   NOT NULL DEFAULT 0,
   votes_T    int         NOT NULL DEFAULT 0,
   org        varchar(10) NOT NULL DEFAULT '',  index(org),
   district   varchar(16) NOT NULL DEFAULT '',  index(district),
   office     varchar(20) NOT NULL DEFAULT '',  index(office),
   subdist    tinyint     NOT NULL DEFAULT 0,   index(subdist),
   termlen    tinyint     NOT NULL DEFAULT 0,
   cycle      smallint    NOT NULL DEFAULT 0,
   partial    tinyint     NOT NULL DEFAULT 0,
   winner     tinyint     NOT NULL DEFAULT 0,
   incumbent  char(1)     NOT NULL DEFAULT '',
   imported   tinyint     NOT NULL DEFAULT 0
);
