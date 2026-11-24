#!/usr/bin/env python3
"""Generate a WordPress import file from the locally captured public pages."""
import html
from html.parser import HTMLParser
from pathlib import Path
import xml.etree.ElementTree as ET
root=Path(__file__).resolve().parent.parent
pages={'obchodni-podminky':'Obchodní podmínky','ochrana-osobnich-udaju':'Ochrana osobních údajů','doprava-a-platba':'Doprava a platba'}
class Extract(HTMLParser):
 def __init__(self): super().__init__(convert_charrefs=False); self.depth=0; self.parts=[]; self.active=False
 def handle_starttag(self,tag,attrs):
  d=dict(attrs)
  if not self.active and tag=='div' and 'elementor-widget-text-editor' in d.get('class',''):
   self.active=True; self.depth=1; return
  if self.active:
   if tag=='div': self.depth+=1
   if tag in ('p','br','strong','b','em','i','ul','ol','li','a','h2','h3','h4'):
    if tag=='a' and d.get('href','').startswith(('https://','http://','mailto:','tel:','/')): self.parts.append('<a href="'+html.escape(d['href'],quote=True)+'">')
    elif tag!='a': self.parts.append('<'+tag+'>')
 def handle_startendtag(self,tag,attrs): self.handle_starttag(tag,attrs)
 def handle_endtag(self,tag):
  if self.active:
   if tag=='div':
    self.depth-=1
    if self.depth==0:self.active=False;self.parts.append('\n');return
   if tag in ('p','strong','b','em','i','ul','ol','li','a','h2','h3','h4'):self.parts.append('</'+tag+'>')
 def handle_data(self,data):
  if self.active:self.parts.append(html.escape(data))
 def handle_entityref(self,name):
  if self.active:self.parts.append('&'+name+';')
 def handle_charref(self,name):
  if self.active:self.parts.append('&#'+name+';')
ns={'wp':'http://wordpress.org/export/1.2/','content':'http://purl.org/rss/1.0/modules/content/'}
for prefix,uri in ns.items(): ET.register_namespace(prefix,uri)
rss=ET.Element('rss',version='2.0');channel=ET.SubElement(rss,'channel');ET.SubElement(channel,'title').text='Impozant stránky';ET.SubElement(channel,'link').text='https://impozant.cz/';ET.SubElement(channel,'description').text='Veřejné stránky';ET.SubElement(channel,'{'+ns['wp']+'}wxr_version').text='1.2'
for slug,title in pages.items():
 p=Extract();p.feed((root/'preview'/slug/'index.html').read_text()); content=''.join(p.parts).strip()
 if len(content)<100:raise ValueError('Nedostatek obsahu: '+slug)
 item=ET.SubElement(channel,'item');ET.SubElement(item,'title').text=title;ET.SubElement(item,'{'+ns['content']+'}encoded').text=content
 for key,val in [('post_type','page'),('post_name',slug),('status','publish')]: ET.SubElement(item,'{'+ns['wp']+'}'+key).text=val
 for k,v in [('impozant_page_content',content),('_impozant_page_content','field_impozant_page_content')]:
  meta=ET.SubElement(item,'{'+ns['wp']+'}postmeta');ET.SubElement(meta,'{'+ns['wp']+'}meta_key').text=k;ET.SubElement(meta,'{'+ns['wp']+'}meta_value').text=v
 print(title, len(content),'znaků')
out=Path('/Users/michalmikl/Documents/Codex/2026-11-24/mam-x20/work/impozant-pages.xml');ET.ElementTree(rss).write(out,encoding='utf-8',xml_declaration=True)
