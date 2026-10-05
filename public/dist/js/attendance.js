// attendance.js

/**
 * Runs the passkey sign-in + attendance-recording step.
 *
 * @param {{studentId: string, email: string, deviceId: string, sessionId: string}} identity
 *   Matches what signin_options.php / signin_verify.php expect.
 * @param {{submitBtn?: HTMLElement}} [ctx] Optional caller context (e.g. the
 *   FormValidator instance) so the submit button can be re-enabled/disabled.
 */
async function studentPasskey(identity, ctx = {}) {

	// 1. Check if browser or device support passkeys
	if (!window.PublicKeyCredential) {
		setStMessage("This browser or device doesn't support passkeys. Please try again from a supported browser.");
		return null;
	}

	try {
		// 2. Ask the server for a WebAuthn assertion challenge for this student
		const options = await startPasskeySignin(identity);

		if (!options) {
			// startPasskeySignin() already logged the reason via api();
			// nothing more to do here.
			return null;
		}

		// 3. Decode base64url fields back into ArrayBuffers for the browser API
		const publicKey = {
			...options,
			challenge: bufferDecode(options.challenge),
			allowCredentials: (options.allowCredentials || []).map(cred => ({
				...cred,
				id: bufferDecode(cred.id),
			})),
		};

		// 4. Prompt the platform authenticator (fingerprint / face / device PIN)
		const assertion = await navigator.credentials.get({ publicKey });

		// 5. Re-encode the assertion for transport back to the server
		const credential = {
			id: assertion.id,
			rawId: bufferEncode(assertion.rawId),
			type: assertion.type,
			response: {
				authenticatorData: bufferEncode(assertion.response.authenticatorData),
				clientDataJSON: bufferEncode(assertion.response.clientDataJSON),
				signature: bufferEncode(assertion.response.signature),
				userHandle: assertion.response.userHandle ? bufferEncode(assertion.response.userHandle) : null,
			},
		};

		// 6. Send the signed assertion back to the server to verify and mark attendance
		const result = await verifyPasskeySignin({
			...identity,
			credential,
		});

		if (ctx.submitBtn) {
			ctx.submitBtn.disabled = true;
		}

		return result;

	} catch (err) {
		console.error('Passkey sign-in failed:', err);

		// api() already logged server-provided messages (err.shown === true).
		// This covers everything else, e.g. NotAllowedError when the student
		// cancels the Face/Touch ID prompt, or a network failure.
		if (!err.shown) {
			setStMessage(err.message || "Attendance couldn't be verified. Please try again.");
		}

		if (ctx.submitBtn) {
			ctx.submitBtn.disabled = false;
		}
		return null;
	}
}
