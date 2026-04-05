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
                <h1>Plan Lebanon with a conversation that actually feels useful.</h1>
                <p>
                    Plan your Lebanon trip with grounded suggestions for hotels, restaurants,
                    destinations, and day ideas — all in one place.
                </p>
            </div>

            <div class="chatbot-hero-strip">
                <div class="chatbot-hero-strip__content">
                    <div class="chatbot-hero-strip__metrics">
                        <article class="chatbot-metric">
                            <strong>Hotels</strong>
                            <span>Search stays by city, vibe, and budget.</span>
                        </article>
                        <article class="chatbot-metric">
                            <strong>Restaurants</strong>
                            <span>Match dining spots to the mood of your trip.</span>
                        </article>
                        <article class="chatbot-metric">
                            <strong>Trips</strong>
                            <span>Build day plans and multi-day itineraries faster.</span>
                        </article>
                    </div>

                </div>

                <div class="chatbot-hero-strip__visual">
                    <img src="{{ asset('images/chatback.jpg') }}" alt="Lebanon travel planning">
                    <div class="chatbot-visual-card__overlay">
                        <span class="chatbot-visual-card__badge">Sample trip</span>
                        <h3>Batroun seaside escape</h3>
                        <p>Sunset walk, seafood lunch, boutique stay, and an old town evening.</p>
                    </div>
                    <div class="chatbot-visual-float">
                        <i class="ri-sparkling-2-line"></i>
                        <div>
                            <strong>Best with detail</strong>
                            <p>Add city, budget, and vibe for sharper recommendations.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="chatbot-actions">
                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Plan me a beach day in Batroun with sunset">
                    <span class="chatbot-action__icon">☀️</span>
                    <span class="chatbot-action__text">
                        <strong>Plan a coastal day</strong>
                        <small>Fast trip ideas with beaches and sunsets</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Find me a Lebanese restaurant in Beirut">
                    <span class="chatbot-action__icon">🍽️</span>
                    <span class="chatbot-action__text">
                        <strong>Find restaurants</strong>
                        <small>Discover food spots by mood and city</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Recommend a hotel in Beirut near the sea">
                    <span class="chatbot-action__icon">🏨</span>
                    <span class="chatbot-action__text">
                        <strong>Find hotels</strong>
                        <small>Search stays for couples, friends, or families</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>

                <button type="button" class="chatbot-action chatbot-chip" data-prompt="Give me hidden gem places in Lebanon">
                    <span class="chatbot-action__icon">📍</span>
                    <span class="chatbot-action__text">
                        <strong>Discover places</strong>
                        <small>Explore quieter spots and local favorites</small>
                    </span>
                    <span class="chatbot-action__plus">+</span>
                </button>
            </div>

            <div class="chatbot-panel">
                <div class="chatbot-panel__main">
                <div class="chatbot-conversation-card">
                    <div class="chatbot-conversation-header">
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

                        <button id="new-chat-btn" type="button" class="chat-footer-btn">
                            <i class="ri-refresh-line"></i>
                            <span>New Chat</span>
                        </button>
                    </div>

                    <div class="chatbot-conversation-toolbar">
                        <span class="chatbot-toolbar-pill"><i class="ri-hotel-line"></i> Stays</span>
                        <span class="chatbot-toolbar-pill"><i class="ri-restaurant-line"></i> Dining</span>
                        <span class="chatbot-toolbar-pill"><i class="ri-road-map-line"></i> Itineraries</span>
                    </div>

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

                <div class="chat-input-panel">
                    <label class="chat-input-label" for="chat-input">Ask Yalla Nemshi</label>
                    <div class="chat-input-area">
                        <span class="chat-input-icon"><i class="ri-quill-pen-ai-line"></i></span>
                        <input
                            type="text"
                            id="chat-input"
                            placeholder="Try: Plan a 3 day trip in Byblos with seafood and sunset"
                            autocomplete="off"
                        />
                        <button id="send-btn" type="button" class="chat-send-btn" aria-label="Send message">
                            ➤
                        </button>
                    </div>

                    <div class="chat-input-footer">
                        <div class="chat-footer-left">
                            <span class="chat-footer-pill">City-aware</span>
                            <span class="chat-footer-pill">Budget-aware</span>
                            <span class="chat-footer-pill">Trip planning</span>
                        </div>

                        <div class="chat-footer-note">
                            Tip: mention city, vibe, budget, and trip length.
                        </div>
                    </div>
                </div>

                </div>

                <aside class="chatbot-panel__aside">
                    <div class="chatbot-side-card">
                        <div class="chatbot-side-card__header">
                            <p class="chatbot-side-card__eyebrow">Popular prompts</p>
                            <h4>Use a better starting point</h4>
                        </div>

                        <div class="chatbot-prompt-list">
                            <button type="button" class="chatbot-prompt chatbot-chip" data-prompt="Plan a 2 day seaside trip in Batroun with sunset and seafood">
                                <i class="ri-sun-foggy-line"></i>
                                <span>2 day seaside trip in Batroun</span>
                            </button>
                            <button type="button" class="chatbot-prompt chatbot-chip" data-prompt="Find me a romantic dinner in Beirut with a sea view">
                                <i class="ri-heart-3-line"></i>
                                <span>Romantic dinner in Beirut</span>
                            </button>
                            <button type="button" class="chatbot-prompt chatbot-chip" data-prompt="Recommend a budget hotel in Byblos near the old town">
                                <i class="ri-wallet-3-line"></i>
                                <span>Budget hotel in Byblos</span>
                            </button>
                            <button type="button" class="chatbot-prompt chatbot-chip" data-prompt="Give me hidden gem places in Lebanon for a quiet day">
                                <i class="ri-map-2-line"></i>
                                <span>Hidden gem places for a quiet day</span>
                            </button>
                        </div>
                    </div>

                    <div class="chatbot-side-card">
                        <div class="chatbot-side-card__header">
                            <p class="chatbot-side-card__eyebrow">How to ask</p>
                            <h4>Get more reliable answers</h4>
                        </div>

                        <ul class="chatbot-guide-list">
                            <li><span>1</span> Add a city or route.</li>
                            <li><span>2</span> Mention budget and trip length.</li>
                            <li><span>3</span> Describe the vibe you want.</li>
                            <li><span>4</span> Ask for hotels, restaurants, or a full plan.</li>
                        </ul>
                    </div>
                </aside>
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
