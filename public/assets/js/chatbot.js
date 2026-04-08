const chatBox = document.getElementById('chat-box');
const chatInput = document.getElementById('chat-input');
const sendBtn = document.getElementById('send-btn');
const newChatBtn = document.getElementById('new-chat-btn');
const suggestionChips = document.querySelectorAll('.chatbot-chip');
const chatbotPage = document.querySelector('.chatbot-page');
const sendUrl = chatbotPage?.dataset.sendUrl || '/chatbot/message';
const newSessionUrl = chatbotPage?.dataset.newSessionUrl || '/chatbot/new-session';
const csrfToken = chatbotPage?.dataset.csrfToken
    || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    || '';

if (!chatBox || !chatInput || !sendBtn || !newChatBtn || !chatbotPage) {
    console.warn('Chatbot UI is not fully available on this page.');
} else {
let currentSessionId = localStorage.getItem('chat_session_id') || null;
let activeRequestController = null;
let chatViewVersion = 0;
let activeTypingId = null;

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
    const startVersion = ++chatViewVersion;

    if (activeRequestController) {
        activeRequestController.abort();
        activeRequestController = null;
    }

    if (activeTypingId) {
        removeTypingMessage(activeTypingId);
        activeTypingId = null;
    }

    newChatBtn.disabled = true;
    sendBtn.disabled = true;
    currentSessionId = null;
    localStorage.removeItem('chat_session_id');

    try {
        const response = await fetch(newSessionUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (startVersion !== chatViewVersion) {
            return;
        }

        currentSessionId = data.session_id;
        localStorage.setItem('chat_session_id', currentSessionId);

        chatBox.innerHTML = getWelcomeMessageHtml();
        sendBtn.disabled = false;
        scrollChatToBottom();
        chatInput.focus();
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error(error);
        alert('Could not start a new chat.');
    } finally {
        if (startVersion === chatViewVersion) {
            newChatBtn.disabled = false;
            sendBtn.disabled = false;
        }
    }
}

async function sendMessage() {
    const message = chatInput.value.trim();
    if (!message) return;

    const requestVersion = chatViewVersion;

    addMessage(message, 'user-message', 'You');
    chatInput.value = '';
    sendBtn.disabled = true;

    const typingId = 'typing-msg-' + Date.now();
    activeTypingId = typingId;
    chatBox.insertAdjacentHTML('beforeend', `
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
    `);
    scrollChatToBottom();

    const controller = new AbortController();
    activeRequestController = controller;

    try {
        const response = await fetch(sendUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            signal: controller.signal,
            body: JSON.stringify({
                message,
                session_id: currentSessionId
            })
        });

        const data = await response.json();
        const entityLinks = Array.isArray(data.entity_links) ? data.entity_links : [];

        removeTypingMessage(typingId);
        if (activeTypingId === typingId) {
            activeTypingId = null;
        }

        if (requestVersion !== chatViewVersion) {
            return;
        }

        if (data.session_id) {
            currentSessionId = data.session_id;
            localStorage.setItem('chat_session_id', currentSessionId);
        }

        if (!response.ok) {
            addBotResponse(data.reply || 'Request failed.', entityLinks, data.structured || null);
            console.error('Backend error:', data);
            return;
        }

        addBotResponse(data.reply || 'No reply returned.', entityLinks, data.structured || null);
    } catch (error) {
        removeTypingMessage(typingId);
        if (activeTypingId === typingId) {
            activeTypingId = null;
        }

        if (error.name === 'AbortError') {
            return;
        }

        if (requestVersion !== chatViewVersion) {
            return;
        }

        addMessage('Something went wrong while contacting the chatbot.', 'bot-message', 'Yalla Nemshi');
        console.error(error);
    } finally {
        if (activeRequestController === controller) {
            activeRequestController = null;
        }

        sendBtn.disabled = false;
        if (requestVersion === chatViewVersion) {
            chatInput.focus();
        }
    }
}

