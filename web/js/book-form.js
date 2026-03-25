(function() {
    'use strict';

    let authorsSelectedIds = [];
    let authorsListUrl = '';
    let authorsCurrentPage = 1;
    let authorsTotalPages = 1;

    const loadAuthors = (page = 1, append = false) => {
        $.ajax({
            url: authorsListUrl,
            data: { page },
            dataType: 'json',
            success: (data) => {
                let html = '';

                data.items.forEach((author) => {
                    const checked = authorsSelectedIds.indexOf(author.id) !== -1 ? 'checked' : '';
                    html += `<div class="form-check">
                        <input class="form-check-input" type="checkbox" name="BookForm[authorIds][]" value="${author.id}" id="author_${author.id}" ${checked}>
                        <label class="form-check-label" for="author_${author.id}">${author.full_name}</label>
                    </div>`;
                });

                if (!data.items.length && !append) {
                    html = '<p class="text-muted">Авторы пока не найдены.</p>';
                }

                if (append) {
                    $('#authors-list').find('.load-more-btn').remove();
                    $('#authors-list').append(html);
                } else {
                    $('#authors-list').html(html);
                }

                authorsCurrentPage = data.pagination.page;
                authorsTotalPages = data.pagination.pageCount;

                if (authorsCurrentPage < authorsTotalPages) {
                    const btnHtml = `<button type="button" class="btn btn-sm btn-link load-more-btn" onclick="loadAuthors(${authorsCurrentPage + 1}, true)">Загрузить еще</button>`;
                    $('#authors-list').append(btnHtml);
                }
            },
            error: (xhr, status, error) => {
                console.error('Ошибка загрузки авторов:', { xhr, status, error, url: authorsListUrl });
                $('#authors-list').html(`<p class="text-danger">Ошибка загрузки авторов: ${error || status}</p>`);
            }
        });
    };

    window.loadAuthors = loadAuthors;

    $(document).ready(() => {
        const $authorsList = $('#authors-list');
        if ($authorsList.length) {
            try {
                const selectedIdsAttr = $authorsList.attr('data-selected-ids') || '[]';
                authorsSelectedIds = JSON.parse(selectedIdsAttr).map((id) => parseInt(id, 10));
            } catch (e) {
                console.error('Ошибка парсинга selected-ids:', e, 'Значение:', $authorsList.attr('data-selected-ids'));
                authorsSelectedIds = [];
            }

            authorsListUrl = $authorsList.attr('data-list-url') || '';

            if (authorsListUrl) {
                loadAuthors(1, false);
            } else {
                console.error('URL для загрузки авторов не найден');
                $('#authors-list').html('<p class="text-danger">URL для загрузки авторов не настроен</p>');
            }
        }
    });
})();
