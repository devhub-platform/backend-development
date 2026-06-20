<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Realtime Chat Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen py-8 px-4 text-slate-900">

<main class="mx-auto max-w-3xl rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="text-xl font-semibold">Realtime Chat Test - Conversation #<span id="conv-id"></span></h2>
    <div id="status" class="mt-3 rounded-lg border px-3 py-2 text-sm font-medium bg-sky-50 text-sky-700 border-sky-200">
        Connecting to Pusher...
    </div>
    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm">
        <div class="flex items-center justify-between gap-2">
            <div class="flex items-center gap-2">
                <span id="presence-dot" class="inline-block h-2.5 w-2.5 rounded-full bg-slate-400"></span>
                <span id="presence-label" class="font-medium text-slate-700">User status: unknown</span>
            </div>
            <span id="last-seen" class="text-xs text-slate-500">Last seen: --</span>
        </div>
    </div>

    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Messages</h3>
    <div id="messages"
         class="mt-2 h-80 overflow-y-auto rounded-xl border border-slate-200 bg-slate-50 p-3 flex flex-col gap-2"></div>

    <div class="mt-3 space-y-2">
        <div class="flex items-center gap-2">
            <label for="attachment-input"
                   class="cursor-pointer rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Attach
                file</label>
            <input id="attachment-input" type="file" class="hidden">
            <span id="attachment-name" class="text-xs text-slate-500">No file selected</span>
            <button id="clear-attachment" type="button"
                    class="hidden rounded-md px-2 py-1 text-xs font-medium text-rose-600 hover:bg-rose-50">Clear
            </button>
        </div>
        <div class="flex items-end gap-2">
            <textarea id="message-input"
                      class="min-h-12 max-h-32 flex-1 resize-y rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none ring-0 focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                      placeholder="Type your message... (Enter to send, Shift+Enter for new line)"></textarea>
            <button id="send-button" type="button"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60">
                Send
            </button>
        </div>
    </div>
</main>