function removeTypingMessage(typingId) {
    const typing = document.getElementById(typingId);
    if (typing) typing.remove();
}

function addMessage(text, className, label, entityLinks = [], options = {}) {
    const avatar = className === 'user-message' ? 'Y' : 'YN';
    const avatarClass = className === 'user-message' ? 'user-avatar' : 'bot-avatar';
    const cleanText = String(text || '').trim();
    const formattedText = className === 'bot-message'
        ? formatBotContent(cleanText, entityLinks, options.structured || null)
        : formatPlainText(cleanText);
    const scrollMode = options.scrollMode || 'bottom';
    const messageClass = options.messageClass ? ` ${options.messageClass}` : '';

    chatBox.insertAdjacentHTML('beforeend', `
        <div class="message ${className}${messageClass}">
            <div class="message-avatar ${avatarClass}">${escapeHtml(avatar)}</div>
            <div class="message-body">
                <div class="message-label">${escapeHtml(label)}</div>
                <div class="message-text">${formattedText}</div>
            </div>
        </div>
    `);

    const messageElement = chatBox.lastElementChild;

    if (scrollMode === 'bottom') {
        scrollChatToBottom();
    } else if (scrollMode === 'start' && messageElement) {
        scrollElementToTop(messageElement);
    }

    return messageElement;
}

function addBotResponse(text, entityLinks = [], structured = null) {
    const hasTripPlan = hasStructuredTripPlan(structured);
    const messageElement = addMessage(text, 'bot-message', 'Yalla Nemshi', entityLinks, {
        scrollMode: 'none',
        structured,
        messageClass: hasTripPlan ? 'message--trip' : ''
    });

    if (hasTripPlan) {
        const tripSupportHtml = renderTripSupportResponse(structured);
        if (tripSupportHtml) {
            chatBox.insertAdjacentHTML('beforeend', tripSupportHtml);
        }
    } else if (hasStructuredContent(structured)) {
        chatBox.insertAdjacentHTML('beforeend', renderStructuredResponse(structured));
    }

    if (messageElement) {
        scrollElementToTop(messageElement);
    }
}

function hasStructuredContent(structured) {
    if (!structured || typeof structured !== 'object') {
        return false;
    }

    const sections = getDisplayStructuredSections(structured);

    return sections.length > 0;
}

function hasStructuredTripPlan(structured) {
    return !!(structured && structured.trip_plan && Array.isArray(structured.trip_plan.days) && structured.trip_plan.days.length);
}

function renderStructuredResponse(structured) {
    const sections = getDisplayStructuredSections(structured);

    const sectionsHtml = sections.map(renderStructuredSection).join('');

    return `
        <section class="chat-structured-panel" aria-label="Structured recommendations">
            ${sectionsHtml}
        </section>
    `;
}

function renderTripSupportResponse(structured) {
    const sections = getTripSupportSections(structured);
    if (!sections.length) {
        return '';
    }

    return `
        <section class="chat-structured-panel chat-structured-panel--trip-support" aria-label="Trip places">
            ${sections.map(renderStructuredSection).join('')}
        </section>
    `;
}

