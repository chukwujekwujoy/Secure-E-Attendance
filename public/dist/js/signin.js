// signin.js
// Requires net-log.js and student-helpers.js to be loaded before this module
// (they define NetLog, setStMessage, studentExists, fetchGeoFence, ...).

export class FormValidator {
	constructor(config) {
		this.form = config.form;

		this.matric = config.matric || null;

		this.submitBtn = config.submitBtn;

		this.matricError = config.matricError || null;

		// No longer used: messages now go to #network_messages via NetLog.
		// Kept so existing callers that still pass it don't break.
		this.stMessages = config.stMessages || null;

		this.matricRegex = config.matricRegex || /^(202[0-6])\d{7}$/;

		// Filled in by validateMatric() from student_verify.php's response,
		// then used by handleSubmit() to build the identity payload for
		// signin_options.php / signin_verify.php.
		this.studentEmail = null;
		this.deviceId = null;

		this.init();
	}

	init() {
		this.submitBtn.disabled = true;

		if (this.matric) this.matric.focus();

		this.setupInput(this.matric);

		this.setupCursor(this.submitBtn, this.submitBtn);

		this.form.addEventListener('submit', (e) => this.handleSubmit(e));
	}

	setupInput(input) {
		if (!input) return;

		const container = input.closest('div');

		input.addEventListener('focus', () => container.classList.add('focus'));

		input.addEventListener('blur', () => container.classList.remove('focus'));

		this.setupCursor(input, container);

		if (input === this.matric) {
			input.addEventListener('input', () => this.validateMatric());
		}

		input.addEventListener('keydown', async (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();

				if (input === this.matric) {
					const valid = await this.validateMatric();

					if (valid) {
						this.form.requestSubmit();
						document.querySelector('#attendDetails').showModal();
					}
				}
			}
		});
	}

	setupCursor(input, container) {
		container.addEventListener('mouseenter', () => {
			if (input.disabled) {
				container.style.cursor = 'not-allowed';
			} else if (input.tagName.toLowerCase() === 'button') {
				container.style.cursor = 'pointer';
			} else {
				container.style.cursor = 'text';
			}
		});

		container.addEventListener('mouseleave', () => {
			container.style.cursor = '';
		});
	}

	// Matric No Validation
	async validateMatric() {
		const container = this.matric.closest('div');
		const matricNo = this.matric.value.trim();

		this.submitBtn.disabled = true;

		// First we validate the matric number format
		if (!this.matricRegex.test(matricNo)) {
			if (this.matricError) {
				this.matricError.textContent =
					'Matric Number must be exactly 11 digits and begin with 2020–2026!';
			}

			this.setInvalid(container);
			return false;
		}

		// Guarded: matricError is optional in the constructor config
		if (this.matricError) {
			this.matricError.textContent = '';
		}

		try {
			// studentExists() returns the full response body, so we can pick
			// up email/deviceId for later steps. api() has already typed the
			// server's message into the log (green on success, red on error).
			const res = await studentExists(matricNo);

			if (!res?.success) {
				this.setInvalid(container);
				return false;
			}

			this.studentEmail = res.data?.StudentEmail ?? null;
			this.deviceId = res.data?.deviceID ?? null;

			this.setValid(container);

			this.submitBtn.style.display = "block";
			this.submitBtn.disabled = false;

			return true;

		} catch (error) {
			console.error('Student verification failed:', error);

			// api() flags errors whose server message is already on screen
			if (!error.shown) {
				setStMessage(error.message || "Couldn't verify your matric number. Please try again.", 'error');
			}

			this.setInvalid(container);
			return false;
		}
	}

	async handleSubmit(e) {
		e.preventDefault();

		// Fresh log for each attempt
		NetLog.clear();

		const matricNo = this.matric.value.trim();

		const matricValid = await this.validateMatric();

		if (!matricValid) {
			this.matric.focus();
			return;
		}
		
		document.querySelector('#attendDetails').showModal();

		if (!attendID || attendID === "") {
			alert("Invalid Attendance link! Redirecting you for proper QR Code scan.");
			window.location.href = "/index.php";
			return;
		}

		this.submitBtn.disabled = true;

		let attended = false;

		try {
			// The attendance session code is the global `attendID` set
			// server-side in students-signin.php.
			const geo = await fetchGeoFence(attendID);
			

			if (!geo?.success) {
				// api()/setStMessage() already surfaced the reason (denied
				// location, outside the geofence, etc).
				return;
			}

			// studentPasskey() returns the signin_verify.php response on
			// success and null on any failure.
			const result = await studentPasskey({
				studentId: matricNo,
				email: this.studentEmail,
				deviceId: this.deviceId,
				sessionId: attendID,
			}, this);

			attended = result?.success === true;

		} catch (error) {
			console.error('Sign-in failed:', error);

			if (!error.shown) {
				setStMessage(error.message || "Sign-in failed. Please try again.", 'error');
			}
		} finally {
			// Only re-enable on failure. Previously this ran unconditionally,
			// which undid studentPasskey()'s "disable after success" and let
			// the student submit attendance a second time.
			if (!attended) {
				this.submitBtn.disabled = false;
			}
		}
	}

	// Updating UI feedback
	setValid(container) {
		container.style.border = "2px solid blue";
	}

	setInvalid(container) {
		container.style.border = "2px solid red";
	}
}