const { spawn } = require('child_process');
const path = require('path');

const port = process.env.PORT || 10000;

console.log('Starting PHP server...');
const php = spawn('php', ['-S', `0.0.0.0:${port}`], {
  cwd: path.join(__dirname),
  stdio: 'inherit'
});

php.on('error', (err) => {
  console.error('Failed to start PHP:', err);
  process.exit(1);
});

php.on('exit', (code) => {
  console.log(`PHP process exited with code ${code}`);
  process.exit(code);
});