function getTripSupportSections(structured) {
    const tripPlan = structured?.trip_plan;
    const days = Array.isArray(tripPlan?.days) ? tripPlan.days : [];
    if (!days.length) {
        return [];
    }

    const stayItems = [];
    const restaurantItems = [];
    const seenStayKeys = new Set();
    const seenRestaurantKeys = new Set();

    days.forEach(day => {
        if (!day || typeof day !== 'object') {
            return;
        }

        const flow = day.flow && typeof day.flow === 'object' ? day.flow : {};
        const stay = flow.stay;
        if (stay && typeof stay === 'object') {
            const key = String(stay.id || stay.hotel_name || '').trim().toLowerCase();
            if (key && !seenStayKeys.has(key)) {
                seenStayKeys.add(key);
                stayItems.push({
                    title: String(stay.hotel_name || '').trim(),
                    subtitle: String(stay.address || '').trim(),
                    meta: [
                        String(stay.price_per_night || '').trim(),
                        stay.rating_score ? `Rating ${stay.rating_score}/10` : '',
                        String(stay.budget_tier || '').trim().replace(/_/g, ' '),
                    ].filter(Boolean),
                    url: typeof stay.url === 'string' ? stay.url : null,
                    url_label: typeof stay.url === 'string' ? 'Open hotel' : null,
                });
            }
        }

        ['lunch', 'dinner'].forEach(slotKey => {
            const restaurant = flow[slotKey];
            if (!restaurant || typeof restaurant !== 'object') {
                return;
            }

            const key = String(restaurant.id || restaurant.restaurant_name || '').trim().toLowerCase();
            if (!key || seenRestaurantKeys.has(key)) {
                return;
            }

            seenRestaurantKeys.add(key);
            restaurantItems.push({
                title: String(restaurant.restaurant_name || '').trim(),
                subtitle: String(restaurant.location || '').trim(),
                meta: [
                    String(restaurant.food_type || '').trim(),
                    String(restaurant.price_tier || '').trim(),
                    restaurant.rating ? `Rating ${restaurant.rating}/5` : '',
                ].filter(Boolean),
                url: typeof restaurant.url === 'string' ? restaurant.url : null,
                url_label: typeof restaurant.url === 'string' ? 'Open restaurant' : null,
            });
        });
    });

    return [
        stayItems.length ? {
            type: 'hotels',
            title: 'Stay From This Plan',
            items: stayItems,
        } : null,
        restaurantItems.length ? {
            type: 'restaurants',
            title: 'Restaurants In This Plan',
            items: restaurantItems,
        } : null,
    ].filter(Boolean);
}

function getDisplayStructuredSections(structured) {
    return Array.isArray(structured?.sections)
        ? structured.sections.filter(section =>
            section
            && section.type !== 'activities'
            && Array.isArray(section.items)
            && section.items.length
        )
        : [];
}

function renderStructuredSection(section) {
    const items = Array.isArray(section.items) ? section.items.filter(Boolean) : [];
    if (!items.length) {
        return '';
    }

    return `
        <section class="chat-structured-section chat-structured-section--${escapeHtml(String(section.type || 'results'))}">
            <div class="chat-structured-section__header">${escapeHtml(String(section.title || 'Recommendations'))}</div>
            <div class="chat-structured-grid">
                ${items.map(renderStructuredCard).join('')}
            </div>
        </section>
    `;
}

function renderStructuredCard(item) {
    const meta = Array.isArray(item.meta) ? item.meta.filter(Boolean) : [];
    const hasUrl = typeof item.url === 'string' && item.url.trim() !== '';

    const titleHtml = hasUrl
        ? `<a class="chat-structured-card__title" href="${escapeHtml(String(item.url))}">${escapeHtml(String(item.title || 'Recommendation'))}</a>`
        : `<div class="chat-structured-card__title">${escapeHtml(String(item.title || 'Recommendation'))}</div>`;

    const subtitleHtml = item.subtitle
        ? `<div class="chat-structured-card__subtitle">${escapeHtml(String(item.subtitle))}</div>`
        : '';

    const metaHtml = meta.length
        ? `<div class="chat-structured-card__meta">${meta.map(entry => `<span>${escapeHtml(String(entry))}</span>`).join('')}</div>`
        : '';

    const linkHtml = hasUrl && item.url_label
        ? `<a class="chat-structured-card__link" href="${escapeHtml(String(item.url))}">${escapeHtml(String(item.url_label))}</a>`
        : '';

    return `
        <article class="chat-structured-card">
            ${titleHtml}
            ${subtitleHtml}
            ${metaHtml}
            ${linkHtml}
        </article>
    `;
}

function formatBotContent(text, entityLinks = [], structured = null) {
    if (hasStructuredTripPlan(structured)) {
        return renderTripPlanInline(text, entityLinks, structured.trip_plan);
    }

    return formatBotText(text, entityLinks);
}

