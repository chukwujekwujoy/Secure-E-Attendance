// ═══════════════════════════════════════════════════════════════
// student-helpers.js
// Requires net-log.js to be loaded first.
// ═══════════════════════════════════════════════════════════════

function showError(message = "") {
	errorBox.innerHTML = message;
	errorBox.style.display = message ? "block" : "none";
}
function showSuccess(message = "") {
	successBox.innerHTML = message;
	successBox.style.display = message ? "block" : "none";
}

// Every one of our /api endpoints (geo_verify.php, student_verify.php,
// signin_options.php, signin_verify.php) returns a "message" field on
// both success and error. This is the single place that puts it in front
// of the student: it is typed out as a new line in #network_messages.
// Note: this APPENDS a line each call (the old version replaced the text).
// Call NetLog.clear() at the start of a submit attempt for a fresh log.
function setStMessage(message = "") {
	NetLog.log(message);
}

function bufferDecode(value) {
	if (!value) return null;

	value = value.replace(/-/g, '+').replace(/_/g, '/');

	const pad = value.length % 4;
	if (pad) {
		value += '='.repeat(4 - pad);
	}

	const str = atob(value);
	const bytes = new Uint8Array(str.length);

	for (let i = 0; i < str.length; i++) {
		bytes[i] = str.charCodeAt(i);
	}

	return bytes.buffer;
}

function bufferEncode(value) {
	return btoa(String.fromCharCode(...new Uint8Array(value)))
		.replace(/\+/g, '-')
		.replace(/\//g, '_')
		.replace(/=/g, '');
}

function setSubmitting(isSubmitting, idleLabel = "SUBMIT", busyLabel = "VERIFYING…") {
	submitForm.disabled = isSubmitting;
	submitForm.textContent = isSubmitting ? busyLabel : idleLabel;
}

function describeGeoError(error) {
	if (error.code === error.PERMISSION_DENIED) {
		return "Location access was denied. Please allow precise location access for this site and try again.";
	}

	if (error.code === error.POSITION_UNAVAILABLE) {
		return "Your location couldn't be determined. Please check your GPS/location settings and try again.";
	}

	if (error.code === error.TIMEOUT) {
		return "Getting your location timed out. Please try again.";
	}

	return error.message || "Couldn't get your location. Please try again.";
}

async function api(url, options = {}) {
	const res = await fetch(`${API_BASE}${url}`, {
		headers: { 'Content-Type': 'application/json' },
		credentials: 'same-origin',   // send the PHP session cookie
		...options
	});

	let body = null;

	try {
		body = await res.json();
	} catch (_) {
		/* non-JSON response */
	}

	// Surface every API message — success or error — to the student.
	const shown = Boolean(body && typeof body.message === 'string' && body.message !== '');
	if (shown) {
		setStMessage(body.message);
	}

	if (!res.ok) {
		console.error('API Error:', {
			status: res.status,
			response: body
		});

		const err = new Error(body?.error || body?.message || `Request failed (${res.status})`);
		// Tells callers' catch blocks the message is already on screen,
		// so they don't log it a second time.
		err.shown = shown;
		throw err;
	}

	return body;
}


// ═══════════════════════════════════════════════════════════════
//  API RESPONSE SHAPE — these can be adjusted later if our JSON keys change
// ═══════════════════════════════════════════════════════════════

// Returns the full response body. Callers check `.success` themselves.
async function studentExists(matricNo) {
	return api(
		`/student_verify.php` +
		`?studentId=${encodeURIComponent(matricNo)}` +
		`&deviceId=${encodeURIComponent(window.deviceId)}`
	);
}

async function fetchGeoFence(sessionId) {
	const location = await getPreciseLocation();

	if (!location) {
		return null;
	}

	const {
		lat,
		lng,
		accuracy_m
	} = location;

	return api(
		`/geo_verify.php` +
		`?attendID=${encodeURIComponent(sessionId)}` +
		`&lat=${encodeURIComponent(lat)}` +
		`&lng=${encodeURIComponent(lng)}` +
		`&radius=${encodeURIComponent(accuracy_m)}`
	);
}

// signin_options.php / signin_verify.php go through api() too, so their
// messages land in the log the same way and every endpoint's request
// shape lives in one place.
async function startPasskeySignin(payload) {
	const res = await api('/signin_options.php', {
		method: 'POST',
		body: JSON.stringify(payload),
	});
	return res?.options ?? null;
}

async function verifyPasskeySignin(payload) {
	return api('/signin_verify.php', {
		method: 'POST',
		body: JSON.stringify(payload),
	});
}
