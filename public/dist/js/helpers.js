// ═══════════════════════════════════════════════════════════════
//  HELPERS
// ═══════════════════════════════════════════════════════════════

function ts() {
	return Math.floor(Date.now() / 1000);
}

function fmtTs(unix) {
	return new Date(unix * 1000).toLocaleString('en-NG', {
		year:'numeric', month:'short', day:'numeric', hour:'2-digit', minute:'2-digit'
	});
}

// Normalizes whatever shape a timestamp field arrives in (unix seconds,
// unix milliseconds, ISO string, or "YYYY-MM-DD HH:MM:SS" MySQL datetime
// strings) down to unix seconds, which is what fmtTs() and the
// active/expired QR_VALIDITY_WINDOW math both expect.
function parseTsToUnix(value) {
	if (value === null || value === undefined || value === '') return null;
	if (typeof value === 'number') {
		return value > 1e12 ? Math.floor(value / 1000) : Math.floor(value);
	}
	if (typeof value === 'string') {
		if (/^\d+$/.test(value)) {
			const n = Number(value);
			return n > 1e12 ? Math.floor(n / 1000) : n;
		}
		// MySQL datetimes use a space instead of "T", which some browsers
		// fail to parse — normalize before handing off to Date.parse.
		const iso = (value.includes(' ') && !value.includes('T'))
			? value.replace(' ', 'T')
			: value;
		const parsed = Date.parse(iso);
		return Number.isNaN(parsed) ? null : Math.floor(parsed / 1000);
	}
	return null;
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
	if (!res.ok) {
		console.table('API Error:', {
			status: res.status,
			response: body
		});
		
		throw new Error(body?.error || body?.message || `Request failed (${res.status})`);
	}
	return body;
}


// ═══════════════════════════════════════════════════════════════
//  API RESPONSE SHAPE — these can be adjusted later if our JSON keys changes
// ═══════════════════════════════════════════════════════════════

function extractSessions(summaryResponse) {
	if (Array.isArray(summaryResponse)) return summaryResponse;
	return summaryResponse?.sessions || summaryResponse?.data || [];
}

function normSession(s) {
	const id = s.session_id ?? s.session_Id ?? s.attendance_id ?? s.attendanceID ?? s.id ?? '';
	if (id === '' && s && typeof s === 'object') {
		// None of our known id keys matched — log the raw object so the
		// actual key name can be spotted in devtools and added above.
		console.warn('normSession: no id field matched known keys, raw object was:', s);
	}

	// Current summary.php embeds each session's roster as a JSON-stringified
	// array under `signins` (e.g. "[]"), with the real headcount split out
	// into `total_signins`. Older API responses instead sent `signins` as a
	// plain number (just the count) and, if a roster was embedded at all,
	// put it under `attendees`/`records`. Try the new string-array shape
	// first and fall back to the old ones so both keep working.
	let embeddedAttendees = null;
	if (typeof s.signins === 'string') {
		try {
			const parsed = JSON.parse(s.signins);
			if (Array.isArray(parsed)) embeddedAttendees = parsed;
		} catch (_) {
			// Wasn't JSON — probably the old shape, where `signins` is just a
			// numeric/stringified count rather than a roster. Nothing to parse.
		}
	}

	return {
		id,
		tutor_id: s.tutor_id ?? s.tutorID ?? '',
		// `eraValid` is the current API's name for the session's creation
		// timestamp — same value `created_at`/`createdAt` used to carry, and
		// what the active/expired QR_VALIDITY_WINDOW math is measured from.
		created_at: parseTsToUnix(s.eraValid ?? s.created_at ?? s.createdAt ?? s.timestamp ?? null),
		signins: s.total_signins ?? (typeof s.signins === 'number' ? s.signins : null) ?? s.signin_count ?? s.count
			?? (embeddedAttendees ? embeddedAttendees.length : null)
			?? (Array.isArray(s.attendees) ? s.attendees.length : null),
		attendees: embeddedAttendees ?? s.attendees ?? s.records ?? null,
		// summary.php now resolves active/expired itself and returns it as
		// `time_status` ("active"/"expired") instead of leaving the client to
		// recompute it from created_at + QR_VALIDITY_WINDOW. Still honor a
		// literal is_active if an older response sends one, then fall back to
		// time_status, then to null (unknown) if neither is present.
		is_active: 'is_active' in s
			? (s.is_active === '1' || s.is_active === 1 || s.is_active === true)
			: ('time_status' in s ? s.time_status === 'active' : null)
	};
}

function normAttendee(a) {
	// Our current API stores signins as plain strings.
	if (typeof a === 'string' || typeof a === 'number') {
		return {
			reg_no: Number.parseInt(a, 16).toString(),
			signed_at: null
		};
	}
	
	const regNo = 
		a?.reg_no ??
		a?.reg_number ??
		a?.matric_no ??
		a?.regNo ??
		'';
	
	return {
		reg_no: regNo ? Number.parseInt(String(regNo), 16).toString() : '',
		signed_at: a.signed_at ?? a.signedAt ?? a.timestamp ?? a.created_at ?? null
	};
}

async function sessionExists(sessionId) {
	const res = await api(`/sessions.php?session_id=${encodeURIComponent(sessionId)}`);
	return res?.exists === true;
}

async function fetchSessionAttendance(sessionId) {
	try {
		const raw = await api(`/sessions.php?session_id=${encodeURIComponent(sessionId)}&tutor_id=${LECTURER.tutor_id}`);
		
		// Verify that the API found the requested session.
		if (!raw?.success || !raw?.exists || !raw?.session) {
			return [];
		}
		
		const session = raw.session;
		
		// Our API stores signins as a JSON string.
		let signins = session.signins;
		
		if (typeof signins === 'string') {
			signins = JSON.parse(signins);
		}
		
		// Ensure we have an array before processing it.
		if (!Array.isArray(signins)) {
			return [];
		}
		
		// Normalize each attendee into the format expected by the frontend.
		return signins.map(normAttendee);
	} catch (e) {
		return [];
	}
}