function renderTripPlanInline(text, entityLinks, tripPlan) {
    const parsedTrip = parseTripReply(text, tripPlan);
    const mergedDays = mergeTripDaysWithFallback(parsedTrip.days, tripPlan);

    if (!mergedDays.length) {
        return formatBotText(text, entityLinks);
    }

    const titleHtml = parsedTrip.title
        ? `<div class="trip-inline__title">${escapeHtml(String(parsedTrip.title))}</div>`
        : '';

    const introHtml = parsedTrip.intro.length
        ? parsedTrip.intro.map(paragraph => `<p>${formatBotText(paragraph, entityLinks)}</p>`).join('')
        : '';
    const outroHtml = parsedTrip.outro.length
        ? `
            <div class="trip-inline__outro">
                ${parsedTrip.outro.map(paragraph => `<p>${formatBotText(paragraph, entityLinks)}</p>`).join('')}
            </div>
        `
        : '';

    return `
        <div class="trip-inline">
            <div class="trip-inline__intro">
                ${titleHtml}
                ${introHtml}
            </div>
            <div class="trip-inline__days">
                ${mergedDays.map(day => renderTripInlineDay(day, entityLinks)).join('')}
            </div>
            ${outroHtml}
        </div>
    `;
}

function parseTripReply(text, tripPlan) {
    const cleanLines = String(text || '')
        .split(/\n+/)
        .map(line => line.trim())
        .filter(Boolean)
        .filter(line => !/^Top .+ matches:/i.test(line));

    const fallbackTitle = String(tripPlan?.title || '').trim();
    const lines = [...cleanLines];
    let title = fallbackTitle || '';

    const intro = [];
    const days = [];
    const outro = [];
    let currentDay = null;
    let currentSlot = null;
    const knownLocations = Array.isArray(tripPlan?.days)
        ? tripPlan.days
            .map(day => String(day?.location || '').trim())
            .filter(Boolean)
        : [];

    lines.forEach(line => {
        const dayMatch = line.match(/^Day\s+(\d+)(?:\s*(?:-|in)\s*(.+?))?:?$/i);
        if (dayMatch) {
            currentDay = {
                dayNumber: Number(dayMatch[1]),
                heading: `Day ${dayMatch[1]}`,
                location: (dayMatch[2] || '').trim(),
                intro: [],
                slots: [],
            };
            days.push(currentDay);
            currentSlot = null;
            return;
        }

        if (!currentDay) {
            intro.push(line);
            return;
        }

        if (isTripOutroLine(line)) {
            currentSlot = null;
            outro.push(line);
            return;
        }

        if (!currentDay.location && !currentDay.intro.length && !currentDay.slots.length && isStandaloneTripLocationLine(line, knownLocations)) {
            currentDay.location = line;
            return;
        }

        const explicitSlot = detectExplicitTripSlot(line);
        if (explicitSlot) {
            currentSlot = {
                label: explicitSlot.label,
                title: explicitSlot.title || '',
                content: explicitSlot.content || '',
            };
            currentDay.slots.push(currentSlot);
            return;
        }

        const inferredSlot = detectImplicitTripSlot(line);
        if (inferredSlot && (!currentSlot || inferredSlot.label !== currentSlot.label)) {
            currentSlot = {
                label: inferredSlot.label,
                title: inferredSlot.title || '',
                content: inferredSlot.content || '',
            };
            currentDay.slots.push(currentSlot);
            return;
        }

        if (currentSlot) {
            currentSlot.content = appendTripSlotContent(currentSlot.content, line);
        } else {
            currentDay.intro.push(line);
        }
    });

    const extractedTitleIndex = intro.findIndex(line => isLikelyTripTitle(line));
    if (extractedTitleIndex !== -1) {
        const [parsedTitle] = intro.splice(extractedTitleIndex, 1);
        title = pickPreferredTripTitle(parsedTitle, fallbackTitle);
    } else if (!title && fallbackTitle) {
        title = fallbackTitle;
    }

    return {
        title,
        intro: intro.length ? intro : (tripPlan?.summary ? [String(tripPlan.summary).trim()] : []),
        days,
        outro,
    };
}

