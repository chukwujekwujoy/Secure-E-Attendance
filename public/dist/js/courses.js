// ═══════════════════════════════════════════════════════════════
//  UI: DASHBOARD
// ═══════════════════════════════════════════════════════════════

const COURSES_DATA = {
	"year_one": {
		"harmattan_semester": [
			{
				"code": "MTH 101",
				"title": "Elementary Mathematics I"
			},
			{
				"code": "PHY 101",
				"title": "General Physics 1"
			},
			{
				"code": "CHM 101",
				"title": "General Chemistry I"
			},
			{
				"code": "BIO 101",
				"title": "Biology for Physical Sciences"
			},
			{
				"code": "ENG 101",
				"title": "Workshop Practice I"
			},
			{
				"code": "ENG 103",
				"title": "Engineering Drawing I"
			},
			{
				"code": "GST 101",
				"title": "Use of English I"
			},
			{
				"code": "GST 103",
				"title": "Humanities"
			},
			{
				"code": "IGB 101/FRN 101",
				"title": "Use of Igbo I/French I"
			}
		],
		"rain_semester": [
			{
				"code": "MTH 102",
				"title": "Elementary Mathematics II"
			},
			{
				"code": "PHY 102",
				"title": "General Physics II"
			},
			{
				"code": "CHM 102",
				"title": "General Chemistry II"
			},
			{
				"code": "ENG 102",
				"title": "Workshop Practice II"
			},
			{
				"code": "ENG 104",
				"title": "Engineering Drawing II"
			},
			{
				"code": "GST 102",
				"title": "Use of English II"
			},
			{
				"code": "GST 110",
				"title": "Science, Technology and Society"
			},
			{
				"code": "GST 108",
				"title": "Social Science I"
			},
			{
				"code": "IGB 102/FRN 102",
				"title": "Use of Igbo II/French II"
			}
		]
	},
	"year_two": {
		"harmattan_semester": [
			{
				"code": "CSC 201",
				"title": "Computer and Applications I"
			},
			{
				"code": "CSC 203",
				"title": "Fundamentals of Cyber Security I"
			},
			{
				"code": "MTH 201",
				"title": "Mathematical Methods I"
			},
			{
				"code": "MTH 203",
				"title": "Elementary Differential Equations I"
			},
			{
				"code": "STA 211",
				"title": "Introduction to Statistics and Probability"
			},
			{
				"code": "PHY 201",
				"title": "Applied Electricity I"
			},
			{
				"code": "ENG 201",
				"title": "Workshop Practice III"
			},
			{
				"code": "GST 201",
				"title": "Social Science II"
			}
		],
		"rain_semester": [
			{
				"code": "CSC 202",
				"title": "Computer and Applications II"
			},
			{
				"code": "CIT 204",
				"title": "Computer Architecture and Organization I"
			},
			{
				"code": "CIT 202",
				"title": "Computer Programming I"
			},
			{
				"code": "MTH 202",
				"title": "Mathematical Methods II"
			},
			{
				"code": "CSC 204",
				"title": "Fundamentals of Cyber Security II"
			},
			{
				"code": "CSC 208",
				"title": "Introduction to Database Design and Applications"
			},
			{
				"code": "MTH 222",
				"title": "Numerical Methods"
			},
			{
				"code": "STA 212",
				"title": "Probability and Random Variables"
			},
			{
				"code": "SIW 200",
				"title": "SIWES"
			}
		]
	},
	"year_three": {
		"harmattan_semester": [
			{
				"code": "CIT 301",
				"title": "Operating Systems I"
			},
			{
				"code": "CIT 303",
				"title": "System Analysis and Design"
			},
			{
				"code": "CSC 303",
				"title": "Computer Systems Laboratory"
			},
			{
				"code": "CSC 305",
				"title": "Data Structures and Algorithms"
			},
			{
				"code": "CSC 307",
				"title": "Structured Programming"
			},
			{
				"code": "CSC 309",
				"title": "Discrete Structures"
			},
			{
				"code": "PHY 303",
				"title": "Applied Electronics"
			},
			{
				"code": "CIT 305",
				"title": "Introduction to Software Engineering"
			},
			{
				"code": "ENS 301",
				"title": "Introduction to Entrepreneurship & Innovation I"
			},
			{
				"code": "MTH 303",
				"title": "Real Analysis I"
			},
			{
				"code": "STA 311",
				"title": "Introduction to Statistical Inference"
			},
			{
				"code": "CSC 311",
				"title": "Statistical Computing"
			}
		],
		"rain_semester": [
			{
				"code": "CSC 310",
				"title": "Operating System II"
			},
			{
				"code": "CSC 302",
				"title": "Computer Architecture and Organization II"
			},
			{
				"code": "CIT 304",
				"title": "Database Management Systems Design I"
			},
			{
				"code": "CIT 302",
				"title": "Computer Programming II"
			},
			{
				"code": "CIT 306",
				"title": "Web Design and Programming I"
			},
			{
				"code": "CSC 304",
				"title": "Compiler Construction I"
			},
			{
				"code": "CSC 306",
				"title": "Assembly and Machine Language Programming"
			},
			{
				"code": "ENS 302",
				"title": "Introduction to Entrepreneurship & Innovation II"
			}
		]
	},
	"year_four": {
		"harmattan_semester": [
			{
				"code": "CSC 401",
				"title": "Survey of Programming Languages"
			},
			{
				"code": "CSC 401_DB",
				"title": "Database Management Systems"
			},
			{
				"code": "CIT 401",
				"title": "Computer Hardware Systems Design"
			},
			{
				"code": "CSC 403",
				"title": "Algorithms and Complexity Analysis"
			},
			{
				"code": "CSC 405",
				"title": "Computer and Society"
			},
			{
				"code": "CSC 407",
				"title": "Human Computer Interface Design"
			},
			{
				"code": "CSC 409",
				"title": "Computer Applications in Operations Research"
			},
			{
				"code": "CSC 411",
				"title": "Mobile Computing Systems Design"
			},
			{
				"code": "CSC 415",
				"title": "Research Methodology & Capstone Management"
			},
			{
				"code": "IFT 405",
				"title": "Design II"
			},
			{
				"code": "CSC 417",
				"title": "Embedded Systems and Firmware Design"
			},
			{
				"code": "CSC 419",
				"title": "Compiler Construction II"
			},
			{
				"code": "CSC 421",
				"title": "Database Systems Programming"
			},
			{
				"code": "CSC 423",
				"title": "Concurrent Systems Programming"
			},
			{
				"code": "CSC 413",
				"title": "Numerical Computations"
			},
			{
				"code": "STA 451",
				"title": "Design and Analysis of Experiments I"
			}
		],
		"rain_semester": [
			{
				"code": "SIWES 400",
				"title": "Student Industrial Attachment"
			}
		]
	},
	"year_five": {
		"harmattan_semester": [
			{
				"code": "CSC 501",
				"title": "Software Engineering"
			},
			{
				"code": "CSC 503",
				"title": "Information Systems Management"
			},
			{
				"code": "CSC 505",
				"title": "Algorithmic Techniques for Smart Systems"
			},
			{
				"code": "CSC 507",
				"title": "Data Communication Systems"
			},
			{
				"code": "CSC 509",
				"title": "Net-Centric Computing and Data Security"
			},
			{
				"code": "CSC 511",
				"title": "Artificial Intelligence"
			},
			{
				"code": "CSC 513",
				"title": "Data Mining & Big Data Analysis"
			},
			{
				"code": "CSC 555",
				"title": "Final Year Project"
			},
			{
				"code": "CSC 515",
				"title": "Microprocessor Architecture"
			},
			{
				"code": "CSC 517",
				"title": "Distributed Computing Systems Design"
			},
			{
				"code": "CSC 519",
				"title": "Special Topics in Information Technology"
			},
			{
				"code": "CSC 521",
				"title": "Organization of Programming Languages"
			},
			{
				"code": "STA 513",
				"title": "Sampling Theory and Survey Methods II"
			}
		],
		"rain_semester": [
			{
				"code": "CSC 502",
				"title": "Formal Models of Computation"
			},
			{
				"code": "CSC 504",
				"title": "Computer Graphics and Visualization"
			},
			{
				"code": "CSC 506",
				"title": "Computer Communications Networks"
			},
			{
				"code": "CSC 508",
				"title": "System Performance Evaluation"
			},
			{
				"code": "CSC 510",
				"title": "Computer Modeling and Simulation"
			},
			{
				"code": "CSC 514",
				"title": "Special Topics in Software Engineering"
			},
			{
				"code": "CSC 512",
				"title": "The Internet of Things"
			},
			{
				"code": "CSC 556",
				"title": "Final Year Project"
			},
			{
				"code": "CSC 516",
				"title": "Cryptography Algorithms and Applications"
			},
			{
				"code": "STA 502",
				"title": "Decision Theory"
			}
		]
	}
};

