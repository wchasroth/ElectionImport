DROP   TABLE IF EXISTS s4precincts;

CREATE TABLE           s4precincts (
   precinct_id int      NOT NULL DEFAULT 0, primary key(precinct_id),
   county_id   tinyint  NOT NULL DEFAULT 0,
   juris_id    int      NOT NULL DEFAULT 0,
   ward        smallint NOT NULL DEFAULT 0,
   pct         smallint NOT NULL DEFAULT 0
);

