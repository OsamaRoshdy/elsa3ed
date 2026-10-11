import $ from 'jquery';
import select2 from 'select2';

window.$ = window.jQuery = $;
select2(window, $);

/**
 * Turns any <select data-searchable> into a searchable Select2.
 *
 * Optional attributes:
 *  - data-ajax-url: load options from a JSON endpoint ({ results: [{ id, text }], pagination: { more } })
 *  - data-placeholder: placeholder text
 */
function initSelect2(root = document) {
    const lang = window.portalLang ?? {};

    $(root).find('select[data-searchable]').each(function () {
        const $select = $(this);
        const ajaxUrl = $select.data('ajax-url');

        $select.select2({
            width: '100%',
            dir: document.documentElement.dir || 'ltr',
            placeholder: $select.data('placeholder') || '',
            allowClear: true,
            language: {
                noResults: () => lang.noResults ?? 'No results found',
                searching: () => lang.searching ?? 'Searching…',
                errorLoading: () => lang.errorLoading ?? 'The results could not be loaded.',
                loadingMore: () => lang.loadingMore ?? 'Loading more results…',
            },
            ajax: ajaxUrl
                ? {
                      url: ajaxUrl,
                      dataType: 'json',
                      delay: 250,
                      data: (params) => ({ q: params.term ?? '', page: params.page ?? 1 }),
                      // Select2 4.1 normalizes AJAX items with an unbound `this`, which throws once bundled
                      // (strict mode). Giving each item its own _resultId skips that code path.
                      processResults: (data) => ({
                          ...data,
                          results: (data.results ?? []).map((item) => ({
                              ...item,
                              _resultId: `${this.name || 'select2'}-result-${item.id}`,
                          })),
                      }),
                      cache: true,
                  }
                : undefined,
        });

        // Select2 only fires jQuery events; re-dispatch a native "change" so Alpine (x-model, @change) sees it.
        $select.on('select2:select select2:clear', () => {
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}

$(() => initSelect2());