function bindCourseCascade(prefix, onSelected) {
	const ysSel = document.getElementById(`${prefix}-year-semester`);
	const courseSel = document.getElementById(`${prefix}-course-select`);
	if (!ysSel || !courseSel) return;
	
	ysSel.addEventListener('change', () => {
		const value = ysSel.value;
		courseSel.innerHTML = '<option value="">— select a course —</option>';
		courseSel.disabled = !value;
		if (!value) {
			onSelected?.('');
			return;
		}
		
		const [year, semester] = value.split(':');
		const courses = COURSES_DATA?.[year]?.[semester] || [];
		courses.forEach(c => {
			const opt = document.createElement('option');
			opt.value = c.code;
			opt.textContent = `${c.code} — ${c.title}`;
			courseSel.appendChild(opt);
		});
	});
	
	courseSel.addEventListener('change', () => onSelected?.(courseSel.value));
	
}


function jumpToCourse() {
	const course = document.querySelector('#dash-course-jump').value.trim().toUpperCase();
	if (!course) {
		toast('Enter a course code.', 'error');
		return;
	}
	navigate('attendance');
	document.querySelector('#av-course-input').value = course;
	loadAttendance();
}

function renderRecentSessions() {
	const tbody = document.querySelector('#dash-recent-tbody');
	if (!tbody) return;
	if (!recentSessions.length) {
		tbody.innerHTML = `<tr>
		<td colspan="4">
			<div class="empty-state" style="padding:20px"><i class="fas fa-clock-rotate-left"></i><p>Sessions you create will show up here for this visit.</p></div>
		</td>
		</tr>`;
		return;
	}
	tbody.innerHTML = recentSessions.map(s => `<tr>
		<td>
			<strong>${s.course}</strong>
		</td>
		<td>
			<code>${s.session_id}</code>
		</td>
		<td>
			${fmtTs(s.created_at)}
		</td>
		<td>
			<button class="btn btn-outline btn-sm" onclick="viewRecentSession('${s.course}','${s.session_id}')">View</button>
		</td>
	</tr>`).join('');
}

