// ═══════════════════════════════════════════════════════════════
// net-log.js
// Typewriter-style message log for #network_messages.
// Load this BEFORE student-helpers.js and attendance.js.
// ═══════════════════════════════════════════════════════════════

const NetLog = (() => {
	const box = document.querySelector('#network_messages');

	// If the element isn't on this page, become a harmless no-op
	if (!box) return { log() {}, success() {}, error() {}, clear() {} };

	const cursor = document.createElement('span');
	cursor.className = 'cursor';
	box.append(cursor);

	const queue = [];
	let busy = false;
	const sleep = ms => new Promise(r => setTimeout(r, ms));

	// Phosphor icon classes per message type (info falls back to a plain ">")
	const ICONS = {
		success: 'ph-bold ph-checks',
		error:   'ph-bold ph-x',
	};

	function makePrefix(type) {
		const prefix = document.createElement('span');
		prefix.className = 'prefix';

		if (ICONS[type]) {
			const icon = document.createElement('i');
			icon.className = ICONS[type];
			prefix.append(icon);
		} else {
			prefix.textContent = '>';
		}
		return prefix;
	}

	/**
	 * Queue a message. Each message becomes its own line and is typed
	 * out after the previous one finishes.
	 * @param {string} text
	 * @param {'info'|'success'|'error'} type  controls the coloured line prefix
	 * @param {number} speed  ms per character (capped so one line never takes > ~3s)
	 */
	function log(text, type = 'info', speed = 20) {
		if (!text) return;
		queue.push({ text: String(text), type, speed });
		if (!busy) run();
	}

	async function run() {
		busy = true;

		while (queue.length) {
			const { text, type, speed } = queue.shift();

			const line = document.createElement('div');
			line.className = `line ${type}`;

			const content = document.createElement('span');
			line.append(makePrefix(type), content, cursor);   // moves the single cursor onto this line
			box.append(line);

			const delay = Math.min(speed, 3000 / Math.max(text.length, 1));

			// for...of iterates by code point, so emoji/unicode stay intact.
			// textContent (never innerHTML) keeps server responses from injecting HTML.
			for (const ch of text) {
				content.textContent += ch;
				box.scrollTop = box.scrollHeight;
				await sleep(delay);
			}
		}

		busy = false;
	}

	/** Drop pending messages and empty the log (call at the start of a new attempt). */
	function clear() {
		queue.length = 0;
		box.replaceChildren(cursor);
	}

	return {
		log,
		success: (text, speed) => log(text, 'success', speed),
		error:   (text, speed) => log(text, 'error', speed),
		clear,
	};
})();