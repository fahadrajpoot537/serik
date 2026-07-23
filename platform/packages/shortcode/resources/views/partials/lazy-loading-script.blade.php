@once
    <script>
        var lazyLoadShortcodeBlocks = function() {
            const loaders = Array.from(document.querySelectorAll('.shortcode-lazy-loading'));

            if (loaders.length === 0) {
                return;
            }

            const csrfToken = '{{ csrf_token() }}';
            const batchUrl = '{{ route('public.ajax.render-ui-blocks-batch') }}';
            const singleUrl = '{{ route('public.ajax.render-ui-block') }}';

            document.body.classList.add('lazy-loading-active');

            const applyBlockHtml = function(element, data, name, attributes) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data;
                const firstChild = tempDiv.firstElementChild;

                if (firstChild) {
                    firstChild.classList.add('shortcode-lazy-loading-loaded');
                    data = tempDiv.innerHTML;
                }

                const scripts = tempDiv.querySelectorAll('script');

                element.outerHTML = data;

                scripts.forEach(function(oldScript) {
                    const newScript = document.createElement('script');
                    if (oldScript.src) {
                        newScript.src = oldScript.src;
                    } else {
                        newScript.textContent = oldScript.textContent;
                    }
                    Array.from(oldScript.attributes).forEach(function(attr) {
                        newScript.setAttribute(attr.name, attr.value);
                    });
                    document.body.appendChild(newScript);
                });

                document.dispatchEvent(new CustomEvent('shortcode.loaded', {
                    detail: {
                        name,
                        attributes,
                        html: data,
                        ok: true
                    }
                }));
            };

            const finishLoading = function() {
                setTimeout(function() {
                    const remainingLoaders = document.querySelectorAll('.shortcode-lazy-loading');
                    if (remainingLoaders.length === 0) {
                        document.body.classList.remove('lazy-loading-active');
                    }
                }, 100);
            };

            const handleFailure = function(element) {
                try {
                    element.outerHTML = '<div class="text-center py-3 text-muted">Content temporarily unavailable.</div>';
                } catch (e) {}
                finishLoading();
            };

            const loadSingleBlock = function(element) {
                const name = element.getAttribute('data-name');
                const attributes = JSON.parse(element.getAttribute('data-attributes') || '{}');

                return fetch(singleUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            name,
                            attributes
                        })
                    })
                    .then(response => response.json().then(payload => ({
                        ok: response.ok,
                        payload
                    })).catch(() => ({
                        ok: false,
                        payload: null
                    })))
                    .then(({
                        ok,
                        payload
                    }) => {
                        const error = payload && payload.error;
                        let data = payload && payload.data;

                        if (!ok || error || data === null || data === undefined) {
                            handleFailure(element);
                            return;
                        }

                        applyBlockHtml(element, data, name, attributes);
                    })
                    .catch(function(error) {
                        console.error('Fetch error:', error);
                        handleFailure(element);
                    });
            };

            const loadBlocksIndividually = function(elements) {
                return Promise.all(elements.map(loadSingleBlock)).then(function() {
                    if (typeof Theme !== 'undefined' && typeof Theme.lazyLoadInstance !== 'undefined') {
                        Theme.lazyLoadInstance.update();
                    }
                    finishLoading();
                });
            };

            if (loaders.length > 1) {
                const blocks = loaders.map(function(element, index) {
                    const blockId = element.getAttribute('data-block-id') || String(index);
                    element.setAttribute('data-block-id', blockId);

                    return {
                        id: blockId,
                        name: element.getAttribute('data-name'),
                        attributes: JSON.parse(element.getAttribute('data-attributes') || '{}'),
                        element: element
                    };
                });

                fetch(batchUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({
                            blocks: blocks.map(function(block) {
                                return {
                                    id: block.id,
                                    name: block.name,
                                    attributes: block.attributes
                                };
                            })
                        })
                    })
                    .then(response => response.json().then(payload => ({
                        ok: response.ok,
                        status: response.status,
                        payload
                    })).catch(() => ({
                        ok: false,
                        status: 0,
                        payload: null
                    })))
                    .then(({
                        ok,
                        status,
                        payload
                    }) => {
                        if (!ok || !payload || payload.error) {
                            console.warn('Batch shortcode load failed (' + status + '), falling back to single requests.');
                            return loadBlocksIndividually(loaders);
                        }

                        const data = payload.data || {};
                        let missing = false;

                        blocks.forEach(function(block) {
                            const html = data[block.id];
                            if (!html) {
                                missing = true;
                                handleFailure(block.element);
                                return;
                            }
                            applyBlockHtml(block.element, html, block.name, block.attributes);
                        });

                        if (typeof Theme !== 'undefined' && typeof Theme.lazyLoadInstance !== 'undefined') {
                            Theme.lazyLoadInstance.update();
                        }

                        finishLoading();

                        if (missing) {
                            console.warn('Some batch shortcode blocks were missing from the response.');
                        }
                    })
                    .catch(function(error) {
                        console.error('Batch fetch error:', error);
                        loadBlocksIndividually(loaders).finally(function() {
                            document.body.classList.remove('lazy-loading-active');
                        });
                    });

                return;
            }

            loadSingleBlock(loaders[0]).then(function() {
                if (typeof Theme !== 'undefined' && typeof Theme.lazyLoadInstance !== 'undefined') {
                    Theme.lazyLoadInstance.update();
                }
                finishLoading();
            });
        };

        window.addEventListener('load', function() {
            lazyLoadShortcodeBlocks();
        });
    </script>
@endonce
