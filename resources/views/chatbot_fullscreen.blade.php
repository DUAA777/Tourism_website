@extends('layout.app')

@section('hideFooter', '1')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot-fullscreen.css') }}">
@endpush

@section('content')
<section class="chatbot-page">
    <div class="chatbot-screen">
        <header class="chatbot-screen__top">
            <a href="{{ route('home') }}" class="chatbot-brand">
                <span class="chatbot-brand__badge">YN</span>
                <span class="chatbot-brand__copy">
                    <strong>Yalla Nemshi AI</strong>
                    <small>Lebanon trip planner</small>
                </span>
            </a>

            <div class="chatbot-screen__hero">
                <p>AI Travel Planner</p>
                <h1>Plan a trip, not just a prompt.</h1>
            </div>

            <div class="chatbot-screen__top-actions">
                <button id="new-chat-btn" type="button" class="chatbot-utility-btn">
                    <i class="ri-refresh-line"></i>
                    <span>New Chat</span>
                </button>
            </div>
        </header>

        <div class="chatbot-screen__body">
            <aside class="chatbot-landing">
                <div class="chatbot-block">
                    <div class="chatbot-block__header">
                        <p>Popular prompts</p>
                        <h2>Start with one of these</h2>
                    </div>

                    <div class="chatbot-prompt-grid">
                        <button type="button" class="chatbot-prompt-card chatbot-chip" data-prompt="Plan a 2 day seaside trip in Batroun with sunset and seafood">
                            <span class="chatbot-prompt-card__icon"><i class="ri-sun-foggy-line"></i></span>
                            <span class="chatbot-prompt-card__text">
                                <strong>2 day seaside trip in Batroun</strong>
                                <small>Sunset, seafood, and a relaxed coastal pace.</small>
                            </span>
                        </button>

                        <button type="button" class="chatbot-prompt-card chatbot-chip" data-prompt="Find me a romantic dinner in Beirut with a sea view">
                            <span class="chatbot-prompt-card__icon"><i class="ri-heart-3-line"></i></span>
                            <span class="chatbot-prompt-card__text">
                                <strong>Romantic dinner in Beirut</strong>
                                <small>Sea view, atmosphere, and date-night ideas.</small>
                            </span>
                        </button>

                        <button type="button" class="chatbot-prompt-card chatbot-chip" data-prompt="Recommend a budget hotel in Byblos near the old town">
                            <span class="chatbot-prompt-card__icon"><i class="ri-hotel-bed-line"></i></span>
                            <span class="chatbot-prompt-card__text">
                                <strong>Budget hotel in Byblos</strong>
                                <small>Good value stays close to the old town.</small>
                            </span>
                        </button>

                        <button type="button" class="chatbot-prompt-card chatbot-chip" data-prompt="Give me hidden gem places in Lebanon for a quiet day">
                            <span class="chatbot-prompt-card__icon"><i class="ri-map-2-line"></i></span>
                            <span class="chatbot-prompt-card__text">
                                <strong>Hidden gem places for a quiet day</strong>
                                <small>Less touristy spots with a calm vibe.</small>
                            </span>
                        </button>
                    </div>

                    <div class="chatbot-sidebar-tips">
                        <div class="chatbot-sidebar-tips__header">
                            <p>How to ask</p>
                            <h4>Get better answers faster</h4>
                        </div>

                        <div class="chatbot-sidebar-tips__row">
                            <article class="chatbot-sidebar-tip">
                                <span>01</span>
                                <strong>Add a city or route</strong>
                            </article>
                            <article class="chatbot-sidebar-tip">
                                <span>02</span>
                                <strong>Mention budget and duration</strong>
                            </article>
                            <article class="chatbot-sidebar-tip">
                                <span>03</span>
                                <strong>Describe the vibe</strong>
                            </article>
                        </div>
                    </div>
                </div>

            </aside>

            <section class="chatbot-chat-panel">
                <div class="chatbot-chat-panel__header">
                    <div class="chatbot-conversation-title">
                        <div class="chatbot-bot-avatar">YN</div>
                        <div>
                            <div class="chatbot-conversation-title__top">
                                <h3>Yalla Nemshi Assistant</h3>
                                <span class="chatbot-status">
                                    <span class="chatbot-status__dot"></span>
                                    Ready to plan
                                </span>
                            </div>
                            <p>Hotels, restaurants, places, and trip ideas across Lebanon</p>
                        </div>
                    </div>

                </div>

                <div class="chatbot-chat-panel__stage">
                    <div id="chat-box" class="chat-box" role="log" aria-live="polite" aria-label="Chat conversation">
                        <div class="message bot-message">
                            <div class="message-avatar bot-avatar">YN</div>
                            <div class="message-body">
                                <div class="message-label">Yalla Nemshi</div>
                                <div class="message-text">Hi! Tell me what kind of trip or place you're looking for, and I'll help you plan it.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="chatbot-input-dock">
                    <label class="chat-input-label" for="chat-input">Ask Yalla Nemshi</label>
                    <div class="chat-input-area">
                        <span class="chat-input-icon"><i class="ri-quill-pen-ai-line"></i></span>
                        <input
                            type="text"
                            id="chat-input"
                            placeholder="Plan a 2 day seaside trip in Batroun with sunset and seafood"
                            autocomplete="off"
                        />
                        <button id="send-btn" type="button" class="chat-send-btn" aria-label="Send message">
                            <i class="ri-arrow-right-line"></i>
                        </button>
                    </div>

                </div>
            </section>
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

