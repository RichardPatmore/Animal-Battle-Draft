const [major, minor] = process.versions.node.split('.').map(Number);
const ok = major > 20 || (major === 20 && minor >= 19);

if (!ok) {
	console.error('');
	console.error('EFL-Stat-Roulette needs Node.js 20.19 or newer.');
	console.error(`You are running ${process.versions.node} (${process.execPath}).`);
	console.error('');
	console.error('Install Node 20 LTS (https://nodejs.org) or use fnm/nvm, then run npm run build again.');
	console.error('Herd users: use a terminal where `node -v` shows v20+, not an old global Node 14 install.');
	console.error('');
	process.exit(1);
}