function mergeTripDaysWithFallback(parsedDays, tripPlan) {
    const fallbackDays = Array.isArray(tripPlan?.days) ? tripPlan.days : [];
    const normalizedParsedDays = Array.isArray(parsedDays) ? parsedDays.filter(Boolean) : [];

    if (!fallbackDays.length) {
        return normalizedParsedDays;
    }

    const parsedDaysByNumber = new Map(
        normalizedParsedDays
            .filter(day => Number.isFinite(Number(day?.dayNumber)))
            .map(day => [Number(day.dayNumber), day])
    );

    const mergedDays = fallbackDays.map((fallbackDay, index) => {
        if (!fallbackDay || typeof fallbackDay !== 'object') {
            return normalizedParsedDays[index] || null;
        }

        const fallbackDayNumber = Number(fallbackDay.day);
        const parsedDay = parsedDaysByNumber.get(fallbackDayNumber) || normalizedParsedDays[index] || null;

        const slotOrder = ['morning', 'lunch', 'afternoon', 'evening', 'dinner', 'stay'];
        const parsedSlotsByKey = new Map(
            (Array.isArray(parsedDay?.slots) ? parsedDay.slots : [])
                .filter(slot => slot && slot.label)
                .map(slot => [String(slot.label).trim().toLowerCase(), slot])
        );

        const mergedSlots = slotOrder
            .map((slotKey) => parsedSlotsByKey.get(slotKey) || buildFallbackTripSlot(slotKey, fallbackDay.flow?.[slotKey]))
            .filter(Boolean);

        return {
            ...(parsedDay || {}),
            dayNumber: parsedDay?.dayNumber || fallbackDayNumber || (index + 1),
            heading: parsedDay?.heading || `Day ${fallbackDayNumber || (index + 1)}`,
            intro: Array.isArray(parsedDay?.intro) ? parsedDay.intro : [],
            location: parsedDay?.location || String(fallbackDay.location || '').trim(),
            slots: mergedSlots,
        };
    }).filter(Boolean);

    return mergedDays;
}

function buildFallbackTripSlot(slotKey, slotValue) {
    if (!slotValue || typeof slotValue !== 'object') {
        return null;
    }

    const label = capitalizeLabel(slotKey);

    if (slotKey === 'stay') {
        const hotelName = String(slotValue.hotel_name || '').trim();
        if (!hotelName) {
            return null;
        }

        const address = String(slotValue.address || '').trim();
        const price = String(slotValue.price_per_night || '').trim();
        let content = `Stay at ${hotelName}`;

        if (address) {
            content += ` in ${address}`;
        }

        if (price) {
            content += `, with rates around ${price}`;
        }

        return { label, title: '', content: `${content}.` };
    }

    if (slotKey === 'lunch' || slotKey === 'dinner') {
        const restaurantName = String(slotValue.restaurant_name || '').trim();
        if (!restaurantName) {
            return null;
        }

        const location = String(slotValue.location || '').trim();
        const foodType = String(slotValue.food_type || '').trim();
        const priceTier = String(slotValue.price_tier || '').trim();
        let content = slotKey === 'dinner'
            ? `For dinner, head to ${restaurantName}`
            : `For lunch, head to ${restaurantName}`;

        if (location) {
            content += ` in ${location}`;
        }

        const details = [foodType, priceTier].filter(Boolean);
        if (details.length) {
            content += ` for ${details.join(', ')}`;
        }

        return { label, title: '', content: `${content}.` };
    }

    const title = String(slotValue.title || '').trim();
    const activities = Array.isArray(slotValue.activities)
        ? slotValue.activities.map(activity => String(activity || '').trim()).filter(Boolean)
        : [];

    if (!title && !activities.length) {
        return null;
    }

    return {
        label,
        title,
        content: activities.length ? `${activities.join('; ')}.` : '',
    };
}

