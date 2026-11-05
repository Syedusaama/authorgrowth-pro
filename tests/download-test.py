"""Exercise streaming and rejection handlers with API doubles, not a real database."""
import os
import subprocess
from pathlib import Path
php = os.environ.get('PHP_BIN', 'php')
fixture = str(Path(__file__).with_name('download-fixture.php'))
for mode in ['valid', 'expired', 'replay', 'race', 'draft', 'missing']:
    result = subprocess.run([php, '-n', fixture, mode], check=True, capture_output=True)
    expected = b'%PDF-1.4 test chapter' if mode == 'valid' else b'DENIED:403'
    assert result.stdout == expected, (mode, result.stdout, result.stderr)
    assert not result.stderr, (mode, result.stderr)
print('PASS: 6 download handler scenarios')
