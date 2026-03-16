
Level 0: complete wipe & rebuild from the ph1/ph2 data we have
   rm */*tsv */all-parsed
   parse ALL
   recreate tables

   v4initialize
   markCompletedCounties

   This is typically a "do once" operation, unless we're intentionally
   rebuilding EVERYTHING from scratch for some (good) reason.

   The remaining levels are done cyclically, as we get in new county
   election data, or new sources of corrections for termlen, voteFor,
   or maxseats.

Level 1: parse any newly added data
   v4parse
   markCompletedCounties

Level 2: completely rebuild the v4elections table
   phase9
   various fix-up scripts

Level 3: dedup and winners

Level 4: Scan all incomplete kinds of jurisdictions.
   If they are now complete, create the v4seats and
   v4incumbents rows.  Mark the jurisdiction as complete.
