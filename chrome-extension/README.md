# FlowCast Recorder — Chrome Extension

Record your screen and upload to FlowCast to generate n8n workflows.

## Features

- Record the current browser tab
- Upload recordings directly to your FlowCast instance
- Authenticate via API key (generated in FlowCast → API Keys)

## Installation (Development)

1. Open Chrome and navigate to `chrome://extensions/`
2. Enable **Developer mode** (toggle in the top-right corner)
3. Click **Load unpacked**
4. Select the `chrome-extension/` directory from this repository

## Configuration

1. Click the FlowCast Recorder extension icon in the Chrome toolbar
2. Enter your **API Key** (starts with `fc_`) — generate one in FlowCast under Settings → API Keys
3. Enter your **Server URL** (e.g. `http://localhost:8000` for local development or `https://app.flowcast.io` for production)
4. Click **Save & Connect**

The extension will verify the connection and display your organization name when connected.

## Usage

1. Navigate to the tab you want to record
2. Click the extension icon and press **Start Recording**
3. Perform your actions in the browser
4. Click the extension icon and press **Stop Recording**
5. The recording is automatically uploaded to FlowCast
6. Click **Open in FlowCast** to view the recording and generate workflows

## Icons

Place your icon files in the `icons/` directory:

- `icon16.png` — 16×16px (toolbar)
- `icon48.png` — 48×48px (extensions page)
- `icon128.png` — 128×128px (Chrome Web Store)

## Development

- Plain JavaScript, no build step required
- Manifest V3 format
- Uses `chrome.tabCapture` for recording
- Uses `chrome.storage.local` for settings persistence
- Background service worker (`background.js`) handles recording state and uploads
- Popup (`popup.html` / `popup.js`) handles UI and user interaction
