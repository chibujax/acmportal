(function () {
    var input = document.getElementById('docs-search');
    var results = document.getElementById('docs-search-results');
    var scriptTag = document.currentScript;
    if (!input || !results || !scriptTag) return;

    var searchUrl = scriptTag.getAttribute('data-search-url');
    var fuse = null;

    fetch(searchUrl, { headers: { 'Accept': 'application/json' } })
        .then(function (r) { return r.json(); })
        .then(function (data) {
            fuse = new Fuse(data, {
                keys: ['title', 'excerpt', 'keywords', 'sectionLabel'],
                threshold: 0.35,
            });
        })
        .catch(function () {});

    input.addEventListener('input', function () {
        var q = input.value.trim();

        if (!q || !fuse) {
            results.classList.add('d-none');
            results.innerHTML = '';
            return;
        }

        var hits = fuse.search(q).slice(0, 8);

        if (!hits.length) {
            results.innerHTML = '<div class="list-group-item small text-muted">No results.</div>';
            results.classList.remove('d-none');
            return;
        }

        results.innerHTML = hits.map(function (hit) {
            var item = hit.item;
            return '<a href="' + item.url + '" class="list-group-item list-group-item-action small">' +
                '<div class="fw-medium">' + escapeHtml(item.title) + '</div>' +
                '<div class="text-muted" style="font-size:.75rem">' + escapeHtml(item.sectionLabel) + '</div>' +
                '</a>';
        }).join('');
        results.classList.remove('d-none');
    });

    document.addEventListener('click', function (e) {
        if (!results.contains(e.target) && e.target !== input) {
            results.classList.add('d-none');
        }
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
})();
