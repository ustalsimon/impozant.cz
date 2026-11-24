#!/usr/bin/env python3
"""Prepare WooCommerce import data from the publicly available store catalog."""
import concurrent.futures
import csv
import json
import subprocess
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
products = json.loads((ROOT / 'content/public-products.json').read_text(encoding='utf-8'))
ids = sorted({v['id'] for product in products for v in product.get('variations', [])})
def fetch(identifier):
    result = subprocess.run(['curl', '-fsSL', '--max-time', '30', f'https://impozant.cz/wp-json/wc/store/v1/products/{identifier}'], check=True, capture_output=True)
    value = json.loads(result.stdout)
    if value.get('id') != identifier or value.get('type') != 'variation':
        raise ValueError(f'Invalid variation response: {identifier}')
    return value
with concurrent.futures.ThreadPoolExecutor(max_workers=4) as pool:
    variants = {item['id']: item for item in pool.map(fetch, ids)}
(ROOT / 'content/public-variations.json').write_text(json.dumps(list(variants.values()), ensure_ascii=False, indent=2), encoding='utf-8')
headers = ['Type','SKU','Name','Published','Visibility in catalog','Short description','Description','In stock?','Regular price','Categories','Images','Parent','Attribute 1 name','Attribute 1 value(s)','Attribute 1 visible','Attribute 1 global']
rows=[]
for p in products:
    sku = 'impozant-' + str(p['id'])
    attributes = p.get('attributes', [])
    attr = attributes[0] if attributes else {}
    row = {'Type':p['type'],'SKU':sku,'Name':p['name'],'Published':'1','Visibility in catalog':'visible','Short description':p['short_description'],'Description':p['description'],'In stock?':int(p['is_in_stock']),'Regular price':p['prices']['regular_price'] if p['type']=='simple' else '', 'Categories':', '.join(x['name'].replace(',','\\,') for x in p['categories']), 'Images':', '.join(x['src'] for x in p['images']), 'Attribute 1 name':attr.get('name',''),'Attribute 1 value(s)':', '.join(t['name'].replace(',','\\,') for t in attr.get('terms',[])), 'Attribute 1 visible':'1' if attr else '', 'Attribute 1 global':'0' if attr else ''}
    rows.append(row)
    for v in p.get('variations',[]):
        d=variants[v['id']]
        attrs=v.get('attributes',[])
        term_names = {t['slug']: t['name'] for t in attr.get('terms', [])}
        value = attrs[0]['value'] if attrs else ''
        value = term_names.get(value, value)
        rows.append({'Type':'variation','SKU':'impozant-'+str(v['id']),'Name':d['name'],'Published':'1','Visibility in catalog':'visible','In stock?':int(d['is_in_stock']),'Regular price':d['prices']['regular_price'],'Parent':sku,'Attribute 1 name':attr.get('name',''),'Attribute 1 value(s)':value, 'Attribute 1 global':'0'})
with (ROOT / 'content/woocommerce-import.csv').open('w',encoding='utf-8-sig',newline='') as f:
    writer=csv.DictWriter(f,fieldnames=headers);writer.writeheader();writer.writerows(rows)
print(f'Prepared {len(products)} products and {len(ids)} variations.')
