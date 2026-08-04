#!/usr/bin/env python3.9

#---mivoter phase3 parsing, JSON -> TSV.
#
#   Installation notes:
#      1. Ensure python development environment is installed
#         (e.g. python39-devel)
#      2. pip install pandas
#------------------------------------------------

import argparse
import json 
import pandas as pd
import sys
from pathlib import Path

def party_to_p(party):
    if party == None or len(party)==0:
        return '?'
    party = party[0:5]
    party = party.lower()
    pmap = { 'democ':  'D',
             'green':  'G',
             'liber':  'L',
             'natur':  'A',
             'no pa':  'N',
             'nonpa':  'N',
             'repub':  'R',
             'u.s. ':  'T',
             'u. s.':  'T',
             'worki':  'C',
             'write':  'W',
             'propo':  ''
    }
    return pmap[party]

def phase3(json_text, countyID):
    json_data = json.loads(json_text)

    # Create TSV with these columns:
    # yyyy-mm-dd   county#   title   voteFor#   candidateName   partyLetter   #votes
    list_of_dicts = []
    date = f"{json_data['electionDate']}"

    # For each office or issue...
    for j in range(len(json_data['results']['ballotItems'])):
        # Shorthand for the subtree of one election:
        Election = json_data['results']['ballotItems'][j]
        office = f"{Election['name']}"
        vote_for = f"{Election['voteFor']}"

        pctinfo = ""
        if (len(Election['ballotOptions']) > 0):
           pctResults = Election['ballotOptions'][0]['precinctResults']
           if (len(pctResults) > 0):
              pctinfo = Election['ballotOptions'][0]['precinctResults'][0]['name']

        commaPos = pctinfo.find(",")
        if (commaPos > 0):
           pctinfo = pctinfo[0:commaPos]
        office = office + " {" + pctinfo + "}"

        # For each candidate or choice...
        for i in range(len(Election['ballotOptions'])):
            candidate = f"{Election['ballotOptions'][i]['name']}"
            party = party_to_p(Election['ballotOptions'][i]['politicalParty'])
            total_votes     = f"{Election['ballotOptions'][i]['voteCount']}"
            list_of_dicts.append({'date': date,
                                  'county': countyID,
                                  'office': office,
                                  'votefor': vote_for,
                                  'candidate': candidate,
                                  'party': party,
                                  'votes': total_votes
            })
                                  
    df = pd.DataFrame(list_of_dicts)                                 
    df.to_csv(sys.stdout, index=False, sep='\t')

if __name__ == "__main__":
    parser = argparse.ArgumentParser(description="A sample script demonstrating argparse.")
    parser.add_argument('county', help='County ID number')

    args = parser.parse_args()

    json_text = sys.stdin.read()
    phase3(json_text, args.county)
