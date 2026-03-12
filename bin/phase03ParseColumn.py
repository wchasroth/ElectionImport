#!/usr/bin/env python3.9

import argparse
import copy
from enum import Enum, auto
from numpy import nan
import pandas as pd
import re
import sys
import xmltodict

# ToDo:
# Report Write In candidates' party in Party row
# Add code to verify_office?

# An empty template
empty_row = { 'table': nan,
              'row':   nan,
              'col3':  nan,
              'col4':  nan,
              'col5':  nan,
              'col6':  nan,
              'col7':  nan,
              'col8':  nan,
              'col9':  nan,
              'col10': nan,
              'col11': nan, }

ignorable_races = [ 
    'statement of votes cast', 'sovc', 'straight party', 'election summary',
    'president', 'president/vice', 'president / vice', 'president and vice',
    'us senator', 'united states', 
    'representative in', 'rep in congress', 'us rep', 'congress',
    'governor', 'secretary of state', 'attorney general',
    'state senator', 
    'state representative', 'rep in state legislature', 'rep in state legis', 
    'state rep', 'state district representative', 'representative',
    'state legislature',
    'member of the state board', 'state board of education', 'state board of ed', 
    'state bd', 'board of education',
    'regent', 'trustee of michigan state', 'trustees michigan state', 
    'michigan state university', 'trustee of mich state', 'trustee msu',    
    'governor of wayne state', 'governors wayne state', 'msu trustee', 'trustees msu',
    'proposal', 'proposition', 'prop', 'amendment', 
    'millage', 'question', 'transportation',
    'judge', 'justice', 'court', 
    'ordinance', 'ordianance', 'advisory', 'suppression', 'authority', 'city charter', 'sinking fund',
    'hudson memorial building', 'fire and rescue', 'annexation', 'tax rate', 'road and bridges', 'resolution',
    'increase', 'fire protection', 'city parcel', 'override', 'fire department',
    'disincorporation',
    'smith-kimball',  # community center, not doing these now.
     # TODO: look into intermediate school district stuff like this.  Ignore for now
    'midland esa', 'esa isd' 
]

ignore_columns = [ 
    'Registered Voters'
    ]

party_abbrevs = { 'dem':   'Democratic',
                  'democ': 'Democratic',
                  'lib':   'Libertarian',
                  'liber': 'Libertarian',
                  'nlp':   'Natural Law Party',
                  'natur': 'Natural Law Party',
                  'nonpa': 'Non Partisan',
                  'non':   'Non Partisan',
                  'npa':   'Non Partisan',
                  'nopar': 'Non Partisan',
                  'non-p': 'Non Partisan',
                  'n':     'Non Partisan',
                  'rep':   'Republican',
                  'repub': 'Republican',
                  'ustax': 'US Taxpayers Party',
                  'ust':   'US Taxpayers Party',
                  'worki': 'Working Class Party',
                  'wcp':   'Working Class Party',
                  'write': 'Write-In'
}


def listify(obj):
    # If obj is not a list, create a one-item list for that object.
    if isinstance(obj, list):
        return obj
    else:
        return [obj]

def build_initial_dfs(data_dict):
    # 'Cell' might be a dict or a list of dicts
    w = 1
    # Store each table in a DF, append to a list of DFs. 
    #   Merge multi-page elections later.
    all_page_dfs = []
    for ws in data_dict['Workbook']['Worksheet']:
#       print(ws['Table']['Row'][0]['Cell'][5]['ssData']['Font']['@html:Face'], end=' : ')
#       print(ws['Table']['Row'][0]['Cell'][5]['ssData']['Font']['B'])
        rows = []
        for row in range(len(ws['Table']['Row'])):
            cells = []  # List of contents of cells in this row
            try:
                # Convert the dict in a cell into a list to iterate through the items
                ws['Table']['Row'][row]['Cell'] = listify(ws['Table']['Row'][row]['Cell'])
            except:
                continue
            # Drill down to the level that has the text that we want
            for cellnum in range(len(ws['Table']['Row'][row]['Cell'])):
                font_items = []
                for cellitem in ws['Table']['Row'][row]['Cell'][cellnum]:
                    if cellitem == 'ssData':
                        cell = ws['Table']['Row'][row]['Cell'][cellnum]
                        for k in cell['ssData'].keys():
                            cell_contents = nan
                            # There might be multiple Font[#text] items in one cell
                            if k == 'Font':
        # No work. Why?     print('CI ssData4: ', cell['ssData']['Font'])
                                cell['ssData'][k] = listify(cell['ssData'][k])
                                for j in range(len(cell['ssData'][k])):
