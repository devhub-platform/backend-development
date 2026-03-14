<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Realtime Chat Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        #messages {
            border: 1px solid #ccc;
            padding: 10px;
            height: 300px;
            overflow: auto;
            background: #f9f9f9;
        }

        #messages div {
            padding: 8px;
            margin: 4px 0;
            background: white;
            border-radius: 4px;
        }

        .status {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }

        .status.success {
            background: #d4edda;
            color: #155724;
        }

        .status.error {
            background: #f8d7da;
            color: #721c24;
        }

        .status.info {
            background: #d1ecf1;
            color: #0c5460;
        }
    </style>
</head>
<body>

<h2>Realtime Chat Test - Conversation #<span id="conv-id"></span></h2>
<div id="status" class="status info">Connecting to Pusher...</div>


<h3>Messages:</h3>
<div id="messages"></div>

<script>
    const APP_KEY = "8386ec29a087993e4c57";
    const CLUSTER = "mt1";
    const conversationId = 5;

    document.getElementById('conv-id').textContent = conversationId;

    Pusher.logToConsole = true;

    const pusher = new Pusher(APP_KEY, {
        cluster: CLUSTER,
        authEndpoint: "https://api.dev-hubs.tech/api/broadcasting/auth",
        auth: {
            headers: {
                Authorization: "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vZGV2aHViLmV1LW5vcnRoLTEuZWxhc3RpY2JlYW5zdGFsay5jb20vYXBpL3YxL2F1dGgvZ29vZ2xlL2NhbGxiYWNrIiwiaWF0IjoxNzcyOTAyMjI1LCJleHAiOjE3NzM1MDcwMjUsIm5iZiI6MTc3MjkwMjIyNSwianRpIjoiYXdlOEFFZU96dTE1UmZMeiIsInN1YiI6IjQwNiIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.z4oXaEctfDfMIqhwhbYq_8so4W8WAZAAWLN2xvY_Sbs"
            }
        }
    });

    function updateStatus(message, type = 'info') {
        const statusDiv = document.getElementById('status');
        statusDiv.textContent = message;
        statusDiv.className = 'status ' + type;
    }


    function handleMessageDelete(parsedData) {
        console.log("Handling message deletion:", parsedData);

        const existing = document.getElementById("msg-" + parsedData.id);
        console.log("Found element:", existing);

        if (existing) {
            existing.style.transition = "opacity 0.3s";
            existing.style.opacity = "0";
            setTimeout(() => {
                existing.remove();
                console.log("Element removed:", parsedData.id);
                updateStatus('Message #' + parsedData.id + ' deleted successfully', 'success');
            }, 300);
        } else {
            console.log("Element not found for message ID:", parsedData.id);
            updateStatus('Message #' + parsedData.id + ' not found in DOM. Add it first!', 'error');
        }
    }

    const channel = pusher.subscribe(
        "private-mc-chat-conversation." + conversationId
    );

    channel.bind("Musonza\\Chat\\Eventing\\MessageWasSent", function (data) {
        const messageBox = document.getElementById("messages");

        const msg = document.createElement("div");
        msg.id = "msg-" + data.message.id;
        msg.innerHTML = `
            <strong>${data.message.sender.name ?? "User"}:</strong>
            ${data.message.body}
        `;

        messageBox.appendChild(msg);
        messageBox.scrollTop = messageBox.scrollHeight;
    });

    channel.bind("message.updated", function (data) {
        console.log("message.updated event received:", data);

        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        console.log("Parsed data:", parsedData);

        const messageBox = document.getElementById("messages");
        const existing = document.getElementById("msg-" + parsedData.id);

        if (existing) {
            existing.innerHTML = `
                <strong>(edited):</strong>
                ${parsedData.body}
            `;
        } else {
            const msg = document.createElement("div");
            msg.id = "msg-" + parsedData.id;
            msg.innerHTML = `<strong>(edited):</strong> ${parsedData.body}`;
            messageBox.appendChild(msg);
            messageBox.scrollTop = messageBox.scrollHeight;
        }
    });

    channel.bind("message.deleted", function (data) {
        console.log("message.deleted event received from Pusher:", data);
        console.log("Data type:", typeof data);

        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        console.log("Parsed data:", parsedData);

        handleMessageDelete(parsedData);
    });

    channel.bind("pusher:subscription_succeeded", function () {
        console.log("Subscribed successfully to channel: private-mc-chat-conversation." + conversationId);
        console.log("All event bindings are active");
        updateStatus('Connected! Listening for events on conversation #' + conversationId, 'success');
    });

    channel.bind("pusher:subscription_error", function (error) {
        console.error("Subscription error", error);
        updateStatus('Subscription error: ' + JSON.stringify(error), 'error');
    });

    // Global listener to catch ALL events
    channel.bind_global(function (eventName, data) {
        console.log("Global event captured - Event:", eventName, "Data:", data);
    });

    console.log("Event bindings registered for:",
        "MessageWasSent, message.updated, message.deleted");
    console.log("Channel:", "private-mc-chat-conversation." + conversationId);
</script>

</body>
</html>