function viewRecentSession(course, sessionId) {
	navigate('attendance');
	document.querySelector('#av-course-input').value = course;
	loadAttendance().then(() => {
		document.querySelector('#av-session-select').value = sessionId;
		setAttendanceView('session');
		onAvSessionChange();
	});
}


function autoSessionId() {
	const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	const id = [...Array(7)].map(() => chars[Math.random() * chars.length | 0]).join('');
	document.querySelector('#session-id-input').value = id;
}



async function createSession() {
	const course = document.querySelector('#cs-course-select').value;
	if (!course) {
		toast('Select a course first.', 'error');
		return;
	}
	
	const session_id = document.querySelector('#session-id-input').value.trim().toUpperCase();
	
	if (!session_id) {
		toast('Enter or generate a session ID.', 'error');
		return;
	}
	
	// Rejecting a taken ID before asking for location ──
	try {
		if (await sessionExists(session_id)) {
			toast(`Session ID '${session_id}' is already taken.`, 'error');
			return;
		}
	} catch (e) {
		toast(e.message, 'error');
		return;
	}

	
	const locationData = await getGeofencedSessionData(300);
	
	if (locationData) {
		try {
			const payload = {
				course_code: course,
				session_id: session_id,
				attendance_id: session_id,  // sent alongside session_id in case your endpoint expects this key instead
				tutor_id: LECTURER.tutor_id,
				locationData
			};
			console.log(payload);
			
			const data = await api('/create.php', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify(
					payload
				),
			});
			
			if (data?.error) {
				toast(data.error, 'error');
				return;
			}
			
			const validity = ts();
			const url = `${QR_BASE_URL}?attendance_id=${session_id}`;
			activeQR = {
				url,
				expiresAt: validity + QR_VALIDITY_WINDOW,
				sessionId: session_id
			};
			
			generateQR(url);
			const qrDialog = document.querySelector('.qr-panel');
			const modalDialog = document.querySelector('#session-modal');
			const modalDialogBody = document.querySelector('#modal-body');
			startCountdown(validity, course);
			modalDialogBody.append(qrDialog);
			modalDialog.showModal();
			recentSessions.unshift({
				course,
				session_id,
				created_at: validity
			});
			renderRecentSessions();
			await renderSessionsForCourse(course);
			toast(`Session '${session_id}' created for ${course}.`, 'success');
		} catch (e) {
			toast(e.message, 'error');
		}
	} else {
		toast('Location is required to start a session.', 'error');
		console.error('Location is required to start a session.', 'error');
		return;   // function already set #session-status with the reason
	}
}