#                                   print(cell['ssData']['Font'][0]['@html:Face'], font_item)
                                    if '#text' in cell['ssData'][k][j].keys() :
                                        font_item = cell['ssData'][k][j]['#text']
                                        font_items.append(font_item)
#                                       print('#text:', cell['ssData']['Font'][0]['@html:Face'], font_item)
                                    if 'B' in cell['ssData'][k][j].keys():
                                        cell_contents = cell['ssData'][k][j]['B']
                                        cells.append(cell_contents)
                                        if len(rows) == 0:
                                            cells.append('DELETE') #Marker consumed in merge
                                            cells.append('BEGIN_OFFICE')
#                                       print('B:', cell['ssData']['Font'][0]['@html:Face'], cell_contents)
                            if k == '@ss:Type':
                                if cell['ssData'][k] == 'Number':
                                    cell_contents = cell['ssData']['#text']
                                    cells.append(cell_contents)
                if len(font_items)>0:
                    cells.append(" ".join(font_items))
    
            row2dict = empty_row
            row2dict = { 'table': w,
                         'row': row, }
            if len(cells) < 5: cells.append('PADDING')
            for i in range(len(cells)):
                row2dict[f'col{i}'] = cells[i]
            rows.append(row2dict)
    
        # At the end of a table:
        new_df = pd.DataFrame(rows)
        new_df.rename(columns={'col0': 'title_label'}, inplace=True)
        all_page_dfs.append(new_df)
        w += 1
    return(all_page_dfs)    
    
# Remove all None's from the end of a list. The original list is modified.
def remove_end_nones(orig):
    for item in orig[:]:
        if item is None:
            orig.pop()
    return(orig)

def merge_all_table_dfs(df_list):
    '''
    Use a list of all the DFs for an election report, merge all of the ones 
    that report data for one office. Return a new list with all of the DFs
    that have been merged and DFs that did not need merging.

    State machine:
     NO_OFFICE: Not processing an office. NextDF is a New Office, end or error.
        ROW1 : Have a "first" DF, building Row1, looking for Row2
        ROW2 : Building Row2, looking for NextDF that begins NextOffice
     
    Transitions: 
        To NO_OFFICE: 
            Original state. Also, currently in ROW1 or ROW2, and found 
            "new office" marker in NextDF, but haven't begun processing it 
            yet as currentDF.
        To ROW1: In NO_OFFICE, found "new office" marker.
        To ROW2: In ROW1, found Row2 marker
    
    Algo:
      For each DF:
        If in NO_OFFICE, and DF does not have NewOffice marker, report error.
        If in NO_OFFICE and DF has NewOffice marker, move to ROW1. Store info.
        If in ROW1, and DF does not have NewOffice or Row2 marker, add DF to Row1.
          If DF has NewOffice marker, complete processing for previous office:
            verify table structure for preceding office;
            build merged DF and add it to Master DF List
            Then store info about this new office
          If DF has Row2 marker, add new row.
        If in ROW2, and DF does not have NewOffice marker, add DF to Row2.
          If DF has NewOffice marker, complete processing for previous office:
            verify table structure for preceding office;
            build merged DF and add it to Master DF List
            Then store info about this new office

    '''
    class TableState(Enum):
        NO_OFFICE = auto()
        ROW1 = auto()
        ROW2 = auto()
    
    def report(message):
        print(f'\n!!!!!!!!  {message}  !!!!!!!')

    def is_newoffice(df):
        first_row = df.loc[0]
        for value in first_row:
            if isinstance(value, str) and 'BEGIN_OFFICE' in value:
                return True
        return False

    def is_newrow(df):
        if df.iloc[0,2] == 'Precinct' and df.iloc[0,3] == 'Precinct':
            return True
        else:
            return False
        
    def is_newcolumn(df):
        if df.iloc[0,2] == 'Precinct' and df.iloc[0,3] != 'Precinct':
            return True
        else:
            return False

    def verify_office(row1, row2):
        pass
        
    def build_office(row1, row2):
        try:
            verify_office(row1, row2)
        except:
            report('Verifying office XYZ failed.')
        return(merge_table_dfs(row1, row2))

    current_state = TableState.NO_OFFICE
    office_list = []
    Row1 = []
    Row2 = []
    Fail = False
    
    for df in df_list:
        if len(df) == 0:  # Empty pages happen
            continue
        if current_state == TableState.NO_OFFICE:
            if is_newoffice(df) == True:
                Row1 = []
                Row2 = []
                current_state = TableState.ROW1
                Row1 = [df]
            else:
