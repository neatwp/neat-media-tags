(function($) {
    function initNeatMediaTagsAutocomplete($input) {
        if ($input.data('ui-autocomplete')) {
            return;
        }

        $input.autocomplete({
            source: function(request, response) {
                var terms = request.term.split(',');
                var lastTerm = $.trim(terms[terms.length - 1]);

                if (lastTerm.length < 2) {
                    response([]);
                    return;
                }

                $.get(neatMediaTagsConfig.ajaxurl, {
                    action: 'neat_media_tags_autocomplete',
                    term: lastTerm,
                    nonce: neatMediaTagsConfig.nonce
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
                "ui-autocomplete": "ui-autocomplete-neat-media-tags"
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
                widget.addClass('ui-autocomplete-neat-media-tags')
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
        $('input.neat-media-tags-input, input[name="neat_media_tag"]').each(function() {
            initNeatMediaTagsAutocomplete($(this));
        });

        $(document).on('focus', 'input.neat-media-tags-input, input[name="neat_media_tag"], input[name*="[neat_media_tag]"]', function() {
            initNeatMediaTagsAutocomplete($(this));
        });
    });

    if (typeof wp !== 'undefined' && wp.media) {
        wp.media.view.Modal.prototype.on('open', function() {
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes.length) {
                        $(mutation.addedNodes).find('input.neat-media-tags-input, input[name="neat_media_tag"], input[name*="[neat_media_tag]"]').each(function() {
                            initNeatMediaTagsAutocomplete($(this));
                        });
                    }
                });
            });

            var modalContent = document.querySelector('.media-modal-content');
            if (modalContent) {
                observer.observe(modalContent, {
                    childList: true,
                    subtree: true
                });
                window.neatMediaTagsObserver = observer;
            }

            setTimeout(function() {
                $('.media-modal-content').find('input.neat-media-tags-input, input[name="neat_media_tag"], input[name*="[neat_media_tag]"]').each(function() {
                    initNeatMediaTagsAutocomplete($(this));
                });
            }, 100);
        });

        wp.media.view.Modal.prototype.on('close', function() {
            if (window.neatMediaTagsObserver) {
                window.neatMediaTagsObserver.disconnect();
            }
        });
    }
})(jQuery);