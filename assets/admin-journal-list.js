import $ from 'jquery';

window.ventiler = function (idLigne, montant) {
    const ventilation = prompt(
        'Combien souhaitez-vous ventiler ? (utilisez le séparateur ; pour ventiler sur plusieurs lignes)' + "\n" +
        '(toutes les values saisies seront déduites de la ligne existante)',
        montant
    );
    if (ventilation) {
        if (ventilation >= montant) {
            alert('Vous ne pouvez pas saisir une valeur égale ou supérieur à la valeur initiale !');
        } else {
            window.location = '/admin/accounting/journal/allocate/' + idLigne + '?amount=' + encodeURIComponent(ventilation);
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    const initDropzone = function (container) {
        $('.js-dropzone', container).each(function () {
            const elmt = $(this);
            elmt.dropzone({
                url: elmt.attr('href'),
                previewTemplate: $('.js-dz-preview-template').html(),
                init: function () {
                    this.on('error', function (file, errorMessage) {
                        $('.js-upload-loader').hide();
                        alert(errorMessage);
                    });
                    this.on('success', function () {
                        elmt.parents('td').first().find('.js-has-attachment').show();
                        $('.js-upload-loader').hide();
                    });
                    this.on('uploadprogress', function (file, progress) {
                        if (progress < 100) {
                            $('.js-upload-loader').show();
                        } else {
                            $('.js-upload-loader').hide();
                        }
                    });
                }
            });
            elmt.show();
        });
    };
    initDropzone(document);

    $('.js-attachment-change').change(function (e) {
        const checkbox = e.target;
        const val = checkbox.checked ? checkbox.value : null;

        $.ajax({
            url: $(checkbox).data('post-url'),
            type: 'post',
            data: { val },
            dataType: 'json',
            success: function () {
                const formContainer = $(checkbox).parent().find('.js-attachment-form-container');
                if (val) {
                    formContainer.find('.js-dropzone--lazy')
                        .addClass('js-dropzone')
                        .removeClass('js-dropzone--lazy');
                    initDropzone(formContainer);
                    formContainer.show();
                } else {
                    formContainer.hide();
                }
            },
            error: function () {
                checkbox.checked = !checkbox.checked;
                alert('Oops… something went wrong. Still logged in?');
            }
        });
    });

    $('.js-select-change').change(function (e) {
        const elmt = $(e.target);

        $.ajax({
            url: elmt.data('post-url'),
            type: 'post',
            data: { val: elmt.val() },
            dataType: 'json',
            success: function () {
                elmt.parent().find('span').remove();
                const span = $('<span></span>');
                span.html(elmt.find('option:selected').html() + ' &#x2705;');
                elmt.parent().append(span);
                elmt.remove();
            },
            error: function () {
                elmt.parent().find('span').remove();
                const span = $('<span></span>');
                span.html(' &#x1F6AB;');
                elmt.parent().append(span);
                elmt.val('');
            }
        });
    });

    $('a.js-edit-comment').click(function (e) {
        e.stopPropagation();

        const elmt = e.target.nodeName === 'I' ? $(e.target).parent() : $(e.target);
        const comment = prompt('Commentaire :', elmt.data('comment'));

        if (comment !== null) {
            $.ajax({
                url: elmt.data('post-url'),
                type: 'post',
                data: { val: comment },
                dataType: 'json',
                success: function () {
                    $('.icon', elmt).toggleClass('outline', comment.length === 0);
                    let tooltipLabel = 'Editer le commentaire';
                    if (comment.length > 0) {
                        tooltipLabel += ' ("' + comment + '")';
                    }
                    elmt.attr('data-tooltip', tooltipLabel);
                    elmt.data('comment', comment);
                },
                error: function () {
                    alert('Oops… something went wrong. Still logged in?');
                }
            });
        }

        return false;
    });
});