#               report('While merging tables, in state NO_OFFICE, encountered table that is not the beginning of an office.')
#               Fail = True
#               break
                pass
            continue
        if current_state == TableState.ROW1:
            if is_newoffice(df) == True:
                office_list.append(build_office(Row1, Row2))
                Row1 = []
                Row2 = []
                Row1 = [df]
                current_state = TableState.ROW1
            elif is_newrow(df) == True:
                Row2 = [df]
                current_state = TableState.ROW2
            elif is_newcolumn(df) == True:
                Row1.append(df)
            else:
                report('While merging tables, in state ROW1, encountered table that does not belong in row 1 or 2.')
                Fail = True
                break
            continue
        if current_state == TableState.ROW2:
            if is_newoffice(df) == True:
                office_list.append(build_office(Row1, Row2))
                Row1 = []
                Row2 = []
                Row1 = [df]
                current_state = TableState.ROW1
            elif is_newcolumn(df) == True:
                Row2.append(df)
            else:
                report('While merging tables, in state ROW2, encountered table that does not belong in row 1.')
                break

    if Fail == True:
        return        
    if len(Row1) != 0:
        office_list.append(merge_table_dfs(Row1, Row2))
        
    return office_list

def merge_table_dfs(up, down=None, remove_header=False):
    ''' Merge 2, 3, 4 or6 DFs that each represent a portion of a larger table
        The order of the DF args match the order in the PDF.
        Use an UP array of 2 or 3 to merge columns from 2 or 3 tables
        Use an UP array of 2-3 AND a DOWN array of 2-3 to:
            Merge columns of UP, then columns of DOWN, and 
            finally add the rows of DOWN to the end of UP.
        remove_header: set to True to remove the top row of the DOWN DFs
    '''
    # Preprocess args  Remove Nones
    for l in [up, down]:
        l = remove_end_nones(l)
        
    # Add a new first row into second DF. This is the office title.
    if len(up) > 1:
        empty_row = [nan] * up[1].shape[1]
        up[1].loc[-1] = empty_row    # add a row
        up[1].index = up[1].index + 1   # shift index
        up[1].sort_index(inplace=True)
    if len(up) > 2:
        up[2].loc[-1] = up[0].iloc[0]    # add a row
        up[2].index = up[2].index + 1   # shift index
        up[2].sort_index(inplace=True) 

    # Merge columns in the upper tables, and then in lower if they exist.
    df_new = {}
    for df in [up, down]:
        if not df:
            break

        # Drop duplicate columns before merging
        df[0].drop(['col1'], axis=1, inplace=True)
        if len(up) > 1:
            df[1].drop(['table', 'row', 'title_label'], axis=1, inplace=True)
        # Append merged df to end of list of dfs
        if len(up) == 1:
            new = df[0]
        if len(up) > 1:
            new = pd.merge(df[0], df[1], left_index=True, right_index=True)
        if len(up) > 2:
            df[2].drop(['table', 'row', 'title_label'], axis=1, inplace=True)
            new = pd.merge(new, df[2], left_index=True, right_index=True)

        if len(up) > 1:
            new.rename(columns={'title_label_x': 'title_label'}, inplace=True)

        if df is up:
            df_new['up'] = new
        else:
            df_new['down'] = new

    if not down:
        return df_new['up']

    # Concatenate lower rows to upper rows
    df_all = df_new['up']
    if down is not None:
        df_all = pd.concat([df_new['up'], df_new['down']])

    df_all.reset_index(inplace=True, drop=True)
    df_all.drop(labels=[len(up[0])], inplace=True)
    df_all.reset_index(inplace=True, drop=True)

    return df_all