chatInput.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' && !sendBtn.disabled) {
        e.preventDefault();
        sendMessage();
    }
});

suggestionChips.forEach(chip => {
    chip.addEventListener('click', function () {
        chatInput.value = this.dataset.prompt;
        chatInput.focus();
    });
});

function getWelcomeMessageHtml() {
    return `
        <div class="message bot-message">
            <div class="message-avatar bot-avatar">YN</div>
            <div class="message-body">
                <div class="message-label">Yalla Nemshi</div>
                <div class="message-text">Hi! Tell me what kind of trip or place you're looking for, and I'll help you plan it.</div>
            </div>
        </div>
    `;
}

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

        chatBox.innerHTML = getWelcomeMessageHtml();
        scrollChatToBottom();
        chatInput.focus();
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
                <div class="message-text">
                    <span class="typing-dots">
                        <span></span><span></span><span></span>
                    </span>
                </div>
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
        const entityLinks = Array.isArray(data.entity_links) ? data.entity_links : [];

        removeTypingMessage(typingId);

        if (data.session_id) {
            currentSessionId = data.session_id;
            localStorage.setItem('chat_session_id', currentSessionId);
        }

        if (!response.ok) {
            addMessage(data.reply || 'Request failed.', 'bot-message', 'Yalla Nemshi', entityLinks);
            console.error('Backend error:', data);
            return;
        }

        addMessage(data.reply || 'No reply returned.', 'bot-message', 'Yalla Nemshi', entityLinks);
    } catch (error) {
        removeTypingMessage(typingId);
        addMessage('Something went wrong while contacting the chatbot.', 'bot-message', 'Yalla Nemshi');
        console.error(error);
    } finally {
        sendBtn.disabled = false;
        chatInput.focus();
    }
}

function removeTypingMessage(typingId) {
    const typing = document.getElementById(typingId);
    if (typing) typing.remove();
}

function addMessage(text, className, label, entityLinks = []) {
    const avatar = className === 'user-message' ? 'Y' : 'YN';
    const avatarClass = className === 'user-message' ? 'user-avatar' : 'bot-avatar';
    const cleanText = String(text || '').trim();
    const formattedText = className === 'bot-message'
        ? formatBotText(cleanText, entityLinks)
        : formatPlainText(cleanText);

    chatBox.innerHTML += `
        <div class="message ${className}">
            <div class="message-avatar ${avatarClass}">${escapeHtml(avatar)}</div>
            <div class="message-body">
                <div class="message-label">${escapeHtml(label)}</div>
                <div class="message-text">${formattedText}</div>
            </div>
        </div>
    `;
    scrollChatToBottom();
}

function formatPlainText(text) {
    return escapeHtml(text).replace(/\n/g, '<br>');
}

function formatBotText(text, entityLinks = []) {
    const links = Array.isArray(entityLinks)
        ? entityLinks
            .filter(link => link && link.name && link.url)
            .sort((a, b) => String(b.name).length - String(a.name).length)
        : [];

    if (!links.length) {
        return formatPlainText(text);
    }

    let tokenizedText = String(text || '');
    const appliedTokens = [];

    links.forEach((link, index) => {
        const linkName = String(link.name || '');
        if (!linkName || !tokenizedText.includes(linkName)) {
            return;
        }

        const token = `__ENTITY_LINK_${index}__`;
        tokenizedText = tokenizedText.split(linkName).join(token);
        appliedTokens.push({ token, link });
    });

    let html = formatPlainText(tokenizedText);

    appliedTokens.forEach(({ token, link }) => {
        const safeToken = escapeHtml(token);
        const safeName = escapeHtml(String(link.name || ''));
        const safeUrl = escapeHtml(String(link.url || ''));
        const safeType = escapeHtml(String(link.type || 'entity'));
        const tokenPattern = new RegExp(escapeRegExp(safeToken), 'g');

        html = html.replace(
            tokenPattern,
            `<a class="chatbot-entity-link chatbot-entity-link--${safeType}" href="${safeUrl}">${safeName}</a>`
        );
    });

    return html;
}

function escapeRegExp(text) {
    return String(text).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
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
