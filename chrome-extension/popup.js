// State management
let state = 'not_connected'; // not_connected, ready, recording, uploading, uploaded, error
let timerInterval = null;

// On popup open
document.addEventListener('DOMContentLoaded', async () => {
    const { apiKey, serverUrl } = await chrome.storage.local.get(['apiKey', 'serverUrl']);

    if (apiKey && serverUrl) {
        try {
            const response = await fetch(`${serverUrl}/api/v1/extension/ping`, {
                headers: { 'X-Api-Key': apiKey, 'Accept': 'application/json' }
            });
            if (response.ok) {
                const data = await response.json();
                setState('ready');
                document.getElementById('org-name').textContent = data.organization.name;
            } else {
                setState('not_connected');
                showSettingsError('API key invalid or revoked. Please update your settings.');
            }
        } catch (e) {
            setState('not_connected');
            showSettingsError('Cannot connect to server.');
        }
    } else {
        setState('not_connected');
    }

    // Load saved settings into inputs
    if (apiKey) document.getElementById('api-key-input').value = apiKey;
    if (serverUrl) document.getElementById('server-url-input').value = serverUrl;

    // Check if there's an active recording via background
    try {
        const bgState = await chrome.runtime.sendMessage({ type: 'GET_STATE' });
        if (bgState && bgState.recording) {
            setState('recording');
            startTimerDisplay(bgState.startTime);
        }
    } catch (e) {
        // Background may not be ready yet
    }
});

// Save settings
document.getElementById('save-settings-btn').addEventListener('click', async () => {
    const apiKey = document.getElementById('api-key-input').value.trim();
    const serverUrl = document.getElementById('server-url-input').value.trim().replace(/\/$/, '');

    if (!apiKey || !serverUrl) {
        showSettingsError('Both fields are required.');
        return;
    }

    try {
        const response = await fetch(`${serverUrl}/api/v1/extension/ping`, {
            headers: { 'X-Api-Key': apiKey, 'Accept': 'application/json' }
        });
        if (response.ok) {
            await chrome.storage.local.set({ apiKey, serverUrl });
            const data = await response.json();
            setState('ready');
            document.getElementById('org-name').textContent = data.organization.name;
        } else {
            showSettingsError('API key invalid or revoked.');
        }
    } catch (e) {
        showSettingsError('Cannot connect to server.');
    }
});

// Start recording
document.getElementById('start-btn').addEventListener('click', async () => {
    chrome.runtime.sendMessage({ type: 'START_RECORDING' }, (response) => {
        if (response && response.success) {
            setState('recording');
            startTimerDisplay(Date.now());
        } else {
            showError(response?.error || 'Failed to start recording.');
        }
    });
});

// Stop recording
document.getElementById('stop-btn').addEventListener('click', () => {
    chrome.runtime.sendMessage({ type: 'STOP_RECORDING' }, (response) => {
        if (response && response.success) {
            setState('uploading');
        }
    });
});

// New recording
document.getElementById('new-recording-btn').addEventListener('click', () => {
    setState('ready');
});

// Retry
document.getElementById('retry-btn').addEventListener('click', () => {
    chrome.runtime.sendMessage({ type: 'RETRY_UPLOAD' });
    setState('uploading');
});

// Settings button
document.getElementById('settings-btn').addEventListener('click', () => {
    setState('not_connected');
});

// Listen for messages from background
chrome.runtime.onMessage.addListener((message) => {
    if (message.type === 'UPLOAD_PROGRESS') {
        document.getElementById('upload-bar').value = message.progress;
    } else if (message.type === 'UPLOAD_COMPLETE') {
        setState('uploaded');
        document.getElementById('open-in-flowcast').href = message.url;
    } else if (message.type === 'UPLOAD_ERROR') {
        showError(message.error);
    }
});

// Helper functions
function setState(newState) {
    state = newState;

    // Hide all sections
    document.getElementById('settings-section').style.display = 'none';
    document.getElementById('status-section').style.display = 'none';
    document.getElementById('ready-controls').style.display = 'none';
    document.getElementById('recording-controls').style.display = 'none';
    document.getElementById('uploading-controls').style.display = 'none';
    document.getElementById('uploaded-controls').style.display = 'none';
    document.getElementById('error-controls').style.display = 'none';

    // Clear timer if not recording
    if (newState !== 'recording' && timerInterval) {
        clearInterval(timerInterval);
        timerInterval = null;
    }

    const statusDot = document.getElementById('status-dot');

    switch (newState) {
        case 'not_connected':
            document.getElementById('settings-section').style.display = 'block';
            break;

        case 'ready':
            document.getElementById('status-section').style.display = 'block';
            document.getElementById('ready-controls').style.display = 'block';
            statusDot.className = '';
            document.getElementById('status-text').textContent = 'Ready';
            break;

        case 'recording':
            document.getElementById('status-section').style.display = 'block';
            document.getElementById('recording-controls').style.display = 'block';
            statusDot.className = 'recording';
            document.getElementById('status-text').textContent = 'Recording';
            break;

        case 'uploading':
            document.getElementById('status-section').style.display = 'block';
            document.getElementById('uploading-controls').style.display = 'block';
            statusDot.className = '';
            document.getElementById('status-text').textContent = 'Uploading';
            document.getElementById('upload-bar').value = 0;
            break;

        case 'uploaded':
            document.getElementById('status-section').style.display = 'block';
            document.getElementById('uploaded-controls').style.display = 'block';
            statusDot.className = '';
            document.getElementById('status-text').textContent = 'Uploaded';
            break;

        case 'error':
            document.getElementById('status-section').style.display = 'block';
            document.getElementById('error-controls').style.display = 'block';
            statusDot.className = '';
            document.getElementById('status-text').textContent = 'Error';
            break;
    }
}

function showSettingsError(msg) {
    const el = document.getElementById('settings-error');
    el.textContent = msg;
    el.style.display = 'block';
}

function showError(msg) {
    setState('error');
    document.getElementById('error-message').textContent = msg;
}

function startTimerDisplay(startTime) {
    if (timerInterval) clearInterval(timerInterval);

    const timerEl = document.getElementById('recording-timer');

    function updateTimer() {
        const elapsed = Math.floor((Date.now() - startTime) / 1000);
        const minutes = Math.floor(elapsed / 60).toString().padStart(2, '0');
        const seconds = (elapsed % 60).toString().padStart(2, '0');
        timerEl.textContent = `${minutes}:${seconds}`;
    }

    updateTimer();
    timerInterval = setInterval(updateTimer, 1000);
}
