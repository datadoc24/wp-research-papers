import json
import sys
import re
import os
import unicodecsv as csv
from xml.etree import ElementTree as ET

if len(sys.argv) != 3:	
	sys.exit("Wrong arguments")

XMLFILE = sys.argv[1]
OUTPUTFILE = sys.argv[2]

print "Processing XML Endnote file " + XMLFILE

#read in the file contents
with open(XMLFILE, 'r') as xml_file:
    xml = xml_file.read()


tags_to_strip = ['style']

for t in tags_to_strip:	
#strip out unwanted tags	
	xml = re.sub('<'+t+'.*?>','', xml)
	xml = re.sub('</'+t+'>','', xml)

	
#xml = xml.encode('utf-8', errors='ignore')
records_out = []
records_processed = 0
tree = ET.ElementTree(ET.fromstring(xml))
root = tree.getroot()

print root.tag

records = root.find('records')

for record in records:
	record_out = {}
	print '-----------'
	
	contributors = record.find('contributors')
	authors = contributors.find('authors')
	author_string=''
	
	if authors is None:
		authors = contributors.find('secondary-authors')
		
	if authors is not None:
		
		for author in authors:
			author_no_commas = author.text.replace(",","")
			author_string = author_string + ',' + author_no_commas
			
	#remove leading comma from author string
	if len(author_string) > 0:
		author_string = author_string[1:]
	
	#print "AUTHORS: " + author_string
	record_out['Authors'] = author_string
		
	titles = record.find('titles')
	title_string = ''
	journal_string = ''
	
	if titles is not None:
		title = titles.find('title')
		journal = titles.find('secondary-title')
		
		if title is not None:
			title_string = '"' + title.text + '"'
			record_out['Title'] = title.text
		
		if journal is not None:
			journal_string = '"' + journal.text + '"'
			record_out['Journal'] = journal.text
	
	#print "TITLE: "+ title_string
	#print "JOURNAL: " + journal_string
			
	dates = record.find('dates')
	year_string = ''
	
	if dates is not None:
		year = dates.find('year')
		
		if year is not None:
			year_string = year.text
			record_out['Year'] = year.text
	
	#print "YEAR: " + year_string
			
			
	abstract_string = ''
	abstract = record.find('abstract')
	
	if abstract is not None:
		abstract_string = '"' + abstract.text + '"'
		record_out['Abstract'] = abstract.text
		
	
	#print "ABSTRACT: " + abstract_string
	
	url_string = ''
	urls = record.find('urls')
	
	if urls is not None:
		related_urls = urls.find('related-urls')
		
		if related_urls is not None:
			url = related_urls.find('url')
			
			if url is not None:
				url_string = url.text
				record_out['URL'] = url.text
				
				
	#print "URL: " + url_string
	
	records_out.append(record_out)
	records_processed += 1
	print "Processed " + str(records_processed) + " records"
	
keys = ['Authors','Title','Journal','Year','Abstract','URL']

with open(OUTPUTFILE, 'wb') as output_file:
    dict_writer = csv.DictWriter(output_file, keys)
    dict_writer.writeheader()
    dict_writer.writerows(records_out)