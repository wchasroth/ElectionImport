DROP   TABLE IF EXISTS s4van2drop;

CREATE TABLE           s4van2drop (
   van_precinct_id   int          NOT NULL, index(van_precinct_id),
   drop_id           int
);
