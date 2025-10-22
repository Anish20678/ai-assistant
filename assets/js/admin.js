(function($){
    function getFields($builder){
        var fields = $builder.data('fields');
        if (typeof fields === 'string') {
            try {
                fields = JSON.parse(fields);
            } catch (e) {
                fields = [];
            }
        }
        return Array.isArray(fields) ? fields : [];
    }

    function updateInput($builder, fields){
        $builder.find('.form-fields-input').val(JSON.stringify(fields));
    }

    function renderFields($builder){
        var fields = getFields($builder);
        var $tbody = $builder.find('.envara-form-fields');
        $tbody.empty();

        if (!fields.length) {
            $tbody.append('<tr class="no-items"><td colspan="5">' + (EnvaraAiAssistant.emptyForm || 'No fields yet.') + '</td></tr>');
        }

        fields.forEach(function(field, index){
            var $row = $('<tr />');
            $row.append('<td><input type="text" class="widefat" data-key="label" value="' + (field.label || '') + '"></td>');

            var $select = $('<select data-key="type" class="widefat">\n                <option value="text">Text</option>\n                <option value="email">Email</option>\n                <option value="phone">Phone</option>\n                <option value="checkbox">Checkbox</option>\n                <option value="dropdown">Dropdown</option>\n                <option value="textarea">Textarea</option>\n                <option value="date">Date</option>\n            </select>');
            $select.val(field.type || 'text');
            $row.append($('<td />').append($select));

            var $required = $('<input type="checkbox" data-key="required" value="1">').prop('checked', !!field.required);
            $row.append($('<td />').append($required));

            var $options = $('<input type="text" class="widefat" data-key="options">').val(field.options || '');
            $row.append($('<td />').append($options));

            var $remove = $('<button type="button" class="button-link">' + (EnvaraAiAssistant.removeFieldText || 'Remove') + '</button>');
            $remove.on('click', function(){
                fields.splice(index, 1);
                $builder.data('fields', fields);
                renderFields($builder);
            });
            $row.append($('<td />').append($remove));

            $tbody.append($row);
        });

        $builder.data('fields', fields);
        updateInput($builder, fields);
    }

    $(document).on('click', '.add-form-field', function(){
        var $builder = $(this).closest('.envara-form-builder');
        var fields = getFields($builder);
        fields.push({ label: '', type: 'text', required: false });
        $builder.data('fields', fields);
        renderFields($builder);
    });

    $(document).on('change', '.envara-form-fields input, .envara-form-fields select', function(){
        var $builder = $(this).closest('.envara-form-builder');
        var fields = getFields($builder);
        var rowIndex = $(this).closest('tr').index();
        var key = $(this).data('key');
        var value = $(this).is(':checkbox') ? $(this).is(':checked') : $(this).val();
        if (fields[rowIndex]) {
            fields[rowIndex][key] = value;
        }
        $builder.data('fields', fields);
        updateInput($builder, fields);
    });

    $('.envara-form-builder').each(function(){
        var $builder = $(this);
        renderFields($builder);
    });
})(jQuery);
