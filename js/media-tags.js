(function($) {
    function initMediaTagsAutocomplete($input) {
        if ($input.data('ui-autocomplete')) return;
        
        $input.autocomplete({
            source: function(request, response) {
                var terms = request.term.split(',');
                var lastTerm = $.trim(terms[terms.length - 1]);
                
                if (lastTerm.length < 2) {
                    response([]);
                    return;
                }

                $.get(mediaTagsConfig.ajaxurl, {
                    action: 'media_tags_autocomplete',
                    term: lastTerm,
                    nonce: mediaTagsConfig.nonce
                }, function(res) {
                    res && res.success ? response(res.data) : response([]);
                }).fail(function() {
                    response([]);
                });
            },
            minLength: 2,
            delay: 300,
            appendTo: document.body,
            classes: {
                "ui-autocomplete": "ui-autocomplete-media-tags"
            },
            position: {
                my: "left top",
                at: "left bottom",
                collision: "flipfit"
            },
            focus: function() {
                return false;
            },
            select: function(event, ui) {
                var terms = this.value.split(',');
                terms.pop();
                terms.push(ui.item.value);
                terms.push("");
                this.value = terms.join(", ");
                return false;
            },
            open: function() {
                var widget = $input.autocomplete('widget');
                widget.addClass('ui-autocomplete-media-tags')
                     .css('width', $input.outerWidth() + 'px')
                     .scrollTop(0);
            },
            create: function() {
                $(this).data('ui-autocomplete')._renderItem = function(ul, item) {
                    return $('<li>')
                        .append('<div>' + item.label + '</div>')
                        .appendTo(ul);
                };
            }
        });

        $input.on('keydown', function(e) {
            if (e.keyCode === $.ui.keyCode.COMMA) {
                var value = $(this).val();
                if (value.slice(-1) !== ',') {
                    $(this).val(value + ', ');
                }
                e.preventDefault();
            }
        });
    }

    $(function() {
        $('input.media-tags-input, input[name="media_tag"]').each(function() {
            initMediaTagsAutocomplete($(this));
        });
        
        $(document).on('focus', 'input[name="media_tag"], input[name*="[media_tag]"]', function() {
            initMediaTagsAutocomplete($(this));
        });
    });

    if (typeof wp !== 'undefined' && wp.media) {
        wp.media.view.Modal.prototype.on('open', function() {
            setTimeout(function() {
                $('input[name*="media_tag"]').each(function() {
                    initMediaTagsAutocomplete($(this));
                });
            }, 800);
        });
    }

})(jQuery);