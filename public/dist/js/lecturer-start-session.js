/**
 * Lecturer flow: capture location, then start a geofenced attendance session.
 * Wire this to your "Start Session" button.
 */
async function startGeofencedSession(courseId, radiusM = 250) {
  const statusEl = document.getElementById('session-status');

  if (!('geolocation' in navigator)) {
    if (statusEl) statusEl.textContent = 'Your browser does not support geolocation. Cannot start session.';
    return null;
  }

  if (statusEl) statusEl.textContent = 'Getting your location…';

  return new Promise((resolve) => {
    navigator.geolocation.getCurrentPosition(
      async (position) => {
        const { latitude, longitude, accuracy } = position.coords;

        if (accuracy > 50) {
          const proceed = confirm(
            `Your location accuracy is low (±${Math.round(accuracy)}m). ` +
            `Some valid students near the edge of the ${radiusM}m radius may get rejected. Continue anyway?`
          );
          if (!proceed) {
            if (statusEl) statusEl.textContent = 'Session start cancelled.';
            resolve(null);
            return;
          }
        }

        try {
          const res = await fetch('/api/sessions/start.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              course_id: courseId,
              lat: latitude,
              lng: longitude,
              accuracy_m: accuracy,
              radius_m: radiusM,
            }),
          });
          const data = await res.json();
          if (!res.ok) throw new Error(data.error || 'Failed to start session');

          if (statusEl) statusEl.textContent = `Session started. Attendance allowed within ${radiusM}m.`;
          resolve(data);
        } catch (err) {
          if (statusEl) statusEl.textContent = 'Error: ' + err.message;
          resolve(null);
        }
      },
      () => {
        if (statusEl) statusEl.textContent = 'Location permission denied. A session cannot start without location.';
        resolve(null);
      },
      { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 }
    );
  });
}
