/*
 * Tabaix Affiliate Cards — block editor (no build step, no JSX).
 * Manual fields always work. "Fetch" and "Search" use the site's own
 * Creators API credentials through the plugin's REST routes.
 */
(function (wp) {
    'use strict';

    var el = wp.element.createElement;
    var useState = wp.element.useState;
    var registerBlockType = wp.blocks.registerBlockType;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var C = wp.components;
    var apiFetch = wp.apiFetch;

    var MAX_PRODUCTS = 6;

    function emptyProduct() {
        return { asin: '', title: '', image: '', features: '', rating: '', badge: '' };
    }

    function Edit(props) {
        var attributes = props.attributes;
        var setAttributes = props.setAttributes;
        var products = attributes.products || [];
        var layout = attributes.layout || 'single';

        var state = useState({});
        var busy = state[0], setBusy = state[1];
        var msgState = useState({});
        var msgs = msgState[0], setMsgs = msgState[1];
        var kwState = useState('');
        var keywords = kwState[0], setKeywords = kwState[1];
        var resState = useState([]);
        var results = resState[0], setResults = resState[1];
        var searchState = useState('');
        var searchMsg = searchState[0], setSearchMsg = searchState[1];

        function setBusyKey(key, value) {
            var next = Object.assign({}, busy);
            next[key] = value;
            setBusy(next);
        }
        function setMsgKey(key, value) {
            var next = Object.assign({}, msgs);
            next[key] = value;
            setMsgs(next);
        }
        function updateProduct(index, patch) {
            var next = products.slice();
            next[index] = Object.assign({}, next[index], patch);
            setAttributes({ products: next });
        }
        function addProduct(product) {
            if (products.length >= MAX_PRODUCTS) {
                return;
            }
            setAttributes({ products: products.concat([product || emptyProduct()]) });
        }
        function removeProduct(index) {
            var next = products.slice();
            next.splice(index, 1);
            setAttributes({ products: next });
        }

        function fetchProduct(index) {
            var asin = (products[index].asin || '').trim().toUpperCase();
            if (!asin) {
                setMsgKey(index, 'Type the ASIN first.');
                return;
            }
            setBusyKey(index, true);
            setMsgKey(index, '');
            apiFetch({ path: '/tac/v1/fetch', method: 'POST', data: { asin: asin } })
                .then(function (item) {
                    var patch = { asin: asin };
                    if (item.title) { patch.title = item.title; }
                    if (item.image) { patch.image = item.image; }
                    updateProduct(index, patch);
                    setMsgKey(index, item.price ? ('Fetched. Live price: ' + item.price) : 'Fetched from Amazon.');
                    setBusyKey(index, false);
                })
                .catch(function (err) {
                    setMsgKey(index, (err && err.message) ? err.message : 'Fetch failed.');
                    setBusyKey(index, false);
                });
        }

        function search() {
            if (!keywords.trim()) {
                setSearchMsg('Type a keyword first.');
                return;
            }
            setSearchMsg('Searching…');
            apiFetch({ path: '/tac/v1/search', method: 'POST', data: { keywords: keywords } })
                .then(function (items) {
                    setResults(items || []);
                    setSearchMsg(items && items.length ? '' : 'No results.');
                })
                .catch(function (err) {
                    setResults([]);
                    setSearchMsg((err && err.message) ? err.message : 'Search failed.');
                });
        }

        var blockProps = useBlockProps({ className: 'tac-editor' });

        var inspector = el(InspectorControls, null,
            el(C.PanelBody, { title: 'Layout', initialOpen: true },
                el(C.SelectControl, {
                    label: 'Layout',
                    value: layout,
                    options: [
                        { label: 'Single product card', value: 'single' },
                        { label: 'Product list (grid)', value: 'list' },
                        { label: 'Comparison table', value: 'table' }
                    ],
                    onChange: function (value) { setAttributes({ layout: value }); },
                    help: 'Single uses the first product. Table compares up to 4. List shows up to 6.'
                }),
                el(C.TextControl, {
                    label: 'Button text',
                    value: attributes.ctaText || '',
                    onChange: function (value) { setAttributes({ ctaText: value }); }
                })
            ),
            el(C.PanelBody, { title: 'Find products (Amazon API)', initialOpen: false },
                el(C.TextControl, {
                    label: 'Keyword search',
                    value: keywords,
                    onChange: setKeywords,
                    placeholder: 'e.g. car vacuum cleaner'
                }),
                el(C.Button, { isSecondary: true, onClick: search }, 'Search Amazon'),
                searchMsg ? el('p', { className: 'tac-editor-note' }, searchMsg) : null,
                results.map(function (item, i) {
                    return el('div', { key: i, className: 'tac-editor-result' },
                        el('span', null, item.title || item.asin),
                        el(C.Button, {
                            isSmall: true,
                            isPrimary: true,
                            onClick: function () {
                                addProduct({ asin: item.asin || '', title: item.title || '', image: item.image || '', features: '', rating: '', badge: '' });
                            }
                        }, 'Add')
                    );
                })
            )
        );

        var productPanels = products.map(function (p, index) {
            return el('div', { key: index, className: 'tac-editor-product' },
                el('div', { className: 'tac-editor-product-head' },
                    el('strong', null, 'Product ' + (index + 1)),
                    el(C.Button, { isSmall: true, isDestructive: true, onClick: function () { removeProduct(index); } }, 'Remove')
                ),
                el(C.TextControl, {
                    label: 'ASIN',
                    value: p.asin || '',
                    onChange: function (value) { updateProduct(index, { asin: value }); },
                    placeholder: 'B0XXXXXXXX'
                }),
                el(C.Button, {
                    isSecondary: true,
                    isBusy: !!busy[index],
                    disabled: !!busy[index],
                    onClick: function () { fetchProduct(index); }
                }, 'Fetch title & image from Amazon'),
                msgs[index] ? el('p', { className: 'tac-editor-note' }, msgs[index]) : null,
                el(C.TextControl, {
                    label: 'Title',
                    value: p.title || '',
                    onChange: function (value) { updateProduct(index, { title: value }); }
                }),
                el(C.TextControl, {
                    label: 'Image URL',
                    value: p.image || '',
                    onChange: function (value) { updateProduct(index, { image: value }); },
                    help: 'Fetch fills this from Amazon. You can also paste an image link.'
                }),
                p.image ? el('img', { src: p.image, alt: '', className: 'tac-editor-thumb' }) : null,
                el(C.TextareaControl, {
                    label: 'Features (one per line)',
                    value: p.features || '',
                    onChange: function (value) { updateProduct(index, { features: value }); },
                    rows: 3
                }),
                el(C.TextControl, {
                    label: 'Rating text (optional)',
                    value: p.rating || '',
                    onChange: function (value) { updateProduct(index, { rating: value }); },
                    placeholder: 'e.g. Best for sand and dust'
                }),
                el(C.TextControl, {
                    label: 'Badge (optional)',
                    value: p.badge || '',
                    onChange: function (value) { updateProduct(index, { badge: value }); },
                    placeholder: 'e.g. Best overall'
                })
            );
        });

        return el('div', blockProps,
            inspector,
            el('div', { className: 'tac-editor-head' }, 'Tabaix Affiliate Card — ' + (layout === 'single' ? 'Single product' : (layout === 'table' ? 'Comparison table' : 'Product list'))),
            productPanels,
            products.length < MAX_PRODUCTS
                ? el(C.Button, { isPrimary: true, onClick: function () { addProduct(); } }, 'Add product')
                : null,
            el('p', { className: 'tac-editor-note' }, 'Price is never typed by hand — when the Amazon API is live, the price appears on the page automatically from Amazon.')
        );
    }

    registerBlockType('tabaix/affiliate-cards', {
        edit: Edit,
        save: function () { return null; }
    });
})(window.wp);