function detectExplicitTripSlot(line) {
    const colonMatch = line.match(/^(Morning|Lunch|Afternoon|Evening|Dinner|Stay)\s*:\s*(.+)$/i);
    if (colonMatch) {
        return {
            label: capitalizeLabel(colonMatch[1]),
            title: '',
            content: colonMatch[2].trim(),
        };
    }

    const exactLabelMatch = line.match(/^(Morning|Lunch|Afternoon|Evening|Dinner|Stay)$/i);
    if (exactLabelMatch) {
        return {
            label: capitalizeLabel(exactLabelMatch[1]),
            title: '',
            content: '',
        };
    }

    const headingPatterns = [
        { label: 'Morning', pattern: /^(Morning\b.*|Final Morning)$/i },
        { label: 'Lunch', pattern: /^Lunch\b.*$/i },
        { label: 'Afternoon', pattern: /^(Afternoon\b.*|Wrap up and explore)$/i },
        { label: 'Evening', pattern: /^Evening\b.*$/i },
        { label: 'Dinner', pattern: /^Dinner\b.*$/i },
        { label: 'Stay', pattern: /^(Stay\b.*|Overnight\b.*)$/i },
    ];

    const headingMatch = headingPatterns.find(({ pattern }) => pattern.test(line));
    if (!headingMatch) {
        return null;
    }

    return {
        label: headingMatch.label,
        title: line.trim(),
        content: '',
    };
}

function detectImplicitTripSlot(line) {
    const patterns = [
        { label: 'Morning', pattern: /^(Start your day\b|Begin your\b.*morning\b|Ease into\b.*morning\b)/i },
        { label: 'Lunch', pattern: /^(For lunch\b|Enjoy another\b.*lunch\b|Head to\b.*for lunch\b)/i },
        { label: 'Afternoon', pattern: /^(Spend your afternoon\b|For the afternoon\b|Spend the final afternoon\b)/i },
        { label: 'Evening', pattern: /^(As the day winds down\b|In the evening\b|Later in the evening\b)/i },
        { label: 'Dinner', pattern: /^(For dinner\b|Cap off your day\b|End the day with dinner\b)/i },
        { label: 'Stay', pattern: /^(Stay at\b|Overnight at\b|Spend the night at\b)/i },
    ];

    const match = patterns.find(({ pattern }) => pattern.test(line));
    if (!match) {
        return null;
    }

    return {
        label: match.label,
        title: '',
        content: line.trim(),
    };
}

function appendTripSlotContent(existingContent, line) {
    const cleanExisting = String(existingContent || '').trim();
    const cleanLine = String(line || '').trim();

    if (!cleanLine) {
        return cleanExisting;
    }

    if (!cleanExisting) {
        return cleanLine;
    }

    return `${cleanExisting} ${cleanLine}`.trim();
}

function isStandaloneTripLocationLine(line, knownLocations = []) {
    const normalizedLine = normalizeTripLine(line);
    if (!normalizedLine) {
        return false;
    }

    return knownLocations.some(location => normalizeTripLine(location) === normalizedLine);
}

function renderTripInlineDay(day, entityLinks = []) {
    const location = day.location ? `<span>${escapeHtml(String(day.location))}</span>` : '';
    const introHtml = Array.isArray(day.intro) && day.intro.length
        ? day.intro.map(paragraph => `<p class="trip-inline__day-copy">${formatBotText(paragraph, entityLinks)}</p>`).join('')
        : '';
    const slotsHtml = Array.isArray(day.slots)
        ? day.slots.map(slot => renderTripInlineSlot(slot, entityLinks)).join('')
        : '';

    return `
        <section class="trip-inline__day">
            <div class="trip-inline__day-head">
                <strong>${escapeHtml(String(day.heading || 'Day'))}</strong>
                ${location}
            </div>
            ${introHtml}
            <div class="trip-inline__slots">
                ${slotsHtml}
            </div>
        </section>
    `;
}