def replace_with_merged(full_list, orig_indexes, merged_list):
    ''' Given a position in a list, replace a consecutive set of 
        items starting at that position with a merged table. '''

    # Walk through full_list, looking for tables in orig_indexes.
    # Track progress with 2 markers:
    # 1. Current page(table) being processed
    # 2. Current ???
    # To the new, final list of DFs, copy either the original 1-page table,
    # or the merged DF
    new_list = []
    i=0
    while i < len(full_list):
        # Search the merged DFs for one that includes the original table, 
        # using the first table#
        match = None
        for j in range(len(merged_list)):
            if merged_list[j]['merged'].iloc[0]['table'] == i+1:
                last_of_merged = merged_list[j]['original'][-1]-1
                match = j
                break
        if match == None:
            new_list.append(full_list[i])
            i += 1
        else:
            new_list.append(merged_list[match]['merged'])
            # Advance the marker in the full_list
            i = last_of_merged+1

    return new_list

def remove_ignorable_races(df_list):
    keep_list = []
    for df in df_list:
        match = False
        for pattern in ignorable_races:
            if  re.search(pattern, df.iloc[0]['title_label'], re.IGNORECASE):
                match = True
        if match == False:
            keep_list.append(df)
    return keep_list

def restructure_dfs(df_list):
    new_list = []
    for df in df_list:
        # Remove duplicated column of labels that appear in some tables
        for c in [3,4]:
            if df.iloc[1, c] == 'Precinct':
                df.drop(df.columns[c], axis=1, inplace=True)
            
        # Remove unwanted columns
        for c in ignore_columns:
            df.drop(columns=df.columns[(df == c).any()], errors='ignore', inplace=True)
 
        # Move "VoteFor" data from Office Label to the next cell
        named_office = df.iloc[0,2]
        office_pat = r'[\w\s\d\./-]+'
        office = re.search(office_pat, named_office).group().rstrip()
        vf_pat = r'Vote for\s+(\d+)'
        try:
            votefor = re.search(vf_pat, df.iloc[0,2]).group(1)
        except AttributeError:
            new_list.append(df)
            continue

        df.iloc[0,4] = int(votefor) # Row 0, column 3
        df.iloc[0,2] = office

        # Find party abbreviation in the Candidate name, move the party name.
        # Copy the desired row to a list for searching        
        cand_row = df.iloc[1].values.flatten().tolist()
        new_row = [nan] * df.shape[1]
        new_row[2] = 'Party'
        for c in range(3,len(new_row)):    # For each position in the row...
            party = nan
            for abbr in party_abbrevs:
                party_substring = f'{abbr}'
                if f'({party_substring.lower()})' in cand_row[c].lower():
                    party = party_abbrevs[abbr]
                    break
            if party is not nan:
                new_row[c] = party
                # Now remove "(<party>)" from the cell
                df.iloc[1,c] = cand_row[c].replace(f'({party_substring.upper()})', '')
                # And remove "Qualified Write In" if it's there
            elif 'Qualified Write In'.lower() in cand_row[c].lower():
                new_row[c] = 'Write In'
                df.iloc[1,c] = df.iloc[1,c].replace('Qualified Write In', '')

        df.loc[len(df)] = new_row
        
        df = df.replace('PADDING', nan)
        # This does not remove a column that includes "vote for" and NaNs.
        df.dropna(axis=1, how='all', inplace=True)
        df = df.replace('BEGIN_OFFICE', 'VoteFor')

        new_list.append(df)

    return new_list

