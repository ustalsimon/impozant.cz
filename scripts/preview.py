from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
import os, json
ROOT=Path(__file__).resolve().parent.parent / 'preview'
os.chdir(ROOT)
class Handler(SimpleHTTPRequestHandler):
 def do_GET(self):
  if self.path=='/__preview_version':
   stamp=max((p.stat().st_mtime_ns for p in ROOT.rglob('*.html')),default=0)
   body=str(stamp).encode(); self.send_response(200); self.send_header('Content-Type','text/plain'); self.send_header('Cache-Control','no-store'); self.end_headers(); self.wfile.write(body); return
  super().do_GET()
 def end_headers(self):
  self.send_header('Cache-Control','no-store')
  super().end_headers()
 def copyfile(self,source,out):
  if self.path.split('?')[0].endswith('/') or self.path.split('?')[0].endswith('.html'):
   # HTML is served as saved: Content-Length stays correct.
   pass
  super().copyfile(source,out)
ThreadingHTTPServer(('127.0.0.1',8765),Handler).serve_forever()
