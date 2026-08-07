@php
    $assistantName = setting('assistant.name', 'WellDone Assistant');
    $assistantGreeting = setting(
        'assistant.greeting',
        'Hello. I can help you find your way around the site — request a quotation, apply for a job, or reach the right team. What are you looking for?'
    );
    $assistantOpeners = setting('assistant.suggestions');
    $assistantOpeners = is_array($assistantOpeners) && $assistantOpeners !== []
        ? array_slice(array_filter($assistantOpeners, 'is_string'), 0, 4)
        : ['How do I request a quotation?', 'What services do you offer?', 'Are you hiring?', 'How do I contact you?'];
@endphp

<button type="button"
        class="float-btn float-btn--assistant"
        data-assistant-toggle
        aria-expanded="false"
        aria-controls="site-assistant"
        aria-label="Open the {{ $assistantName }}"
        data-bs-toggle="tooltip"
        data-bs-placement="left"
        title="Ask {{ $assistantName }}">
    <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
</button>

<div class="assistant-panel"
     id="site-assistant"
     data-assistant-panel
     data-assistant-endpoint="{{ route('site.assistant.ask') }}"
     data-assistant-reset="{{ route('site.assistant.reset') }}"
     role="dialog"
     aria-labelledby="assistant-heading"
     hidden>

    <header class="assistant-panel__head">
        <div class="assistant-panel__identity">
            <span class="assistant-panel__avatar" aria-hidden="true"><i class="fa-solid fa-headset"></i></span>
            <div>
                <p class="assistant-panel__name" id="assistant-heading">{{ $assistantName }}</p>
                <p class="assistant-panel__role">Here to help you find things</p>
            </div>
        </div>

        <div class="assistant-panel__actions">
            <button type="button" class="assistant-icon-btn" data-assistant-restart aria-label="Start a new conversation">
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
            </button>
            <button type="button" class="assistant-icon-btn" data-assistant-close aria-label="Close the assistant">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
    </header>

    {{--
        The transcript is a live region so a screen reader hears each reply as
        it lands. `polite` rather than `assertive`: an answer is worth waiting
        for the current sentence to finish.
    --}}
    <div class="assistant-panel__log" data-assistant-log role="log" aria-live="polite" aria-atomic="false">
        <div class="assistant-msg assistant-msg--bot">
            <p class="assistant-bubble">{{ $assistantGreeting }}</p>
        </div>

        <div class="assistant-chips" data-assistant-openers>
            @foreach($assistantOpeners as $opener)
                <button type="button" class="assistant-chip" data-assistant-suggestion="{{ $opener }}">{{ $opener }}</button>
            @endforeach
        </div>
    </div>

    <form class="assistant-panel__form" data-assistant-form>
        <label class="visually-hidden" for="assistant-input">Your question</label>
        <input type="text"
               id="assistant-input"
               name="message"
               class="assistant-input"
               placeholder="Ask a question…"
               maxlength="{{ config('assistant.max_question_length', 500) }}"
               autocomplete="off"
               data-assistant-input>
        <button type="submit" class="assistant-send" data-assistant-send aria-label="Send">
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
        </button>
    </form>

    <p class="assistant-panel__note">
        Answers come from this website. For anything binding, please
        <a href="{{ route('site.contact') }}">contact the team</a>.
    </p>
</div>
