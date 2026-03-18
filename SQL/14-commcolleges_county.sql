DROP   TABLE IF EXISTS v4commcolleges_county;
CREATE TABLE           v4commcolleges_county (
   id        smallint NOT NULL DEFAULT 0, index(id),
   county_id tinyint  NOT NULL DEFAULT 0, index(county_id),
   primary key (id, county_id)
);
