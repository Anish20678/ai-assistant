(function($){
    function renderForm($container, fields){
        $container.empty();
        if (!fields || !fields.length) {
            $container.hide();
            return;
        }
        fields.forEach(function(field){
            var $field = $('<div class="form-field" />');
            var id = 'envara-field-' + Math.random().toString(36).substring(2, 9);
            $field.append('<label for="' + id + '">' + (field.label || '') + (field.required ? ' *' : '') + '</label>');
            var input;
            switch (field.type) {
                case 'textarea':
                    input = $('<textarea />');
                    break;
                case 'email':
                    input = $('<input type="email" />');
                    break;
                case 'phone':
                    input = $('<input type="tel" />');
                    break;
                case 'checkbox':
                    input = $('<input type="checkbox" />');
                    break;
                case 'date':
                    input = $('<input type="date" />');
                    break;
                case 'dropdown':
                    input = $('<select />');
                    if (field.options) {
                        field.options.split(',').forEach(function(option){
                            input.append('<option value="' + option.trim() + '">' + option.trim() + '</option>');
                        });
                    }
                    break;
                default:
                    input = $('<input type="text" />');
            }
            var fieldName = field.label ? field.label.toString().toLowerCase().replace(/[^a-z0-9_]/g, '_') : 'field_' + id;
            input.attr('id', id).attr('name', fieldName);
            if (field.required) {
                input.prop('required', true);
            }
            $field.append(input);
            $container.append($field);
        });
        $container.show();
    }

    function appendMessage($messages, sender, text){
        if (!text) {
            return;
        }
        var $message = $('<div class="envara-ai-message ' + sender + '"><div class="bubble"></div></div>');
        $message.find('.bubble').text(text);
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
                page: window.location.href,
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

    function initWidget($widget){
        var data = $widget.data('chatbot');
        if (typeof data === 'string') {
            try { data = JSON.parse(data); } catch (e) { data = null; }
        }
        if (!data) {
            return;
        }

        var $launch = $widget.find('.envara-ai-launch');
        var $window = $widget.find('.envara-ai-window');
        var $messages = $widget.find('.envara-ai-messages');
        var $form = $widget.find('.envara-ai-form');
        var $chatForm = $widget.find('.envara-ai-chat');
        var sessionId = null;
        var hasForm = data.form && data.form.length;

        if (data.welcomeMessage) {
            appendMessage($messages, 'assistant', data.welcomeMessage);
        }

        renderForm($form, data.form);

        function openWindow(){
            $window.prop('hidden', false);
            $launch.attr('aria-expanded', 'true');
        }

        function closeWindow(){
            $window.prop('hidden', true);
            $launch.attr('aria-expanded', 'false');
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

        if (hasForm) {
            if (!$form.find('button[type="submit"]').length) {
                $form.append('<button type="submit" class="button button-primary">' + (data.formButton || 'Start Chat') + '</button>');
            }
            $form.show();
        } else {
            $form.remove();
            $chatForm.show();
        }

        $form.on('submit', function(event){
            event.preventDefault();
            var formData = {};
            $form.serializeArray().forEach(function(item){
                formData[item.name] = item.value;
            });

            startSession(data.id, formData).done(function(response){
                sessionId = response.session_id;
                if (response.welcome) {
                    appendMessage($messages, 'assistant', response.welcome);
                }
                $form.hide();
                $chatForm.show();
            }).fail(function(){
                alert('Unable to start chat session.');
            });
        });

        $chatForm.on('submit', function(event){
            event.preventDefault();
            var $textarea = $chatForm.find('textarea[name="message"]');
            var message = $textarea.val().trim();
            if (!message) {
                return;
            }
            $textarea.val('');
            appendMessage($messages, 'user', message);

            var promise = sessionId ? $.Deferred().resolve({ session_id: sessionId, welcome: null }).promise() : startSession(data.id, {});

            promise.then(function(response){
                if (!sessionId && response.welcome) {
                    appendMessage($messages, 'assistant', response.welcome);
                }
                sessionId = response.session_id || sessionId;
                return sendMessage(sessionId, data.id, message);
            }).done(function(response){
                appendMessage($messages, 'assistant', response.response || '');
            }).fail(function(){
                appendMessage($messages, 'assistant', 'Sorry, something went wrong.');
            });
        });
    }

    $(function(){
        $('.envara-ai-widget').each(function(){
            initWidget($(this));
        });
    });
})(jQuery);
