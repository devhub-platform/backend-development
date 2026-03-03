<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pusher Realtime Message Test</title>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        #messages {
            margin-top: 20px;
        }

        .message {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }

        #status {
            color: #888;
            margin-bottom: 10px;
        }

        #sendBtn {
            margin-left: 10px;
        }
    </style>
</head>
<body>
<h2>Pusher Realtime Message Test</h2>
<div id="status">Connecting...</div>
<div id="messages"></div>

<script>
    Pusher.logToConsole = true;
    var pusher = new Pusher('8386ec29a087993e4c57', {
        cluster: 'mt1',
        forceTLS: true
    });

    var channel = pusher.subscribe('my-channel');
    var status = document.getElementById('status');
    var messages = document.getElementById('messages');

    pusher.connection.bind('state_change', function (states) {
        status.textContent = 'Connection status: ' + states.current;
    });

    channel.bind('my-event', function (data) {
        var msg = document.createElement('div');
        msg.className = 'message';
        msg.textContent = data.message;
        messages.appendChild(msg);
    });
</script>
</body>
</html>