function renderTripInlineSlot(slot, entityLinks = []) {
    if (!slot || typeof slot !== 'object' || (!slot.title && !slot.content)) {
        return '';
    }

    const titleHtml = slot.title
        ? `<div class="trip-inline__slot-title">${formatBotText(String(slot.title), entityLinks)}</div>`
        : '';
    const contentHtml = slot.content
        ? `<div class="trip-inline__slot-copy">${formatBotText(String(slot.content), entityLinks)}</div>`
        : '';

    return `
        <div class="trip-inline__slot">
            <div class="trip-inline__slot-label">${escapeHtml(String(slot.label || 'Plan'))}</div>
            <div class="trip-inline__slot-content">
                ${titleHtml}
                ${contentHtml}
            </div>
        </div>
    `;
}

function isLikelyTripTitle(line) {
    const rawLine = String(line || '').trim();
    const normalized = normalizeTripLine(line);

    if (!normalized || rawLine.length > 80 || /[.!?]/.test(rawLine)) {
        return false;
    }

    return normalized.includes('trip')
        || normalized.includes('itinerary')
        || normalized.includes('escape')
        || normalized.includes('weekend');
}

function normalizeTripLine(line) {
    return String(line || '')
        .trim()
        .replace(/[:.]+$/, '')
        .toLowerCase();
}

function capitalizeLabel(value) {
    const text = String(value || '').trim().toLowerCase();
    return text ? text.charAt(0).toUpperCase() + text.slice(1) : '';
}

function isTripOutroLine(line) {
    return /^(I hope\b|Hope you\b|Enjoy your\b|Wishing you\b|Let me know\b|If you need\b)/i.test(String(line || '').trim());
}

function pickPreferredTripTitle(parsedTitle, fallbackTitle) {
    const cleanParsed = String(parsedTitle || '').trim();
    const cleanFallback = String(fallbackTitle || '').trim();

    if (!cleanParsed) {
        return cleanFallback;
    }

    if (!cleanFallback) {
        return cleanParsed;
    }

    return cleanParsed.length >= cleanFallback.length ? cleanParsed : cleanFallback;
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
        if (!linkName) {
            return;
        }

        const pattern = buildEntityLinkPatternSafe(linkName);
        if (!pattern.test(tokenizedText)) {
            pattern.lastIndex = 0;
            return;
        }

        pattern.lastIndex = 0;

        const token = `__ENTITY_LINK_${index}__`;
        tokenizedText = tokenizedText.replace(pattern, (_, prefix = '') => `${prefix}${token}`);
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

function buildEntityLinkPatternSafe(name) {
    const patternCore = String(name || '')
        .trim()
        .split(/\s+/)
        .map(part => escapeRegExp(part)
            .replace(/['’]/g, "['’]")
            .replace(/-/g, '[-–—]'))
        .join('\\s+');

    return new RegExp(`(^|[^A-Za-z0-9])(${patternCore})(?=$|[^A-Za-z0-9])`, 'gi');
}

function buildEntityLinkPattern(name) {
    const patternCore = String(name || '')
        .trim()
        .split(/\s+/)
        .map(part => escapeRegExp(part)
            .replace(/['’]/g, "['’]")
            .replace(/-/g, '[-–—]'))
        .join('\\s+');

    return new RegExp(`(^|[^A-Za-z0-9])(${patternCore})(?=$|[^A-Za-z0-9])`, 'gi');
}

function scrollChatToBottom() {
    chatBox.scrollTop = chatBox.scrollHeight;
}

function scrollElementToTop(element) {
    const topOffset = Math.max(0, element.offsetTop - 12);
    chatBox.scrollTop = topOffset;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.innerText = text;
    return div.innerHTML;
}
}
