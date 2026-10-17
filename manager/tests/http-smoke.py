"""Run against an isolated local installation, never a production database.

Set VELORA_SMOKE_URL, VELORA_SMOKE_USER and VELORA_SMOKE_PASSWORD in your
environment. Creates/deletes its own test car and enquiry, and exercises the
login rate limit at the end. Requires Python 3 and a running PHP/MySQL app.
"""
import html
import http.cookiejar
import os
import re
import secrets
import urllib.error
import urllib.parse
import urllib.request

BASE = os.environ.get('VELORA_SMOKE_URL', 'http://127.0.0.1:8000').rstrip('/')
USER = os.environ['VELORA_SMOKE_USER']
PASSWORD = os.environ['VELORA_SMOKE_PASSWORD']
if urllib.parse.urlsplit(BASE).hostname not in ('127.0.0.1', 'localhost'):
    raise SystemExit('This smoke test only accepts a localhost installation.')


def client():
    return urllib.request.build_opener(urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))


def request(opener, path, data=None):
    payload = urllib.parse.urlencode(data).encode() if data is not None else None
    try:
        response = opener.open(BASE + '/' + path, data=payload, timeout=15)
    except urllib.error.HTTPError as error:
        response = error
    body = response.read()
    return response.code, body.decode('utf-8', errors='replace'), response.geturl(), response.headers


def token(body):
    match = re.search(r'name="csrf" value="([a-f0-9]+)"', body)
    assert match, 'CSRF field missing'
    return match.group(1)


admin, visitor = client(), client()
code, body, url, _ = request(visitor, 'admin.php')
assert code == 200 and url.endswith('/login.php'), 'Guest reached admin'
code, login, _, _ = request(admin, 'login.php')
code, dashboard, _, headers = request(admin, 'login.php', {'csrf': token(login), 'username': USER, 'password': PASSWORD})
assert code == 200 and 'Total cars' in dashboard, 'Login failed'
assert "script-src 'none'" in headers.get('Content-Security-Policy', ''), 'CSP missing'
code, _, _, _ = request(admin, 'admin.php?view=edit', {'action': 'save_car'})
assert code == 403, 'CSRF protection missing'

name = 'Smoke ' + secrets.token_hex(4) + ' <script>test</script>'
car = {'name': name, 'category': 'performance', 'model_year': '2024', 'price_usd': '123456.78', 'description': 'Fictional smoke-test vehicle. Delete after testing.', 'image_key': 'gt3rs', 'is_published': '0', 'action': 'save_car'}
_, editor, _, _ = request(admin, 'admin.php?view=edit')
code, listing, _, _ = request(admin, 'admin.php?view=edit', {**car, 'csrf': token(editor)})
assert code == 200 and html.escape(name, quote=True) in listing, 'Draft was not saved or escaped'
card = next(part for part in re.findall(r'<article class="car-card">(.*?)</article>', listing, re.S) if html.escape(name) in part)
car_id = re.search(r'view=edit&id=(\d+)', card).group(1)
_, public, _, _ = request(visitor, 'index.php')
assert html.escape(name) not in public, 'Draft leaked into public collection'
car['is_published'] = '1'
_, editor, _, _ = request(admin, 'admin.php?view=edit&id=' + car_id)
code, _, _, _ = request(admin, 'admin.php?view=edit&id=' + car_id, {**car, 'csrf': token(editor)})
assert code == 200
_, public, _, _ = request(visitor, 'index.php')
assert html.escape(name) in public and name not in public, 'Published car missing or unescaped'
_, filtered, _, _ = request(visitor, 'index.php?category=hybrid')
assert html.escape(name) not in filtered, 'Category filter failed'
code, _, _, headers = request(visitor, 'photo.php?key=gt3rs')
assert code == 200 and headers.get_content_type() == 'image/jpeg', 'Photo failed'
code, _, _, _ = request(visitor, 'photo.php?key=../../config/local.php')
assert code == 404, 'Photo traversal was accepted'

_, detail, _, _ = request(visitor, 'index.php?car=' + car_id)
message = 'Smoke message ' + secrets.token_hex(4) + ' <img src=x onerror=alert(1)>'
enquiry = {'csrf': token(detail), 'car_id': car_id, 'name': 'Fictional Tester', 'email': 'bad-email', 'message': message}
code, _, _, _ = request(visitor, 'index.php', enquiry)
assert code == 422, 'Invalid email accepted'
enquiry['email'] = 'fictional@example.com'
code, receipt, _, _ = request(visitor, 'index.php', enquiry)
assert code == 200 and 'Your demo enquiry has been saved' in receipt, 'Enquiry was not saved'
_, inbox, _, _ = request(admin, 'admin.php?view=enquiries')
assert html.escape(message) in inbox and message not in inbox, 'Stored enquiry missing or unescaped'
entry = next(part for part in re.findall(r'<article class="panel enquiry">(.*?)</article>', inbox, re.S) if html.escape(message) in part)
enquiry_id = re.search(r'name="enquiry_id" value="(\d+)"', entry).group(1)
code, _, _, _ = request(admin, 'admin.php?view=enquiries', {'csrf': token(inbox), 'action': 'set_status', 'enquiry_id': enquiry_id, 'status': 'closed'})
assert code == 200
_, closed, _, _ = request(admin, 'admin.php?view=enquiries&status=closed')
assert html.escape(message) in closed, 'Status update did not persist'

_, confirmation, _, _ = request(admin, 'admin.php?view=delete&id=' + car_id)
code, _, _, _ = request(admin, 'admin.php?view=delete&id=' + car_id, {'csrf': token(confirmation), 'action': 'delete_car', 'confirm': 'yes'})
assert code == 200
_, inbox, _, _ = request(admin, 'admin.php?view=enquiries')
assert html.escape(name) in inbox, 'Enquiry car name did not survive car deletion'
code, _, _, _ = request(visitor, 'index.php?car=' + car_id)
assert code == 404, 'Deleted car is still available'
_, confirmation, _, _ = request(admin, 'admin.php?view=delete-enquiry&id=' + enquiry_id)
code, _, _, _ = request(admin, 'admin.php?view=delete-enquiry&id=' + enquiry_id, {'csrf': token(confirmation), 'action': 'delete_enquiry', 'confirm': 'yes'})
assert code == 200
_, inbox, _, _ = request(admin, 'admin.php?view=enquiries')
assert html.escape(message) not in inbox, 'Enquiry deletion failed'
code, _, _, _ = request(admin, 'logout.php')
assert code == 405, 'GET performed a logout'
code, _, url, _ = request(admin, 'logout.php', {'csrf': token(inbox)})
assert code == 200 and url.endswith('/login.php')
code, _, url, _ = request(admin, 'admin.php')
assert url.endswith('/login.php'), 'Logout did not revoke access'

_, login, _, _ = request(visitor, 'login.php')
code, _, url, _ = request(visitor, 'login.php', {'csrf': token(login), 'username': "' OR 1=1 --", 'password': 'invalid password'})
assert code == 200 and url.endswith('/login.php'), 'Injection bypassed login'
codes = []
for _ in range(5):
    _, login, _, _ = request(visitor, 'login.php')
    code, _, _, _ = request(visitor, 'login.php', {'csrf': token(login), 'username': USER, 'password': 'invalid password'})
    codes.append(code)
assert 429 in codes, 'Login rate limit failed'
print('HTTP smoke passed: login, authorization, CSRF, car CRUD, publishing, photo allowlist, enquiry validation/storage/status/deletion, escaped output, logout, and login rate limit.')
