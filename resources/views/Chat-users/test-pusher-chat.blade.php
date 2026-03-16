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
    const conversationId = 19;

    document.getElementById('conv-id').textContent = conversationId;

    Pusher.logToConsole = true;

    const pusher = new Pusher(APP_KEY, {
        cluster: CLUSTER,
        authEndpoint: "https://api.dev-hubs.tech/api/broadcasting/auth",
        auth: {
            headers: {
                Authorization: "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vYXBpLmRldi1odWJzLnRlY2gvYXBpL3YxL3JlZ2lzdGVyIiwiaWF0IjoxNzczNTg0MzE3LCJleHAiOjE3ODEzNjAzMTcsIm5iZiI6MTc3MzU4NDMxNywianRpIjoiVEVDckRYTFBJeTNOMXdNTyIsInN1YiI6IjQ2NyIsInBydiI6IjIzYmQ1Yzg5NDlmNjAwYWRiMzllNzAxYzQwMDg3MmRiN2E1OTc2ZjcifQ.uEI0fZe-5cFYx1C5YNuV9eih6BtjsZwkZsJfARYteKo"
            }
        }
    });

    function updateStatus(message, type = 'info') {
        const statusDiv = document.getElementById('status');
        statusDiv.textContent = message;
        statusDiv.className = 'status ' + type;
    }

    function formatReactions(reactions) {
        if (!reactions) {
            return '';
        }

        const tokens = [];

        if (Array.isArray(reactions)) {
            reactions.forEach((item) => {
                if (typeof item === 'string') {
                    tokens.push(item);
                    return;
                }

                if (item && typeof item === 'object') {
                    const name = item.reaction || item.type || item.name;
                    const count = typeof item.count === 'number'
                        ? item.count
                        : (Array.isArray(item.users) ? item.users.length : null);

                    if (name) {
                        tokens.push(count ? `${name} (${count})` : `${name}`);
                    }
                }
            });
        } else if (typeof reactions === 'object') {
            Object.entries(reactions).forEach(([name, value]) => {
                if (typeof value === 'number') {
                    tokens.push(`${name} (${value})`);
                } else if (Array.isArray(value)) {
                    tokens.push(`${name} (${value.length})`);
                } else if (value && typeof value === 'object' && typeof value.count === 'number') {
                    tokens.push(`${name} (${value.count})`);
                } else {
                    tokens.push(`${name}`);
                }
            });
        }

        return tokens.join(' | ');
    }

    function upsertReactionLine(messageId, reactions) {
        const existing = document.getElementById("msg-" + messageId);

        if (!existing) {
            return;
        }

        let reactionLine = document.getElementById("msg-reactions-" + messageId);
        if (!reactionLine) {
            reactionLine = document.createElement('div');
            reactionLine.id = "msg-reactions-" + messageId;
            reactionLine.style.marginTop = '6px';
            reactionLine.style.fontSize = '13px';
            reactionLine.style.color = '#555';
            existing.appendChild(reactionLine);
        }

        const formatted = formatReactions(reactions);
        reactionLine.textContent = formatted ? `Reactions: ${formatted}` : 'No reactions';
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
            <div id="msg-reactions-${data.message.id}" style="margin-top:6px;font-size:13px;color:#555;">No reactions</div>
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
                <div id="msg-reactions-${parsedData.id}" style="margin-top:6px;font-size:13px;color:#555;">No reactions</div>
            `;
        } else {
            const msg = document.createElement("div");
            msg.id = "msg-" + parsedData.id;
            msg.innerHTML = `
                <strong>(edited):</strong> ${parsedData.body}
                <div id="msg-reactions-${parsedData.id}" style="margin-top:6px;font-size:13px;color:#555;">No reactions</div>
            `;
            messageBox.appendChild(msg);
            messageBox.scrollTop = messageBox.scrollHeight;
        }
    });

    channel.bind("message.reaction.updated", function (data) {
        console.log("message.reaction.updated event received:", data);

        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        console.log("Parsed reaction data:", parsedData);

        upsertReactionLine(parsedData.message_id, parsedData.reactions);
        updateStatus(
            `Reaction ${parsedData.action} on message #${parsedData.message_id}`,
            'success'
        );
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
        "MessageWasSent, message.updated, message.deleted, message.reaction.updated");
    console.log("Channel:", "private-mc-chat-conversation." + conversationId);
</script>

</body>
</html>
