<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat Ilovasi</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.0.0/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gradient-to-r from-purple-600 to-pink-500 font-sans flex h-screen">

<audio id="notificationSound" preload="auto" src="{{ asset('assets/notification_o14egLP.mp3') }}"></audio>

<div class="flex flex-row w-full h-full">
    <!-- Foydalanuvchilar ro'yxati -->
    <div class="w-1/4 bg-white shadow-lg rounded-lg mx-4 my-4 p-5 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-800 mb-6">Foydalanuvchilar</h2>
        <div id="users" class="space-y-4 overflow-y-auto">
            @foreach($users as $user)
                <a href="javascript:void(0);" onclick="selectUser({{ $user->id }})"
                   class="block px-5 py-3 rounded-lg text-lg text-gray-800 hover:bg-indigo-500 hover:text-white transition-all
                    {{ isset($selectedUser) && $selectedUser->id == $user->id ? 'bg-indigo-500 text-white' : '' }}">
                    {{ $user->name }}
                </a>
            @endforeach
        </div>
    </div>

    <!-- Xabarlar qismi -->
    <div class="w-3/4 bg-gray-50 rounded-lg shadow-xl mx-4 my-4 p-6 flex flex-col">
        <h2 class="text-2xl font-semibold text-gray-700 mb-5">
            Xabarlar
            @if(isset($selectedUser))
                - {{ $selectedUser->name }} bilan
            @endif
        </h2>

        <div id="messages" class="flex-grow overflow-y-auto bg-white p-4 border-2 border-gray-300 rounded-lg shadow-md space-y-4">
        </div>

        @if(isset($selectedUser))
            <form id="messageForm" class="mt-6 flex gap-4" onsubmit="sendMessage(event)">
                <input type="hidden" name="receiver_id" id="receiver_id" value="{{ $selectedUser->id }}">
                <input type="text" name="message" id="message" placeholder="Xabar yozing"
                       class="w-full p-4 text-lg rounded-lg border-2 border-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                       required>
                <button type="submit"
                        class="px-6 py-3 bg-indigo-600 text-white text-lg rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    Yuborish
                </button>
            </form>
        @endif
    </div>
</div>

<script>
    let lastMessageCount = 0;
    const notificationSound = document.getElementById('notificationSound');
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

    function playNotificationSound() {
        notificationSound.play().catch(error => {
            console.error('Audio playback error:', error);
        });
    }

    function sendMessage(event) {
        event.preventDefault();
        const messageInput = document.getElementById('message');
        const receiverId = document.getElementById('receiver_id').value;
        const message = messageInput.value;

        fetch('{{ route('chat.send') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                message: message,
                receiver_id: receiverId
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data) {
                    messageInput.value = '';
                    loadMessages();
                }
            })
            .catch(error => console.error('Xato:', error));
    }

    function loadMessages() {
        const receiverId = document.getElementById('receiver_id').value;
        fetch(`{{ url('/chat/messages') }}/${receiverId}`)
            .then(response => response.json())
            .then(data => {
                const messagesContainer = document.getElementById('messages');
                const currentMessageCount = data.messages.length;

                if (lastMessageCount > 0 && currentMessageCount > lastMessageCount) {
                    playNotificationSound();
                }

                lastMessageCount = currentMessageCount;
                messagesContainer.innerHTML = '';

                data.messages.forEach(message => {
                    const messageElement = document.createElement('div');
                    messageElement.className = `message p-4 rounded-lg max-w-md ${
                        message.sender_id === {{ auth()->id() }}
                            ? 'bg-indigo-600 text-white ml-auto'
                            : 'bg-gray-200 text-gray-800'
                    }`;
                    messageElement.innerText = message.message;
                    messagesContainer.appendChild(messageElement);
                });

                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            })
            .catch(error => console.error('Xato:', error));
    }

    function selectUser(userId) {
        window.location.href = `{{ url('/chat') }}/${userId}`;
    }

    if (document.getElementById('receiver_id')) {
        loadMessages();
        setInterval(loadMessages, 1000);
    }

    if (Notification.permission === "default") {
        Notification.requestPermission().then(permission => {
            if (permission === "granted") {
                console.log("Bildirishnomalar yoqildi");
            }
        });
    }
</script>

</body>
</html>