function generateQR(text) {
	const container = document.querySelector('#qr-canvas');
	const placeholder = document.querySelector('#qr-placeholder');
	
	const urlBox = document.querySelector('#qr-url-box');
	urlBox.textContent = text;
	urlBox.style.display = 'block';
	document.querySelector('#copy-url-btn').disabled = false;
	document.querySelector('#print-qr-btn').disabled = false;
	
	container.innerHTML = '';
	
	try {
		QRCode.toCanvas(text, {
			width: 256,
			margin: 2,
			color: {
				dark: '#000000',
				light: '#FFFFFF'
			}
		}, function (err, canvas) {
			if (err) {
				console.error('Error generating QR code:', err);
				toast('Failed to generate QR code.', 'error');
				return;
			}
			container.appendChild(canvas);
			placeholder.style.display = 'none';
			container.style.display = 'block';
		});
	} catch (error) {
		console.error('Error generating QR code:', error);
		toast('QR library error: ' + error.message, 'error');
	}
	
}

function startCountdown(validity) {
	clearInterval(countdownTimer);
	const total = QR_VALIDITY_WINDOW;
	const cring = document.querySelector('#cring');
	const clabel = document.querySelector('#clabel');
	const circum = 2 * Math.PI * 24;
	const sessionId = activeQR.sessionId; // capture now, in case activeQR changes later
	
	document.querySelector('#countdown-wrap').style.display = 'block';
	document.querySelector('#expired-notice').style.display = 'none';
	document.querySelector('#expiry-text').textContent = `Valid for ${Math.round(total / 60)} minutes`;
	document.querySelector('#expiry-text').className = 'qr-expiry active';
	
	function tick() {
		const elapsed = ts() - validity;
		const left = Math.max(0, total - elapsed);
		const mins = String(Math.floor(left / 60)).padStart(2, '0');
		const secs = String(left % 60).padStart(2, '0');
		clabel.textContent = `${mins}:${secs}`;
		const pct = left / total;
		cring.style.strokeDashoffset = circum * (1 - pct);
		cring.style.stroke = left > 300 ? 'var(--accent2)' : left > 60 ? 'var(--accent3)' : 'var(--danger)';
		
		if (left === 0) {
			clearInterval(countdownTimer);
			document.querySelector('#countdown-wrap').style.display = 'none';
			document.querySelector('#expired-notice').style.display = 'block';
			document.querySelector('#session-modal').close();
			document.querySelector('#modal-body').innerHTML = '';
			
			const deactivateSession = api('/update_sess.php', {
				method: 'PATCH',
				headers: {
					'Content-Type': 'application/json'
				},
				body: JSON.stringify({
					session_Id: sessionId
				}),
			}).then(function (deactivateSession) {
				if (deactivateSession && deactivateSession.error) {
					toast(deactivateSession.error, 'error');
					return;
				}
				toast(`Session '${sessionId}' for ${course}`, 'ended successfully.');
			}).catch(function (err) {
				console.error('Failed to deactivate session:', err);
				toast('Failed to end session: ' + err.message, 'error');
			});
		}
	}
	tick();
	countdownTimer = setInterval(tick, 1000);
}

