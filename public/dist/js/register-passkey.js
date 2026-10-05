

// -----------------------------
// Helpers
// -----------------------------

const regDeviceId = window.deviceId;
function showError(message = "") {
	errorBox.innerHTML = message;
	errorBox.style.display = message ? "block" : "none";
}

function showSuccess(message = "") {
	successBox.innerHTML = message;
	successBox.style.display = message ? "block" : "none";
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

function setSubmitting(isSubmitting, label = "REGISTER PASSKEY") {
	registerBtn.disabled = isSubmitting;
	registerBtn.textContent = isSubmitting ? "REGISTERING…" : label;
}


// -----------------------------
// UserAgent Parsing
// -----------------------------
function parseUserAgent() {
    const ua = navigator.userAgent;

    let os = "Unknown OS";
    let browser = "Unknown Browser";

    if (/Android/i.test(ua)) {
        os = "Android";
    } else if (/iPhone|iPad|iPod/i.test(ua)) {
        os = "iOS";
    } else if (/Windows NT/i.test(ua)) {
        os = "Windows";
    } else if (/Macintosh|Mac OS X/i.test(ua)) {
        os = "macOS";
    } else if (/Linux/i.test(ua)) {
        os = "Linux";
    } else if (/CrOS/i.test(ua)) {
        os = "ChromeOS";
    }

    if (/Edg\//i.test(ua)) {
        browser = "Edge";
    } else if (/OPR\//i.test(ua)) {
        browser = "Opera";
    } else if (/CriOS\//i.test(ua)) {
        browser = "Chrome";
    } else if (/Chrome\//i.test(ua)) {
        browser = "Chrome";
    } else if (/FxiOS\//i.test(ua)) {
        browser = "Firefox";
    } else if (/Firefox\//i.test(ua)) {
        browser = "Firefox";
    } else if (/Safari\//i.test(ua) && !/Chrome|CriOS/i.test(ua)) {
        browser = "Safari";
    }

    const allowed =
        (os === "Android" && browser === "Chrome") ||
        (os === "iOS" && (browser === "Chrome" || browser === "Safari"));

    return {
        os: os,
        browser: browser,
        lang: navigator.language || "en",
        userAgent: ua,
        allowed: allowed
    };
}


// -----------------------------
// Register: WebAuthn passkey creation
// -----------------------------
registerBtn.addEventListener("click", async function() {
	const checkDevice = parseUserAgent();
	
	showError("");
	showSuccess("");
	
	// Check if browser/device supports passkeys
	if (!window.PublicKeyCredential) {
		showError("This browser or device doesn't support passkeys. Please try again from a supported browser.");
		return;
	}
	
	
	if (checkDevice.allowed) {
		setSubmitting(true);
		
		try {
			console.log('Student Device ID (regDeviceId): ', regDeviceId);
			
			
			// 1. Ask the server for WebAuthn registration options (challenge, RP info, etc.)
			//    The server already knows who this is from the session, but we send
			//    studentId/email along too so the endpoint doesn't have to guess.
			const optionsRes = await fetch("/api/register_options.php", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					studentId: studentId,
					email: studentEmail,
					deviceId: regDeviceId,
				}),
			});
			
			if (!optionsRes.ok) {
				const err = await optionsRes.json().catch(() => ({}));
				throw new Error(err.message || "Couldn't start passkey registration. Please try again.");
			}
			
			const options = await optionsRes.json();
			
			// 2. Decode base64url fields back into ArrayBuffers for the browser API
			const publicKey = {
				...options,
				challenge: bufferDecode(options.challenge),
				user: {
					...options.user,
					id: bufferDecode(options.user.id),
				},
				excludeCredentials: (options.excludeCredentials || []).map(cred => ({
					...cred,
					id: bufferDecode(cred.id),
				})),
				
			};
			
			// 3. Prompt the platform authenticator (fingerprint / face / device PIN)
			// to create a new passkey
			const credential = await navigator.credentials.create({ publicKey });
			
			// 4. Re-encode the new credential for transport back to the server
			const credentialPayload = {
				id: credential.id,
				rawId: bufferEncode(credential.rawId),
				type: credential.type,
				response: {
					attestationObject: bufferEncode(credential.response.attestationObject),
					clientDataJSON: bufferEncode(credential.response.clientDataJSON),
					transports: credential.response.getTransports  ?  credential.response.getTransports() : [],
				},
			};
			
			// 5. Send the new passkey to the server, along with who it belongs to
			// and a bit of device context
			const verifyRes = await fetch("/api/register_verify.php", {
				method: "POST",
				headers: { "Content-Type": "application/json" },
				body: JSON.stringify({
					studentId: studentId,
					email: studentEmail,
					deviceId: regDeviceId,
					userAgent: parseUserAgent(),
					credential: credentialPayload,
				}),
			});
			
			if (!verifyRes.ok) {
				const err = await verifyRes.json().catch(() => ({}));
				throw new Error(err.message || "Passkey couldn't be saved. Please try again.");
			}
			
			setSubmitting(false, "PASSKEY REGISTERED ✓");
			registerBtn.disabled = true;
			showSuccess("Your passkey is ready — you can use it to sign attendance from now on.");
		} catch (err) {
			setSubmitting(false);
			
			if (err.name === "NotAllowedError") {
				showError("Passkey registration was cancelled or timed out. Please try again.");
			} else if (err.name === "InvalidStateError") {
				showError("A passkey for this device is already registered.");
			} else {
				showError(err.message || "Something went wrong. Please try again.");
			}
		}
	} else {
		showError("Passkey registration isn't supported on this browser/device. Please use Chrome on Android or Safari/Chrome on iOS.");
	}
	
});