def main(args):
    if args.file:
        with open(args.file, 'r') as xml_file:
            xml_content = xml_file.read()
    else:
        xml_content = sys.stdin.read()
    
    # Create data structures that represent all of the parsed XML data
    data_dict = xmltodict.parse(xml_content)

    # Process the data structures into per-page tables, 
    # merge multiple tables from one office, remove state/federal offices,
    # and clean up the per-office tables
    all_page_dfs  = build_initial_dfs(data_dict)
    merged_list   = merge_all_table_dfs(all_page_dfs)
    county_list   = remove_ignorable_races(merged_list)
    structured_df = restructure_dfs(county_list)

    if args.html:
        output_dir = ''
        if args.directory:
            output_dir = f'{args.directory}/'
        html_table_file = f'{output_dir}index.html'
        with open(html_table_file, 'w') as f:
            f.write('<HTML>\n<BODY>')

    output_dir = '.'
    if args.directory:
        output_dir = f'{args.directory}'
    office_list = []
    for df in structured_df:
        if not args.rows:
            df.drop(['table', 'row'], axis=1, inplace=True)
        election_name = df.iloc[0]['title_label'].replace('/', '_')
        if args.tsv:
            df.to_csv(f'{output_dir}/{election_name}.tsv', sep='\t', index=False)

        if args.html:
            html_table = df.to_html()
            with open(html_table_file, 'a') as f:
                f.write(html_table)
                f.write('\n<P>\n')

#       if args.summary or (not args.html and not args.tsv):
        if not args.html and not args.tsv:
            office_details = {}
            office_details['election_date'] = args.date
            office_details['county'] = args.county_number
            office_details['office_title'] = df['title_label'].iloc[0]
            office_details['vote_for'] = df.iloc[0,2]

            # Each row is one candidate
            # For each column that's not:
            #   * Index1="Precinct"
            #   * Index1="Total Votes"
            #   * Index1 contains "Write In"
            for col_name in df.keys():
                # Skip several situations
                if col_name == 'title_label' or df[col_name].iloc[1]=='Total Votes':
                    continue

                # If no candidates listed, the "Vote For" column will have NaNs
                if df[col_name].iloc[1] is nan:
                    continue
                if re.search('Write.In',df[col_name].iloc[1]):
                    continue
                if df[col_name].iloc[-1] is not nan and re.search('Write.In',df[col_name].iloc[-1]):
                    continue
                office_details['candidateName'] = df[col_name].iloc[1]
                office_details['partyLetter'] = df[col_name].iloc[-1]
                try:
                    votes_row = df[df['title_label']=='County - Total'].index[0]
                except IndexError:
                    votes_row = df[df['title_label']=='Total'].index[0]
                office_details['votes'] = df.at[votes_row, col_name]
                office_list.append(copy.deepcopy(office_details))
        office_df = pd.DataFrame(office_list)

    if args.output_file:
        filename_date = re.sub(r'\D', '', args.date)
        out_dir = '.'
        if args.directory is not None:
            out_dir = args.directory
        office_df.to_csv(f"{out_dir}/{args.output_file}_{filename_date}.tsv", sep='\t', index=False)
    else:
        office_df.to_csv(sys.stdout, sep='\t', index=False)

if __name__ == "__main__":
#   pd.options.display.float_format = "{:.6f}".format
    pd.set_option('display.max_columns', 20)
    pd.set_option('display.max_rows', None)
    pd.set_option('display.width', 245)
    pd.set_option('future.no_silent_downcasting', True)

    parser = argparse.ArgumentParser(description="Process a series of XML tables into other formats")
    parser.add_argument('date', type=str, help="Election date")
    parser.add_argument('county_number', type=int, help="Official county number")
    
    parser.add_argument('-d', '--directory', type=str, help="Specify directory for output file(s)")
    parser.add_argument('-f', '--file', type=str, help="Specify XML input file")
    parser.add_argument('-m', '--html', action='store_true', help="Store tables as one HTML file")
    parser.add_argument('-o', '--output_file', type=str, help="Specify TSV output file")
    parser.add_argument('-r', '--rows', action='store_true', help="Include table and row numbers in the output")
#   parser.add_argument('-s', '--summarize', action='store_true', help="Create one record per office")
    parser.add_argument('-t', '--tsv', action='store_true', help="Store per-office tables as TSV files")
#   parser.add_argument('-u', '--summary', action='store_true', help="Write summary results to stdout")
    args = parser.parse_args()

    main(args)
    
