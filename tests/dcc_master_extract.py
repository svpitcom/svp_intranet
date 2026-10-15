import openpyxl, json, datetime, collections, os, re
path = r'C:\Users\thaw0524\AppData\Local\Temp\svp-dcc-master-20261015.xlsx'
w = openpyxl.load_workbook(path, data_only=True)
records, issues = [], []
for s in w:
    if s.title == 'EUDR LIST':
        continue
    for row in s:
        code = str(row[2].value or '').strip()
        if not re.match(r'^[A-Z]{2,4}-', code):
            continue
        title = str(row[1].value or '').strip()
        rev = row[3].value
        date = row[6].value
        record = dict(sheet=s.title, row=row[0].row, code=code, title=title,
            revision=str(rev).strip() if rev is not None else '',
            effective_date=date.strftime('%Y-%m-%d') if isinstance(date, datetime.datetime) else None,
            source_date=str(date) if date is not None else '',
            source_notes=[str(c.value) for c in row[35:] if c.value is not None])
        records.append(record)
counts = collections.Counter(r['code'].casefold() for r in records)
for r in records:
    if counts[r['code'].casefold()] > 1 or not r['title'] or not r['revision'] or not r['effective_date']:
        issues.append(r)
out = r'C:\Users\thaw0524\AppData\Local\Temp\svp-dcc-master-rows-20261015.json'
with open(out, 'w', encoding='utf-8') as f: json.dump(records, f, ensure_ascii=False, indent=2)
print(json.dumps({'rows':len(records), 'sheets':dict(collections.Counter(r['sheet'] for r in records)), 'duplicate_codes':[c for c,n in counts.items() if n>1], 'issues':issues[:12], 'issue_count':len(issues), 'output':out},ensure_ascii=False,default=str))
