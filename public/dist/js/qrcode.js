// ═══════════════════════════════════════════════════════════════
//  UI: QR
// ═══════════════════════════════════════════════════════════════

function generateQR(text) {
	const container = document.getElementById('qr-canvas');
	const placeholder = document.getElementById('qr-placeholder');
	
	const urlBox = document.getElementById('qr-url-box');
	urlBox.textContent = text;
	urlBox.style.display = 'block';
	document.getElementById('copy-url-btn').disabled = false;
	document.getElementById('print-qr-btn').disabled = false;
	
	container.innerHTML = '';
	
	// Create a <canvas> element to pass to QRCode.toCanvas
	const canvas = document.createElement('canvas');
	
	try {
		QRCode.toCanvas(text, {
			width: 256,
			margin: 2,
			color: {
				dark: '#000000',
				light: '#FFFFFF'
			}
			
		}, function(err, canvas) {
			if (err) {
				console.error('Error generating QR code:', err);
				console.error('QRCode Error: ', err.message);
				toast('Failed to generate QR code.', 'error');
				return;
			}
			
			// Success — reveal QRCode
			container.appendChild(canvas);
			placeholder.style.display = 'none';
			container.style.display = 'block';
		});
	} catch (error) {
		console.error('Error generating QR code:', error);
		console.error('Error generating QR code:', error.message);
		toast('QR library error: ' + error.message, 'error');
	}
}

function copyURL() {
	if (!activeQR) return;
	navigator.clipboard.writeText(activeQR.url).then(() => toast('URL copied to clipboard.', 'success'));
}

function printQR() {
	window.print();
}