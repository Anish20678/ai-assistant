(function($){
    function renderForm($container, fields, buttonText){
        $container.empty();

        if (!Array.isArray(fields) || !fields.length) {
            $container.prop('hidden', true).hide();
            return;
        }

        fields.forEach(function(field){
            var id = 'envara-field-' + Math.random().toString(36).substring(2, 9);
            var type = (field.type || 'text').toLowerCase();
            var name = field.label ? field.label.toString().toLowerCase().replace(/[^a-z0-9_]/g, '_') : 'field_' + id;
            var required = !!field.required;
            var labelText = field.label || '';
            var $field = $('<div class="envara-ai-form-field" />');

            if ('checkbox' === type) {
                var $checkboxLabel = $('<label class="envara-ai-checkbox" />').attr('for', id);
                var checkbox = $('<input type="checkbox" />').attr({ id: id, name: name });
                if (required) {
                    checkbox.prop('required', true);
                }
                $checkboxLabel.append(checkbox);
                $checkboxLabel.append($('<span class="envara-ai-checkbox-label" />').text(labelText + (required ? ' *' : '')));
                $field.append($checkboxLabel);
            } else {
                var $label = $('<label class="envara-ai-form-label" />').attr('for', id).text(labelText + (required ? ' *' : ''));
                var input;
                switch (type) {
                    case 'textarea':
                        input = $('<textarea />');
                        break;
                    case 'email':
                        input = $('<input type="email" />');
                        break;
                    case 'phone':
                        input = $('<input type="tel" />');
                        break;
                    case 'date':
                        input = $('<input type="date" />');
                        break;
                    case 'dropdown':
                        input = $('<select />');
                        if (field.options) {
                            field.options.split(',').forEach(function(option){
                                var trimmed = option.trim();
                                if (trimmed) {
                                    var optionEl = $('<option />').val(trimmed).text(trimmed);
                                    input.append(optionEl);
                                }
                            });
                        }
                        break;
                    default:
                        input = $('<input type="text" />');
                        break;
                }

                input.attr({ id: id, name: name }).addClass('envara-ai-input-control');
                if (required) {
                    input.prop('required', true);
                }

                $field.append($label).append(input);
            }

            $container.append($field);
        });

        if (!$container.find('button[type="submit"]').length) {
            var text = buttonText || 'Start chat';
            var $submit = $('<button type="submit" class="envara-ai-form-submit"><span></span></button>');
            $submit.find('span').text(text);
            $container.append($submit);
        }

        $container.prop('hidden', false).show();
    }

    function appendMessage($messages, sender, text){
        if (!text) {
            return;
        }

        var $message = $('<div class="envara-ai-message" />').addClass('from-' + sender);
        var $bubble = $('<div class="envara-ai-message-bubble" />');

        if ('assistant' === sender) {
            $bubble.html(text);
        } else {
            $bubble.text(text);
        }

        $message.append($bubble);
        $messages.append($message);
        $messages.scrollTop($messages[0].scrollHeight);
    }

    function startSession(chatbotId, formData){
        return $.ajax({
            url: EnvaraAiAssistantPublic.restUrl + '/session',
            method: 'POST',
            beforeSend: function(xhr){
                xhr.setRequestHeader('X-WP-Nonce', EnvaraAiAssistantPublic.nonce);
            },
            data: {
                chatbot_id: chatbotId,
                form: formData || {},
                page: window.location.href
            }
        });
    }

    function sendMessage(sessionId, chatbotId, message){
        return $.ajax({
            url: EnvaraAiAssistantPublic.restUrl + '/chat',
            method: 'POST',
            beforeSend: function(xhr){
                xhr.setRequestHeader('X-WP-Nonce', EnvaraAiAssistantPublic.nonce);
            },
            data: {
                session_id: sessionId,
                message: message,
                chatbot_id: chatbotId
            }
        });
    }

    function uniqueSuggestions(items){
        var seen = {};
        return items.filter(function(item){
            var key = item.toLowerCase();
            if (seen[key]) {
                return false;
            }
            seen[key] = true;
            return true;
        });
    }

    function parseSuggestions(settings){
        if (!settings || 'object' !== typeof settings) {
            return [];
        }

        var keys = ['suggestions', 'quick_links', 'quickReplies', 'quick_replies', 'faqs', 'faq', 'help_articles'];
        var collected = [];

        keys.forEach(function(key){
            if (!Object.prototype.hasOwnProperty.call(settings, key)) {
                return;
            }

            var source = settings[key];

            if (Array.isArray(source)) {
                source.forEach(function(entry){
                    if ('string' === typeof entry) {
                        var trimmed = entry.trim();
                        if (trimmed) {
                            collected.push(trimmed);
                        }
                    } else if (entry && 'object' === typeof entry) {
                        var candidate = entry.label || entry.title || entry.text || '';
                        if ('string' === typeof candidate && candidate.trim()) {
                            collected.push(candidate.trim());
                        }
                    }
                });
            } else if ('string' === typeof source) {
                source.split(/\r?\n|,/).forEach(function(item){
                    var trimmed = item.trim();
                    if (trimmed) {
                        collected.push(trimmed);
                    }
                });
            }
        });

        return uniqueSuggestions(collected);
    }

    function initWidget($widget){
        var data = $widget.data('chatbot');
        if ('string' === typeof data) {
            try {
                data = JSON.parse(data);
            } catch (e) {
                data = null;
            }
        }

        if (!data) {
            return;
        }

        var $launch = $widget.find('.envara-ai-launch');
        var $window = $widget.find('.envara-ai-window');
        var $messages = $widget.find('.envara-ai-messages');
        var $form = $widget.find('.envara-ai-form');
        var $chatForm = $widget.find('.envara-ai-chat');
        var $chatTextarea = $chatForm.find('textarea[name="message"]');
        var $views = $widget.find('.envara-ai-view');
        var $navButtons = $widget.find('.envara-ai-nav button');
        var $startChatButtons = $widget.find('.envara-ai-start-chat');
        var $search = $widget.find('.envara-ai-search');
        var $searchInput = $search.find('input[type="search"]');
        var $suggestionsList = $widget.find('.envara-ai-suggestion-list');
        var $backButtons = $widget.find('.envara-ai-back');
        var $helpText = $widget.find('.envara-ai-help-text');

        var sessionId = null;
        var sessionPromise = null;
        var storedFormData = {};
        var hasShownWelcome = false;
        var hasForm = Array.isArray(data.form) && data.form.length;
        var formCompleted = !hasForm;

        var suggestions = parseSuggestions(data.settings || {});

        if (suggestions.length) {
            $search.removeAttr('hidden').show();
        }

        function renderSuggestions(filter){
            if (!suggestions.length) {
                $suggestionsList.empty().hide();
                return;
            }

            var query = (filter || '').toLowerCase();
            var results = suggestions.filter(function(item){
                return !query || item.toLowerCase().indexOf(query) !== -1;
            });

            $suggestionsList.empty().show();

            results.slice(0, 6).forEach(function(item){
                var $item = $('<li />');
                var $button = $('<button type="button" class="envara-ai-suggestion" />').text(item);
                $item.append($button);
                $suggestionsList.append($item);
            });

            if (!results.length) {
                $suggestionsList.hide();
            }
        }

        renderSuggestions('');

        var helpMessage = '';
        if (EnvaraAiAssistantPublic.defaultSettings && EnvaraAiAssistantPublic.defaultSettings.privacy_message) {
            helpMessage = EnvaraAiAssistantPublic.defaultSettings.privacy_message;
        }
        if ($helpText.length) {
            if (helpMessage) {
                $helpText.html(helpMessage);
            } else {
                $helpText.text('We keep your data private and use it only to improve your support experience.');
            }
        }

        if (hasForm) {
            renderForm($form, data.form, data.formButton);
            $widget.find('.envara-ai-home-actions').hide();
        } else {
            $form.remove();
        }

        function setNavActive(target){
            $navButtons.removeClass('is-active');
            $navButtons.filter('[data-target="' + target + '"]').addClass('is-active');
        }

        function showView(view){
            $views.removeClass('is-active');
            var $target = $views.filter('[data-view="' + view + '"]');

            if ('conversation' === view) {
                if (hasForm && !formCompleted) {
                    setNavActive('home');
                    $views.filter('[data-view="home"]').addClass('is-active');
                    $form.prop('hidden', false).show();
                    var $firstInput = $form.find(':input:visible:first');
                    if ($firstInput.length) {
                        $firstInput.trigger('focus');
                    }
                    return;
                }

                if ($target.length) {
                    $target.addClass('is-active');
                }
                setNavActive('messages');
                $widget.addClass('envara-ai-in-conversation');
                $chatForm.prop('hidden', false);
                ensureSession();
            } else {
                if ($target.length) {
                    $target.addClass('is-active');
                }
                setNavActive(view);
                $widget.removeClass('envara-ai-in-conversation');
            }
        }

        function ensureSession(formData){
            var isNewData = formData && Object.keys(formData).length;

            if (isNewData) {
                storedFormData = formData;
                formCompleted = true;
            } else if (hasForm && !formCompleted) {
                return $.Deferred().reject().promise();
            }

            if (sessionId) {
                return $.Deferred().resolve({ session_id: sessionId }).promise();
            }

            if (sessionPromise) {
                return sessionPromise;
            }

            var payload = storedFormData || {};

            sessionPromise = startSession(data.id, payload)
                .done(function(response){
                    sessionId = response.session_id || sessionId;
                    formCompleted = true;
                    if (response.welcome && !hasShownWelcome) {
                        appendMessage($messages, 'assistant', response.welcome);
                        hasShownWelcome = true;
                    }
                })
                .fail(function(){
                    sessionPromise = null;
                })
                .always(function(){
                    sessionPromise = null;
                });

            return sessionPromise;
        }

        function openWindow(){
            $window.prop('hidden', false);
            $launch.attr('aria-expanded', 'true');
            showView('home');
        }

        function closeWindow(){
            $window.prop('hidden', true);
            $launch.attr('aria-expanded', 'false');
            showView('home');
        }

        $launch.on('click', function(){
            if ($window.prop('hidden')) {
                openWindow();
            } else {
                closeWindow();
            }
        });

        $widget.find('.envara-ai-close').on('click', function(){
            closeWindow();
        });

        $startChatButtons.on('click', function(){
            if (hasForm && !formCompleted) {
                showView('home');
                $form.prop('hidden', false).show();
                var $firstField = $form.find(':input:visible:first');
                if ($firstField.length) {
                    $firstField.trigger('focus');
                }
                return;
            }

            showView('conversation');
            $chatTextarea.focus();
        });

        $backButtons.on('click', function(){
            var target = $(this).data('target') || 'messages';
            showView(target);
        });

        $navButtons.on('click', function(){
            var target = $(this).data('target');
            if (target) {
                showView(target);
            }
        });

        $form.on('submit', function(event){
            event.preventDefault();
            var formData = {};
            var arrayData = $form.serializeArray();

            arrayData.forEach(function(item){
                if (Object.prototype.hasOwnProperty.call(formData, item.name)) {
                    if (!Array.isArray(formData[item.name])) {
                        formData[item.name] = [formData[item.name]];
                    }
                    formData[item.name].push(item.value);
                } else {
                    formData[item.name] = item.value;
                }
            });

            var $submit = $form.find('button[type="submit"]');
            $submit.prop('disabled', true).addClass('is-loading');

            ensureSession(formData)
                .done(function(){
                    $form.prop('hidden', true).hide();
                    formCompleted = true;
                    showView('conversation');
                    $chatTextarea.focus();
                })
                .fail(function(){
                    alert('Unable to start chat session.');
                })
                .always(function(){
                    $submit.prop('disabled', false).removeClass('is-loading');
                });
        });

        $chatForm.on('submit', function(event){
            event.preventDefault();
            var message = $chatTextarea.val().trim();
            if (!message) {
                return;
            }

            $chatTextarea.val('');
            appendMessage($messages, 'user', message);

            ensureSession()
                .then(function(){
                    return sendMessage(sessionId, data.id, message);
                })
                .done(function(response){
                    appendMessage($messages, 'assistant', response.response || '');
                })
                .fail(function(){
                    appendMessage($messages, 'assistant', 'Sorry, something went wrong.');
                });
        });

        $searchInput.on('input', function(){
            renderSuggestions($(this).val());
        });

        $widget.on('click', '.envara-ai-suggestion', function(){
            var text = $(this).text().trim();
            if (!text) {
                return;
            }

            if (hasForm && !formCompleted) {
                showView('home');
                $form.prop('hidden', false).show();
                var $first = $form.find(':input:visible:first');
                if ($first.length) {
                    $first.trigger('focus');
                }
                return;
            }

            showView('conversation');
            $chatTextarea.val(text).focus();
        });

        showView('home');
    }

    $(function(){
        $('.envara-ai-widget').each(function(){
            initWidget($(this));
        });
    });
})(jQuery);