function copyURL() {
	if (!activeQR) return;
	navigator.clipboard.writeText(activeQR.url).then(() => toast('URL copied to clipboard.', 'success'));
}

function printQR() {
	window.print();
}

async function renderSessionsForCourse(course) {
	const tbody = document.querySelector('#sessions-tbody');
	const title = document.querySelector('#sessions-list-title');
	title.textContent = course ? `Sessions — ${course}` : 'Sessions for this course';
	
	if (!course) {
		clearSessionsList();
		return;
	}
	
	try {
		const summary = await api(`/summary.php?course_code=${encodeURIComponent(course)}&tutor_id=${LECTURER.tutor_id}`);
		const sessions = extractSessions(summary).map(normSession);
		
		if (!sessions.length) {
			tbody.innerHTML = `<tr>
				<td colspan="4">
					<div class="empty-state" style="padding:20px"><i class="fas fa-calendar-xmark"></i><p>No sessions yet.</p></div>
				</td>
			</tr>`;
			return;
		}
		tbody.innerHTML = [...sessions].reverse().map(s => {
			// summary.php now resolves active/expired itself (time_status),
			// and normSession folds that into is_active — no more recomputing
			// it client-side from created_at + QR_VALIDITY_WINDOW.
			const active = s.is_active === true;
			return `<tr>
				<td><code>${s.id}</code></td>
				<td>${s.created_at ? fmtTs(s.created_at) : '—'}</td>
				<td>${s.signins ?? '—'}</td>
				<td>${active
					? `<span class="badge badge-green"><i class="fas fa-circle" style="font-size:7px"></i> Active</span>`
					: `<span class="badge badge-gray">Expired</span>`
				}</td>
			</tr>`;
		}).join('');
	} catch (e) {
		toast(e.message, 'error');
	}
}

function clearSessionsList() {
	document.querySelector('#sessions-tbody').innerHTML = `
		<tr>
			<td colspan="4">
				<div class="empty-state" style="padding:20px"><i class="fas fa-calendar-xmark"></i><p>Select a course to see its sessions.</p></div>
			</td>
		</tr>
	`;
}


// ═══════════════════════════════════════════════════════════════
//  UI: ATTENDANCE VIEWER
// ═══════════════════════════════════════════════════════════════

async function loadAttendance() {
	const course = document.querySelector('#av-course-input').value.trim().toUpperCase();
	if (!course) {
		toast('Enter a course code.', 'error');
		return;
	}
	
	document.querySelector('#av-empty').style.display = 'none';
	
	try {
		const summary = await api(`/summary.php?course_code=${encodeURIComponent(course)}&tutor_id=${LECTURER.tutor_id}`);
		avSessions = extractSessions(summary).map(normSession);
		
		const sel = document.querySelector('#av-session-select');
		const prevSelected = sel.value;
		sel.innerHTML = '<option value="">— select session —</option>';
		avSessions.forEach(s => {
			const opt = document.createElement('option');
			opt.value = s.id;
			opt.textContent = s.created_at ? `${s.id} — ${fmtTs(s.created_at)}` : s.id;
			sel.appendChild(opt);
		});
		if (prevSelected && avSessions.some(s => s.id === prevSelected)) sel.value = prevSelected;
		
		await renderCourseBlock(course, avSessions);
		
		document.querySelector('#av-course-block').style.display = 'block';
		applyAttendanceView();
		if (sel.value) await onAvSessionChange();
	} catch (e) {
		toast(e.message, 'error');
	}
}

