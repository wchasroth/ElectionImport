https://docs.google.com/spreadsheets/d/1uuRfymuS3-Hxqyvw-KGAa8aUpnaXert6a_HltdyazbQ/edit?gid=114503377#gid=114503377

Finding a voter's drop-box(es):

Start with their county, juris_id, wardpct from the s4streets table.

Split ward/pct (wppp) into ward and pct.

From s4precincts, use county,juris,ward,pct to find precinct_id.

From s4van2drop, use (van_)precinct_id to find drop_id(s!)

From s4dropboxes, find all matching rows (address, directions, hours) for
   the matching drop_ids.