<script>
    const APP_KEY = "8386ec29a087993e4c57";
    const CLUSTER = "mt1";
    const conversationId = Number(new URLSearchParams(window.location.search).get('conversation_id') || 20);
    const currentUserId = Number(new URLSearchParams(window.location.search).get('viewer_id') || '{{ auth()->id() ?? 0 }}');
    const peerIdFromQuery = Number(new URLSearchParams(window.location.search).get('peer_id') || 0);
    const AUTH_TOKEN = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwOi8vZGV2LWh1YnMudGVjaC9hcGkvdjEvYXV0aC9naXRodWIvY2FsbGJhY2siLCJpYXQiOjE3ODA5NDIxMjcsImV4cCI6MTgxMjA0NjEyNywibmJmIjoxNzgwOTQyMTI3LCJqdGkiOiJoNEUzTWQ1WTE2Wk5VcWlHIiwic3ViIjoiMTQzIiwicHJ2IjoiMjNiZDVjODk0OWY2MDBhZGIzOWU3MDFjNDAwODcyZGI3YTU5NzZmNyJ9.eVxbtgrQCNGBgHiiGuUhbAIrBIhCNYT2MHLmQ-iXX0M";
    const API_BASE_URL = "https://dev-hubs.tech/api/v1";
    const STATUS_BASE_CLASS = 'mt-3 rounded-lg border px-3 py-2 text-sm font-medium';
    const STATUS_VARIANTS = {
        info: 'bg-sky-50 text-sky-700 border-sky-200',
        success: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        error: 'bg-rose-50 text-rose-700 border-rose-200'
    };
    const onlineMemberIds = new Set();
    let peerUserId = peerIdFromQuery;
    let peerLastSeenAt = null;

    document.getElementById('conv-id').textContent = conversationId;

    Pusher.logToConsole = true;

    const pusher = new Pusher(APP_KEY, {
        cluster: CLUSTER,
        authEndpoint: "https://dev-hubs.tech/api/broadcasting/auth",
        auth: {
            headers: {
                Authorization: "Bearer " + AUTH_TOKEN
            }
        }
    });

    function getMessageRowClasses(isSent) {
        const base = 'max-w-[75%] rounded-xl px-3 py-2 shadow-sm border';
        return isSent
            ? `${base} ml-auto bg-blue-100 border-blue-200`
            : `${base} mr-auto bg-white border-slate-200`;
    }

    function getReactionClasses() {
        return 'mt-1 text-xs text-slate-500';
    }

    function updateStatus(message, type = 'info') {
        const statusDiv = document.getElementById('status');
        statusDiv.textContent = message;
        statusDiv.className = STATUS_BASE_CLASS + ' ' + (STATUS_VARIANTS[type] || STATUS_VARIANTS.info);
    }

    function formatLastSeen(value) {
        if (!value) {
            return '--';
        }

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) {
            return '--';
        }

        return date.toLocaleString();
    }

    function updatePresenceUi(isOnline, lastSeenAt = null) {
        const dot = document.getElementById('presence-dot');
        const label = document.getElementById('presence-label');
        const lastSeen = document.getElementById('last-seen');

        if (isOnline) {
            dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-emerald-500';
            label.textContent = 'User status: online';
        } else {
            dot.className = 'inline-block h-2.5 w-2.5 rounded-full bg-slate-400';
            label.textContent = 'User status: offline';
        }

        if (lastSeenAt) {
            peerLastSeenAt = lastSeenAt;
        }

        lastSeen.textContent = `Last seen: ${formatLastSeen(peerLastSeenAt)}`;
    }

    function normalizeMemberId(id) {
        const parsed = Number(id);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    async function resolvePeerUserId() {
        if (peerUserId) {
            return peerUserId;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/chat/conversations/${conversationId}`, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${AUTH_TOKEN}`
                }
            });

            if (!response.ok) {
                return 0;
            }

            const payload = await response.json();
            const participants = Array.isArray(payload?.participants) ? payload.participants : [];
            const candidate = participants.find((participant) => {
                const id = Number(participant?.messageable_id || participant?.id || participant?.messageable?.id || 0);
                return id && id !== currentUserId;
            });

            peerUserId = Number(candidate?.messageable_id || candidate?.id || candidate?.messageable?.id || 0);
            return peerUserId;
        } catch (error) {
            console.error('resolvePeerUserId error:', error);
            return 0;
        }
    }

    async function fetchPeerPresence() {
        const resolvedPeerId = await resolvePeerUserId();
        if (!resolvedPeerId) {
            updatePresenceUi(false, null);
            return;
        }

        try {
            const response = await fetch(`${API_BASE_URL}/chat/presence/users/${resolvedPeerId}`, {
                headers: {
                    Accept: 'application/json',
                    Authorization: `Bearer ${AUTH_TOKEN}`
                }
            });

            if (!response.ok) {
                return;
            }

            const payload = await response.json();
            const data = payload?.data || {};
            updatePresenceUi(Boolean(data.is_online), data.last_seen_at || null);
        } catch (error) {
            console.error('fetchPeerPresence error:', error);
        }
    }

    function syncPresenceFromMembers() {
        if (!peerUserId) {
            return;
        }

        const online = onlineMemberIds.has(peerUserId);
        updatePresenceUi(online, online ? new Date().toISOString() : peerLastSeenAt);
    }

    function escapeHtml(text) {
        if (text === null || text === undefined) {
            return '';
        }

        return String(text)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function resolveSenderId(message) {
        return Number(
            message.sender_id
            || message.user_id
            || (message.sender && message.sender.id)
            || 0
        );
    }

    function resolveSenderName(message, fallback = 'User') {
        return message.sender_name
            || message.sender?.name
            || message.user?.name
            || fallback;
    }

    function renderMessage(message, options = {}) {
        const {isEdited = false} = options;
        const messageBox = document.getElementById("messages");
        const messageId = message.id;

        if (!messageId) {
            return;
        }

        let row = document.getElementById("msg-" + messageId);
        const senderId = resolveSenderId(message);
        const senderName = resolveSenderName(message, row?.dataset.senderName || 'User');
        const isSent = currentUserId !== 0 && senderId === currentUserId;

        const previousReactionText = document.getElementById("msg-reactions-" + messageId)?.textContent || 'No reactions';

        if (!row) {
            row = document.createElement("div");
            row.id = "msg-" + messageId;
            messageBox.appendChild(row);
        }

        const messageType = message.type || 'text';
        const attachmentName = message.data?.file_name || message.data?.filename || 'Attachment';
        const attachmentUrl = message.data?.file_url || message.data?.url || '';
        const bodyHtml = messageType === 'attachment'
            ? `<a href="${escapeHtml(attachmentUrl)}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 text-sm font-medium text-blue-700 underline">📎 ${escapeHtml(attachmentName)}</a>`
            : `<div class="text-sm text-slate-800 whitespace-pre-wrap break-words">${escapeHtml(message.body || '')}</div>`;

        row.dataset.senderId = String(senderId);
        row.dataset.senderName = senderName;
        row.className = getMessageRowClasses(isSent);
        row.innerHTML = `
            <div class="mb-1 text-xs text-slate-500"><strong>${escapeHtml(senderName)}</strong>${isEdited ? ' (edited)' : ''}</div>
            ${bodyHtml}
            <div id="msg-reactions-${messageId}" class="${getReactionClasses()}">${escapeHtml(previousReactionText)}</div>
        `;

        messageBox.scrollTop = messageBox.scrollHeight;
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
            reactionLine.className = getReactionClasses();
            existing.appendChild(reactionLine);
        }

        const formatted = formatReactions(reactions);
        reactionLine.textContent = formatted ? `Reactions: ${formatted}` : 'No reactions';
    }

    async function sendMessage() {
        const input = document.getElementById('message-input');
        const button = document.getElementById('send-button');
        const fileInput = document.getElementById('attachment-input');
        const text = input.value.trim();
        const file = fileInput.files[0] || null;

        if (!text && !file) {
            return;
        }

        button.disabled = true;
        button.textContent = 'Sending...';
        updateStatus('Sending message...', 'info');

        try {
            let response;
            if (file) {
                const formData = new FormData();
                formData.append('file', file);
                formData.append('file_name', file.name);
                if (text) {
                    formData.append('message', text);
                }

                response = await fetch(`${API_BASE_URL}/messages/conversation/${conversationId}/send-attachment`, {
                    method: 'POST',
                    headers: {
                        Authorization: `Bearer ${AUTH_TOKEN}`
                    },
                    body: formData
                });
            } else {
                response = await fetch(`${API_BASE_URL}/chat/conversations/${conversationId}/messages`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Authorization: `Bearer ${AUTH_TOKEN}`
                    },
                    body: JSON.stringify({
                        message: text,
                        type: 'text'
                    })
                });
            }

            const payload = await response.json();

            if (!response.ok) {
                updateStatus('Send failed: ' + (payload?.message || payload?.error || 'Unknown error'), 'error');
                return;
            }

            if (payload?.data?.id) {
                renderMessage(payload.data);
            }
            if (payload?.data?.attachment?.id) {
                renderMessage(payload.data.attachment);
            }
            if (payload?.data?.text?.id) {
                renderMessage(payload.data.text);
            }

            input.value = '';
            clearAttachment();
            updateStatus('Message sent successfully.', 'success');
        } catch (error) {
            console.error('sendMessage error:', error);
            updateStatus('Send failed: ' + (error.message || 'Unknown error'), 'error');
        } finally {
            button.disabled = false;
            button.textContent = 'Send';
            input.focus();
        }
    }

    function clearAttachment() {
        const fileInput = document.getElementById('attachment-input');
        const attachmentName = document.getElementById('attachment-name');
        const clearButton = document.getElementById('clear-attachment');

        fileInput.value = '';
        attachmentName.textContent = 'No file selected';
        clearButton.classList.add('hidden');
    }

    function updateAttachmentPreview() {
        const fileInput = document.getElementById('attachment-input');
        const attachmentName = document.getElementById('attachment-name');
        const clearButton = document.getElementById('clear-attachment');
        const file = fileInput.files[0];

        if (!file) {
            clearAttachment();
            return;
        }

        attachmentName.textContent = `${file.name} (${Math.ceil(file.size / 1024)} KB)`;
        clearButton.classList.remove('hidden');
    }

    function handleMessageDelete(parsedData) {
        const existing = document.getElementById("msg-" + parsedData.id);

        if (existing) {
            existing.style.transition = "opacity 0.3s";
            existing.style.opacity = "0";
            setTimeout(() => {
                existing.remove();
                updateStatus('Message #' + parsedData.id + ' deleted successfully', 'success');
            }, 300);
        } else {
            updateStatus('Message #' + parsedData.id + ' not found in DOM. Add it first!', 'error');
        }
    }

    async function sendHeartbeat() {
        try {
            await fetch(`${API_BASE_URL}/chat/presence/online`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Authorization: `Bearer ${AUTH_TOKEN}`
                }
            });
        } catch (error) {
            console.error('Heartbeat failed:', error);
        }
    }

    // Send heartbeat every 4 minutes to keep user online
    setInterval(sendHeartbeat, 4 * 60 * 1000);
    sendHeartbeat(); // Initial heartbeat on load

    // Mark user offline when leaving page
    window.addEventListener('beforeunload', async () => {
        await fetch(`${API_BASE_URL}/chat/presence/offline`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                Authorization: `Bearer ${AUTH_TOKEN}`
            },
            keepalive: true
        });
    });

    const channel = pusher.subscribe("private-mc-chat-conversation." + conversationId);
    const presenceChannel = pusher.subscribe("presence-mc-chat-presence." + conversationId);
    const statusChannel = pusher.subscribe('chat.user-status');

    document.getElementById('send-button').addEventListener('click', sendMessage);
    document.getElementById('attachment-input').addEventListener('change', updateAttachmentPreview);
    document.getElementById('clear-attachment').addEventListener('click', clearAttachment);
    document.getElementById('message-input').addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendMessage();
        }
    });

    channel.bind("Musonza\\Chat\\Eventing\\MessageWasSent", function (data) {
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        const message = parsedData.message || parsedData;
        renderMessage(message);
    });

    channel.bind("message.updated", function (data) {
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;

        const existing = document.getElementById("msg-" + parsedData.id);
        const normalizedMessage = {
            id: parsedData.id,
            body: parsedData.body,
            sender_id: parsedData.sender_id || existing?.dataset.senderId,
            sender_name: parsedData.sender_name || existing?.dataset.senderName || 'User'
        };

        renderMessage(normalizedMessage, {isEdited: true});
    });

    channel.bind("message.reaction.updated", function (data) {
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;

        upsertReactionLine(parsedData.message_id, parsedData.reactions);
        updateStatus(`Reaction ${parsedData.action} on message #${parsedData.message_id}`, 'success');
    });

    channel.bind("message.deleted", function (data) {
        const parsedData = typeof data === 'string' ? JSON.parse(data) : data;
        handleMessageDelete(parsedData);
    });

    channel.bind("pusher:subscription_succeeded", function () {
        updateStatus('Connected! Listening for events on conversation #' + conversationId, 'success');
    });

    presenceChannel.bind('pusher:subscription_succeeded', async function (members) {
        onlineMemberIds.clear();

        if (members && typeof members.each === 'function') {
            members.each(function (member) {
                onlineMemberIds.add(normalizeMemberId(member.id));
            });
        }

        await resolvePeerUserId();
        syncPresenceFromMembers();
        fetchPeerPresence();
    });

    presenceChannel.bind('pusher:member_added', function (member) {
        onlineMemberIds.add(normalizeMemberId(member.id));
        syncPresenceFromMembers();
    });

    presenceChannel.bind('pusher:member_removed', function (member) {
        onlineMemberIds.delete(normalizeMemberId(member.id));
        syncPresenceFromMembers();
        fetchPeerPresence();
    });

    statusChannel.bind('user.online', function (data) {
        if (!peerUserId || Number(data?.id) !== peerUserId) {
            return;
        }

        updatePresenceUi(true, data?.last_seen_at || new Date().toISOString());
    });

    statusChannel.bind('user.offline', function (data) {
        if (!peerUserId || Number(data?.id) !== peerUserId) {
            return;
        }

        updatePresenceUi(false, data?.last_seen_at || peerLastSeenAt);
    });

    channel.bind("pusher:subscription_error", function (error) {
        updateStatus('Subscription error: ' + JSON.stringify(error), 'error');
    });

    channel.bind_global(function (eventName, data) {
        console.log("Global event captured - Event:", eventName, "Data:", data);
    });

    fetchPeerPresence();
</script>

</body>
</html>