async function renderCourseBlock(course, sessions) {
	document.querySelector('#av-course-label').textContent = `${course} — ${sessions.length} session${sessions.length !== 1 ? 's' : ''}`;
	
	const stbody = document.querySelector('#av-sessions-tbody');
	const statsEl = document.querySelector('#av-course-stats');
	const tbody = document.querySelector('#av-course-tbody');
	
	if (!sessions.length) {
		stbody.innerHTML = `<tr><td colspan="4"><div class="empty-state" style="padding:20px"><i class="fas fa-calendar-xmark"></i><p>No sessions yet.</p></div></td></tr>`;
		statsEl.innerHTML = '';
		tbody.innerHTML = `<tr>
			<td colspan="5"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No sign-ins recorded yet.</p></div></td>
		</tr>`;
		return;
	}
	
	// Aggregating per-student totals. Uses attendees embedded in the summary response when present, otherwise falls back to fetching each session's roster in parallel — works either way summary.php is shaped
	const attendeeLists = await Promise.all(sessions.map(s =>
		s.attendees ? Promise.resolve(s.attendees.map(normAttendee)) : fetchSessionAttendance(s.id)
	));
	
	// Backfill each session's sign-in count from the fetched roster before
	// rendering the sessions table, so "Sign-ins" reflects real data instead
	// of only whatever count (if any) summary.php embedded directly.
	sessions.forEach((s, i) => { s.signins = attendeeLists[i].length; });
	
	stbody.innerHTML = [...sessions].reverse().map(s => `
	<tr>
		<td><code>${s.id}</code></td>
		<td>${s.created_at ? fmtTs(s.created_at) : '—'}</td>
		<td>${s.signins ?? '—'}</td>
		<td><button class="btn btn-outline btn-sm" onclick="jumpToSession('${s.id}')">View</button></td>
	</tr>
	`).join('');
	
	const counts = {};
	let totalSignins = 0;
	attendeeLists.forEach(list => {
		list.forEach(a => {
			if (!a.reg_no) return;
			counts[a.reg_no] = (counts[a.reg_no] || 0) + 1;
			totalSignins++;
		});
	});
	const entries = Object.entries(counts);
	
	statsEl.innerHTML = `
		<div class="stat-card" style="--card-accent:var(--accent)">
			<div class="stat-label">Total Sessions</div>
			<div class="stat-value">${sessions.length}</div>
			<i class="fas fa-calendar stat-icon"></i>
		</div>
		<div class="stat-card" style="--card-accent:var(--accent2)">
			<div class="stat-label">Unique Students</div>
			<div class="stat-value">${entries.length}</div>
			<i class="fas fa-users stat-icon"></i>
		</div>
		<div class="stat-card" style="--card-accent:#2563eb">
			<div class="stat-label">Total Sign-ins</div>
			<div class="stat-value">${totalSignins}</div>
			<i class="fas fa-signature stat-icon"></i>
		</div>
	`;
	
	if (!entries.length) {
		tbody.innerHTML = `<tr>
			<td colspan="5"><div class="empty-state"><i class="fas fa-user-slash"></i><p>No sign-ins recorded yet.</p></div></td>
		</tr>`;
	} else {
		tbody.innerHTML = entries.sort((a, b) => b[1] - a[1]).map(([reg_no, count]) => {
			const pct = sessions.length ? Math.round((count / sessions.length) * 100) : 0;
			const color = pct >= 75 ? 'var(--accent2)' : pct >= 50 ? 'var(--accent3)' : 'var(--danger)';
			return `<tr>
				<td><strong>${reg_no}</strong></td>
				<td>${count}</td>
				<td>${sessions.length}</td>
				<td>
					<div style="display:flex;align-items:center;gap:8px">
						<div class="progress-track" style="width:90px">
							<div class="progress-fill" style="width:${pct}%;background:${color}"></div>
						</div>
						<span style="font-weight:700;font-size:13px">${pct}%</span>
					</div>
				</td>
				<td>${pct >= 75
					? '<span class="badge badge-green">On Track</span>'
					: pct >= 50
					? '<span class="badge badge-amber">At Risk</span>'
					: '<span class="badge badge-red">Below Min</span>'}</td>
				</td>
			</tr>`;
		}).join('');
	}
}

