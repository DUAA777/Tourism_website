@extends('layout.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot.css') }}">
@endpush

@section('content')
<section class="chatbot-page">
    <div class="section__container">
        <div class="chatbot-shell">
            <div class="chatbot-intro">
                <p class="chatbot-intro__eyebrow">AI Travel Planner</p>
                <h1>Welcome to Yalla Nemshi Assistant</h1>
                <p>
                    Plan your Lebanon trip with smart suggestions for hotels, restaurants,
                    destinations, and day ideas — all in one place.
                </p>
            </div>

            <div class="chatbot-actions">
                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Plan me a beach day in Lebanon">
                    <span class="chatbot-action__icon">☀️</span>
                    <span class="chatbot-action__text">
                        <strong>Plan a trip</strong>
                        <small>Get a travel idea instantly</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Find me a Lebanese restaurant in Beirut">
                    <span class="chatbot-action__icon">🍽️</span>
                    <span class="chatbot-action__text">
                        <strong>Find restaurants</strong>
                        <small>Discover food spots nearby</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Recommend a hotel in Beirut">
                    <span class="chatbot-action__icon">🏨</span>
                    <span class="chatbot-action__text">
                        <strong>Find hotels</strong>
                        <small>Browse stays and options</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Give me hidden gem places in Lebanon">
                    <span class="chatbot-action__icon">📍</span>
                    <span class="chatbot-action__text">
                        <strong>Discover places</strong>
                        <small>Explore new destinations</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>
            </div>

<div class="chatbot-panel">
    <div class="chatbot-conversation-card">
        <div class="chatbot-conversation-header">
            <div class="chatbot-conversation-title">
                <div class="chatbot-bot-avatar">YN</div>
                <div>
                    <h3>Yalla Nemshi Assistant</h3>
                    <p>Smart recommendations for Lebanon</p>
                </div>
            </div>

            <button id="new-chat-btn" type="button" class="chat-footer-btn">
                New Chat
            </button>
        </div>

        <div id="chat-box" class="chat-box">
            <div class="message bot-message">
                <div class="message-avatar bot-avatar">YN</div>
                <div class="message-body">
                    <div class="message-label">Yalla Nemshi</div>
                    <div class="message-text">
                        Hi! Tell me what kind of trip or place you’re looking for, and I’ll help you plan it.
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="chat-input-panel">
        <div class="chat-input-area">
            <input type="text" id="chat-input" placeholder="Ask about hotels, restaurants, or trip ideas..." />
            <button id="send-btn" type="button" class="chat-send-btn">
                ➤
            </button>
        </div>

        <div class="chat-input-footer">
            <div class="chat-footer-left">
                <span class="chat-footer-pill">Trip planning</span>
                <span class="chat-footer-pill">Hotels</span>
                <span class="chat-footer-pill">Restaurants</span>
            </div>

            <div class="chat-footer-note">
                Built for Lebanon trip planning
            </div>
        </div>
    </div>
</div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
const chatBox = document.getElementById('chat-box');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const newChatBtn = document.getElementById('new-chat-btn');
const suggestionChips = document.querySelectorAll('.chatbot-chip');

let currentSessionId = localStorage.getItem('chat_session_id') || null;

sendBtn.addEventListener('click', sendMessage);
newChatBtn.addEventListener('click', startNewChat);

chatInput.addEventListener('keypress', function (e) {
    if (e.key === 'Enter' && !sendBtn.disabled) {
        sendMessage();
    }
});

suggestionChips.forEach(chip => {
    chip.addEventListener('click', function () {
        chatInput.value = this.dataset.prompt;
        chatInput.focus();
    });
});

async function startNewChat() {
    try {
        const response = await fetch("{{ route('chatbot.newSession') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        currentSessionId = data.session_id;
        localStorage.setItem('chat_session_id', currentSessionId);

     chatBox.innerHTML = `
    <div class="message bot-message">
        <div class="message-avatar bot-avatar">YN</div>
        <div class="message-body">
            <div class="message-label">Yalla Nemshi</div>
            <div class="message-text">Hi! Tell me what kind of trip or place you're looking for, and I'll help you plan it.</div>
        </div>
    </div>
`;
    } catch (error) {
        console.error(error);
        alert('Could not start a new chat.');
    }
}

async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    addMessage(message, 'user-message', 'You');
    chatInput.value = '';
    sendBtn.disabled = true;

    const typingId = 'typing-msg-' + Date.now();
chatBox.innerHTML += `
    <div class="message bot-message" id="${typingId}">
        <div class="message-avatar bot-avatar">YN</div>
        <div class="message-body">
            <div class="message-label">Yalla Nemshi</div>
            <div class="message-text">Thinking...</div>
        </div>
    </div>
`;
    scrollChatToBottom();

    try {
        const response = await fetch("{{ route('chatbot.send') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                message,
                session_id: currentSessionId
            })
        });

        const data = await response.json();

        const typing = document.getElementById(typingId);
        if (typing) typing.remove();

        if (data.session_id) {
            currentSessionId = data.session_id;
            localStorage.setItem('chat_session_id', currentSessionId);
        }

        if (!response.ok) {
            addMessage(data.reply || 'Request failed.', 'bot-message', 'Yalla Nemshi');
            console.error('Backend error:', data);
            return;
        }

        addMessage(data.reply || 'No reply returned.', 'bot-message', 'Yalla Nemshi');
    } catch (error) {
        const typing = document.getElementById(typingId);
        if (typing) typing.remove();

        addMessage('Something went wrong while contacting the chatbot.', 'bot-message', 'Yalla Nemshi');
        console.error(error);
    } finally {
        sendBtn.disabled = false;
        chatInput.focus();
    }
}
function addMessage(text, className, label) {
    const avatar = className === 'user-message' ? 'Y' : 'YN';
    const avatarClass = className === 'user-message' ? 'user-avatar' : 'bot-avatar';

    chatBox.innerHTML += `
        <div class="message ${className}">
            <div class="message-avatar ${avatarClass}">${escapeHtml(avatar)}</div>
            <div class="message-body">
                <div class="message-label">${escapeHtml(label)}</div>
                <div class="message-text">${escapeHtml(text)}</div>
            </div>
        </div>
    `;
    scrollChatToBottom();
}

function scrollChatToBottom() {
    chatBox.scrollTop = chatBox.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
</script>
@endpush