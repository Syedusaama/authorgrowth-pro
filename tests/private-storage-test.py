"""Check canonical public/private roots; no HTTP server exposure test implied."""
import os
import subprocess
from pathlib import Path
php = os.environ.get('PHP_BIN', 'php')
fixture = str(Path(__file__).with_name('private-storage-fixture.php'))
for mode in ['private', 'public', 'wordpress', 'missing', 'symlink']:
    result = subprocess.run([php, '-n', fixture, mode], check=True, capture_output=True)
    assert result.stdout == (b'ALLOWED' if mode == 'private' else b'DENIED'), (mode, result.stdout)
    assert not result.stderr, result.stderr
print('PASS: 5 private storage boundary scenarios')