async function onAvSessionChange() {
	const sid = document.querySelector('#av-session-select').value;
	const tbody = document.querySelector('#av-session-tbody');
	const countEl = document.querySelector('#av-session-count');
	
	if (!sid) {
		tbody.innerHTML = `<tr>
			<td colspan="3">
				<div class="empty-state"><i class="fas fa-users"></i><p>Select a session to load its roster.</p></div>
			</td>
		</tr>`;
		countEl.textContent = '';
		applyAttendanceView();
		return;
	}
	
	tbody.innerHTML = `<tr>
		<td colspan="3">
			<div class="empty-state" style="padding:20px"><i class="fas fa-spinner fa-spin"></i><p>Loading…</p></div>
		</td>
	</tr>`;
	applyAttendanceView();
	
	const records = await fetchSessionAttendance(sid);
	countEl.textContent = `${records.length} student${records.length !== 1 ? 's' : ''}`;
	
	if (!records.length) {
		tbody.innerHTML = `<tr>
			<td colspan="3">
				<div class="empty-state"><i class="fas fa-user-slash"></i><p>No sign-ins for this session yet.</p></div>
			</td>
		</tr>`;
		return;
	}
	tbody.innerHTML = records.map((r, i) => `
	<tr>
		<td>${i + 1}</td>
		<td><strong>${r.reg_no}</strong></td>
		<td>${r.signed_at ? fmtTs(r.signed_at) : '—'}</td>
	</tr>
	`).join('');
}

function jumpToSession(sessionId) {
	document.querySelector('#av-session-select').value = sessionId;
	setAttendanceView('session');
	onAvSessionChange();
}

function setAttendanceView(mode) {
	attendanceView = mode;
	
	document.querySelectorAll('#av-view-toggle button').forEach(b => {
		b.classList.toggle('is-active', b.dataset.view === mode);
	});
	applyAttendanceView();
}

function applyAttendanceView() {
	const courseBlock = document.querySelector('#av-course-block');
	const sessionBlock = document.querySelector('#av-session-block');
	const hasCourseData = courseBlock.innerHTML.trim() !== '';
	const sid = document.querySelector('#av-session-select').value;
	
	courseBlock.style.display = (hasCourseData && (attendanceView === 'course' || attendanceView === 'both')) ? 'block' : 'none';
	sessionBlock.style.display = (sid && (attendanceView === 'session' || attendanceView === 'both')) ? 'block' : 'none';
}


// ═══════════════════════════════════════════════════════════════
//  NAVIGATION
// ═══════════════════════════════════════════════════════════════

function navigate(pageId) {
	document.querySelectorAll('.nav-item').forEach(i => i.classList.remove('active'));
	document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
	const navEl = document.querySelector(`.nav-item[data-page="${pageId}"]`);
	const pageEl = document.querySelector(`#page-${pageId}`);
	if (navEl) navEl.classList.add('active');
	if (pageEl) pageEl.classList.add('active');
}

document.querySelectorAll('.nav-item').forEach(item => {
	item.addEventListener('click', e => {
		e.preventDefault();
		navigate(item.dataset.page);
	});
});


// ═══════════════════════════════════════════════════════════════
//  MODAL (reserved — not currently opened anywhere)
// ═══════════════════════════════════════════════════════════════

function closeModal(e) {
	if (e.target === e.currentTarget) e.currentTarget.classList.remove('open');
}
function closeModalById(id) {
	document.getElementById(id).classList.remove('open');
}


// ═══════════════════════════════════════════════════════════════
//  TOAST
// ═══════════════════════════════════════════════════════════════

function toast(msg, type = 'info') {
	const el = document.querySelector('#toast');
	const msgEl = document.querySelector('#toast-msg');
	const icons = {
		success:'fa-circle-check',
		error:'fa-circle-xmark',
		info:'fa-circle-info'
	};
	el.querySelector('i').className = `fas ${icons[type] || icons.info} ti`;
	el.className = `show ${type}`;
	msgEl.textContent = msg;
	clearTimeout(el._t);
	el._t = setTimeout(() => el.classList.remove('show'), 4000);
}


// ── Init ────────────────────────────────────────────────────────

bindCourseCascade('cs', code => {
	renderSessionsForCourse(code);
});
document.querySelector('#av-session-select').addEventListener('change', onAvSessionChange);