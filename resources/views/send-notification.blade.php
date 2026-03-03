<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Send Test Notification - DevHub</title>
    <script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            max-width: 500px;
            width: 100%;
            padding: 40px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
        }

        .header h1 {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .emoji {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .player-id-box {
            background: #f7fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 30px;
            border-left: 4px solid #48bb78;
        }

        .player-id-box h3 {
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .player-id {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #4a5568;
            word-break: break-all;
            background: white;
            padding: 12px;
            border-radius: 8px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #2d3748;
            font-weight: 600;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid #ffffff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .alert {
            padding: 16px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: none;
            animation: slideIn 0.3s ease;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #f0fdf4;
            border-left: 4px solid #48bb78;
            color: #22543d;
        }

        .alert-error {
            background: #fef2f2;
            border-left: 4px solid #f56565;
            color: #742a2a;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-title {
            font-weight: 600;
            margin-bottom: 4px;
        }

        .tips {
            background: #edf2f7;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
        }

        .tips h4 {
            color: #2d3748;
            font-size: 14px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .tips ul {
            list-style: none;
            padding: 0;
        }

        .tips li {
            color: #4a5568;
            font-size: 13px;
            line-height: 1.6;
            padding-left: 20px;
            position: relative;
            margin-bottom: 8px;
        }

        .tips li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #48bb78;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="emoji">🔔</div>
            <h1>Send Test Notification</h1>
        </div>

        <div class="player-id-box">
            <h3>🎯 Target Player ID</h3>
            <div class="player-id">603a5a55-b4c3-4ae1-a3de-706cb88c72f2</div>
        </div>

        <div id="successAlert" class="alert alert-success">
            <div class="alert-title">✓ Notification Sent!</div>
            <div id="successMessage"></div>
        </div>

        <div id="errorAlert" class="alert alert-error">
            <div class="alert-title">✗ Failed to Send</div>
            <div id="errorMessage"></div>
        </div>

        <form id="notificationForm">
            <div class="form-group">
                <label>📝 Title (Heading)</label>
                <input
                    type="text"
                    id="heading"
                    placeholder="e.g., New Message"
                    value="Test Notification"
                    required
                >
            </div>

            <div class="form-group">
                <label>💬 Message</label>
                <textarea
                    id="message"
                    placeholder="e.g., Hello from DevHub!"
                    required
                >Hello from DevHub! 🚀 This is a test notification.</textarea>
            </div>

            <div class="form-group">
                <label>🔗 URL (Optional)</label>
                <input
                    type="url"
                    id="url"
                    placeholder="https://devhub.test/notifications"
                    value="https://devhub.test"
                >
            </div>

            <button type="submit" class="btn btn-primary" id="sendBtn">
                <span>🚀</span>
                <span>Send Notification Now</span>
            </button>
        </form>

        <div class="tips">
            <h4>💡 Tips</h4>
            <ul>
                <li>Make sure your browser has notifications enabled</li>
                <li>Keep this browser tab open</li>
                <li>The notification will appear in a few seconds</li>
                <li>Click the notification to test the URL link</li>
            </ul>
        </div>
    </div>

    <script>
        const PLAYER_ID = '603a5a55-b4c3-4ae1-a3de-706cb88c72f2';
        const API_ENDPOINT = '{{ url("/api/send-test-notification") }}';

        const form = document.getElementById('notificationForm');
        const sendBtn = document.getElementById('sendBtn');
        const successAlert = document.getElementById('successAlert');
        const errorAlert = document.getElementById('errorAlert');
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Hide previous alerts
            successAlert.classList.remove('show');
            errorAlert.classList.remove('show');

            // Disable button and show loading
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<span class="loading"></span><span>Sending...</span>';

            // Get form values
            const heading = document.getElementById('heading').value;
            const message = document.getElementById('message').value;
            const url = document.getElementById('url').value;

            const payload = {
                player_id: PLAYER_ID,
                heading: heading,
                message: message,
                url: url || null
            };

            console.log('Sending payload:', payload);
            console.log('To endpoint:', API_ENDPOINT);

            try {
                const response = await fetch(API_ENDPOINT, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                });

                console.log('Response status:', response.status);
                const data = await response.json();
                console.log('Response data:', data);

                if (response.ok && data.success) {
                    // Show success message
                    successMessage.textContent = `Notification ID: ${data.notification_id} • Recipients: ${data.recipients}`;
                    successAlert.classList.add('show');

                    // Scroll to top to see alert
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                } else {
                    // Handle error response
                    const errorText = data.message || data.error || JSON.stringify(data.errors || data);
                    throw new Error(errorText);
                }

            } catch (error) {
                console.error('Error:', error);
                errorMessage.textContent = error.message;
                errorAlert.classList.add('show');
                window.scrollTo({ top: 0, behavior: 'smooth' });
            } finally {
                // Re-enable button
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<span>🚀</span><span>Send Notification Now</span>';
            }
        });

        // Check notification permission on load
        window.addEventListener('load', async () => {
            if ('Notification' in window) {
                const permission = Notification.permission;
                if (permission === 'denied') {
                    errorMessage.textContent = 'Notifications are blocked. Please enable them in your browser settings.';
                    errorAlert.classList.add('show');
                } else if (permission === 'default') {
                    console.log('Notification permission not requested yet');
                }
            }
        });
    </script>
</body>
</html>

