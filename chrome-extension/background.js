let mediaRecorder = null;
let recordedChunks = [];
let recordingStartTime = null;
let pendingBlob = null;

// Message handler
chrome.runtime.onMessage.addListener((message, sender, sendResponse) => {
    switch (message.type) {
        case 'GET_STATE':
            sendResponse({ recording: !!mediaRecorder, startTime: recordingStartTime });
            return true;

        case 'START_RECORDING':
            startRecording(sendResponse);
            return true;

        case 'STOP_RECORDING':
            stopRecording(sendResponse);
            return true;

        case 'RETRY_UPLOAD':
            if (pendingBlob) uploadRecording(pendingBlob);
            sendResponse({ success: true });
            return true;
    }
});

async function startRecording(sendResponse) {
    try {
        const streamId = await chrome.tabCapture.getMediaStreamId({});

        recordedChunks = [];
        recordingStartTime = Date.now();
        sendResponse({ success: true });
    } catch (e) {
        sendResponse({ success: false, error: e.message });
    }
}

async function stopRecording(sendResponse) {
    if (mediaRecorder && mediaRecorder.state !== 'inactive') {
        mediaRecorder.stop();
    }
    recordingStartTime = null;
    sendResponse({ success: true });
}

async function uploadRecording(blob) {
    const { apiKey, serverUrl } = await chrome.storage.local.get(['apiKey', 'serverUrl']);

    const formData = new FormData();
    formData.append('file', blob, `recording-${Date.now()}.webm`);
    formData.append('title', `Screen Recording ${new Date().toLocaleString()}`);

    try {
        const response = await fetch(`${serverUrl}/api/v1/recordings/from-extension`, {
            method: 'POST',
            headers: { 'X-Api-Key': apiKey, 'Accept': 'application/json' },
            body: formData
        });

        if (response.ok) {
            const data = await response.json();
            pendingBlob = null;
            chrome.runtime.sendMessage({
                type: 'UPLOAD_COMPLETE',
                url: `${serverUrl}/app/recordings/${data.data.id}`
            });
        } else if (response.status === 401 || response.status === 403) {
            chrome.runtime.sendMessage({
                type: 'UPLOAD_ERROR',
                error: 'API key invalid or revoked. Please update your settings.'
            });
        } else {
            const errorData = await response.json();
            chrome.runtime.sendMessage({
                type: 'UPLOAD_ERROR',
                error: errorData.error?.message || 'Upload failed.'
            });
        }
    } catch (e) {
        pendingBlob = blob;
        chrome.runtime.sendMessage({
            type: 'UPLOAD_ERROR',
            error: 'Network error. Recording saved locally for retry.'
        });
    }
}
