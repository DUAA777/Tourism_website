@extends('layout.app')

@section('bodyClass', 'chatbot-layout')
@section('hideFooter', '1')

@push('styles')
<link rel="stylesheet" href="{{ asset('assets/css/chatbot-fullscreen.css') }}">
@endpush

@section('content')
@php
    $chatUser = auth()->user();
    $chatUserPhoto = $chatUser && $chatUser->profile_picture ? asset($chatUser->profile_picture) : null;
    $chatUserInitial = strtoupper(substr(trim((string) ($chatUser->name ?? 'U')) ?: 'U', 0, 1));
    $chatUserName = trim((string) ($chatUser->name ?? 'there')) ?: 'there';
@endphp
<section
    class="chatbot-page"
    data-send-url="{{ route('chatbot.send') }}"
    data-new-session-url="{{ route('chatbot.newSession') }}"
    data-csrf-token="{{ csrf_token() }}"
    data-user-name="{{ $chatUserName }}"
    data-user-initial="{{ $chatUserInitial }}"
    data-user-photo="{{ $chatUserPhoto ?? '' }}"
>
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
                                <div class="message-text">Hi {{ $chatUserName }}! Tell me what kind of trip or place you're looking for, and I'll help you plan it.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="chatbot-input-dock">
                    <label class="chat-input-label" for="chat-input">Ask Yalla Nemshi</label>
                    <div class="chat-input-area">
                        <span class="chat-input-icon chat-input-icon--avatar">
                            @if($chatUserPhoto)
                                <img src="{{ $chatUserPhoto }}" alt="{{ $chatUser->name }}" class="chat-input-avatar-image">
                            @else
                                <span class="chat-input-avatar-fallback">{{ $chatUserInitial }}</span>
                            @endif
                        </span>
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
<script src="{{ asset('assets/js/chatbot.js') }}"></script>
@endpush
