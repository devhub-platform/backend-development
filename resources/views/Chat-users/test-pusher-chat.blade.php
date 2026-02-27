<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Realtime Chat Test</title>
    <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
</head>
<body>

<h2>Realtime Messages</h2>
<div id="messages" style="border:1px solid #ccc;padding:10px;height:300px;overflow:auto;"></div>

<script>
    const APP_KEY = "8386ec29a087993e4c57";
    const CLUSTER = "mt1";
    const conversationId = 11;

    Pusher.logToConsole = true;

    const pusher = new Pusher(APP_KEY, {
        cluster: CLUSTER,
        authEndpoint: "http://devhub.eu-north-1.elasticbeanstalk.com/api/broadcasting/auth",
        auth: {
            headers: {
                Authorization: "Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2Rldmh1Yi50ZXN0L2FwaS92MS9sb2dpbiIsImlhdCI6MTc3MjEwNzU1MCwiZXhwIjoxNzc0Njk5NTUwLCJuYmYiOjE3NzIxMDc1NTAsImp0aSI6ImJKQ0dkcU5nTlljQnNaeVgiLCJzdWIiOiIyMTMiLCJwcnYiOiIyM2JkNWM4OTQ5ZjYwMGFkYjM5ZTcwMWM0MDA4NzJkYjdhNTk3NmY3In0.BFDMJTUuu1vdjFB6FW0SMlnuY7nb8Bd7BsiIUtEskVE"
            }
        }
    });

    const channel = pusher.subscribe(
        "private-mc-chat-conversation." + conversationId
    );

    channel.bind("Musonza\\Chat\\Eventing\\MessageWasSent", function (data) {
        const messageBox = document.getElementById("messages");

        const msg = document.createElement("div");
        msg.innerHTML = `
            <strong>${data.message.sender.name ?? "User"}:</strong>
            ${data.message.body}
        `;

        messageBox.appendChild(msg);
        messageBox.scrollTop = messageBox.scrollHeight;
    });

    channel.bind("pusher:subscription_succeeded", function () {
        console.log("Subscribed successfully");
    });

    channel.bind("pusher:subscription_error", function (error) {
        console.error("Subscription error", error);
    });
</script>

</body>
</html>
