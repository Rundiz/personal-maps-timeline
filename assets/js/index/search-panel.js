/**
 * JS for Search panel on index page.
 */


class SearchPanel {


    /**
     * @type {Boolean} Mark that current AJAX pagination of search result is working or not.
     */
    #ajaxPaginationWorking = false;


    /**
     * @type {Integer} Number of current offset of visited history dates.
     */
    #currentOffset = 0;


    /**
     * @type {Index}
     */
    #Index;


    /**
     * @type {Integer} Number of items per page.
     */
    #itemsPerPage = 10;


    /**
     * @type {LibMaps}
     */
    #LibMaps;


    /**
     * @type {ListingPanel}
     */
    #ListingPanel;


    /**
     * @type {String} Open search panel link ID.
     */
    #openSearchPanelLinkId = 'pmtl-open-search-panel';


    /**
     * @type {Array|Integer[]} Available pages offset that calculated from server.
     */
    #pagesOffset = [0];


    /**
     * @type {String} Search input ID.
     */
    #searchInputId = 'pmtl-search-input';


    /**
     * @type {String} Search panel container cached.
     */
    #searchPanelContainerCache = '';


    /**
     * @type {String} Search panel content placeholder ID.
     */
    #searchPanelContentPlaceholderId = 'pmtl-search-panel-content-placeholder';


    /**
     * @type {String} Search panel form ID.
     */
    #searchPanelFormId = 'pmtl-search-panel-form';


    /**
     * @type {String} Search panel template ID.
     */
    #searchPanelTemplateId = 'pmtl-search-panel-template';
    
    
    /**
     * @type {String} Search result link class name.
     */
    #searchResultLinkClass = 'pmtl-search-result-place-link';


    /**
     * @type {Integer} Total items available on DB before paginated.
     */
    #totalItems = 0;


    /**
     * Search panel constructor.
     * 
     * @param {LibMaps} LibMaps The `LibMaps` class.
     * @param {Index} Index The `Index` class.
     */
    constructor(LibMaps, Index) {
        if (typeof(LibMaps) === 'object') {
            this.#LibMaps = LibMaps;
        }
        if (typeof(Index) === 'object') {
            this.#Index = Index;
        }
        this.#ListingPanel = new ListingPanel(this.#Index.LibMaps, Index);
    }// constructor


    /**
     * AJAX search.
     * 
     * This method was called from `#listenFormSubmitSearch()`.
     * 
     * @param {string} keyword
     * @param {Integer} offset
     * @returns {Promise}
     */
    #ajaxSearch(keyword, offset = 0) {
        if (typeof(keyword) !== 'string') {
            throw new Error('The argument `keyword` must be string.');
        }

        return Ajax.fetchGet(appBasePath + '/HTTP/search.php?keyword=' + encodeURIComponent(keyword) + '&offset=' + encodeURIComponent(offset))
        .then((response) => {
            // set data.
            if (typeof(response?.search?.result?.currentOffset) === 'number') {
                this.#currentOffset = parseInt(response.search.result.currentOffset);
            } else {
                console.warn('There is no `currentOffset` returned from server.');
            }
            if (typeof(response?.search?.result?.itemsPerPage) === 'number') {
                this.#itemsPerPage = parseInt(response.search.result.itemsPerPage);
            } else {
                console.warn('There is no `itemsPerPage` returned from server.');
            }
            if (typeof(response?.search?.result?.total) === 'number') {
                this.#totalItems = parseInt(response.search.result.total);
            } else {
                console.warn('There is no `total` returned from server.');
            }
            if (typeof(response?.search?.result?.pagesOffset) !== 'undefined') {
                this.#pagesOffset = response.search.result.pagesOffset;
            } else {
                console.warn('There is no `pagesOffset` returned from server.');
            }
            return Promise.resolve(response);
        })
    }// #ajaxSearch


    /**
     * Close search panel.
     * 
     * This method was called from `#listenClickOpenSearchPanel()`.
     * 
     * @returns {undefined}
     */
    #closeSearchPanel() {
        const searchPanelLink = document.getElementById(this.#openSearchPanelLinkId);
        searchPanelLink.classList.remove('active');
        this.#ListingPanel.closePanel();
        // clear loaded map layer group.
        this.#LibMaps.clearMapTimelineLayerGroup();
    }// #closeSearchPanel


    /**
     * Copy search panel container that is in `template` tag into listing panel contents element.
     * 
     * This method was called from `#openSearchPanel()`.
     * 
     * @returns {undefined}
     */
    #copySearchPanelContainerTemplateToListingPanel() {
        const listingPanelContents = document.getElementById(this.#Index.ListingPanel.listingPanelContentsId);
        if (listingPanelContents.querySelector('.pmtl-search-panel-container')) {
            // if already copied to listing panel contents.
            // do nothing here.
            return ;
        }

        let searchPanelCloned = '';
        if (this.#searchPanelContainerCache !== '') {
            searchPanelCloned = this.#searchPanelContainerCache;
        } else {
            const searchPanelContainerTemplate = document.getElementById(this.#searchPanelTemplateId);
            searchPanelCloned = searchPanelContainerTemplate.content.cloneNode(true);
        }

        this.#Index.ListingPanel.setPanelContents(searchPanelCloned);
    }// #copySearchPanelContainerTemplateToListingPanel


    /**
     * Display search result.
     * 
     * This method was called from `#listenFormSubmitSearch()`.
     * 
     * @param {Object} response
     * @returns {Promise}
     */
    #displaySearchResult(response) {
        if (typeof(response) !== 'object') {
            throw new Error('The argument `response` must be an object.');
        }

        const searchPlaceholder = document.getElementById(this.#searchPanelContentPlaceholderId);

        // set search result list placeholder. --------------------------
        let resultHTML = '';
        if (response?.search?.result && response?.search?.result?.total > 0) {
            resultHTML = '<p>Found total ' + response.search.result.total + ' places.';
            const totalDisplaying = (this.#currentOffset + this.#itemsPerPage);
            resultHTML += ' Displaying ' + (this.#currentOffset + 1) + ' - ' + (totalDisplaying <= this.#totalItems ? totalDisplaying : this.#totalItems) + '.';
            resultHTML += '</p>';
            resultHTML += '<ul class="pmtl-search-result-list">';
            response?.search?.result?.items?.forEach((row) => {
                resultHTML += '<li';
                resultHTML += '>';
                resultHTML += '<a class="' + this.#searchResultLinkClass + '"';
                resultHTML += ' data-pmtl-segment-id="' + row.id + '"';
                if (row.visit_id) {
                    resultHTML += ' data-pmtl-visit-id="' + row.visit_id + '"';
                }
                if (row.topCandidate_placeId) {
                    resultHTML += ' data-pmtl-place-id="' + row.topCandidate_placeId + '"';
                }
                if (row.topCandidate_placeLocation_latLng) {
                    resultHTML += ' data-pmtl-latlng="' + row.topCandidate_placeLocation_latLng + '"';
                }
                resultHTML += '>';// close of open `<a>`.
                if (row.place_name) {
                    resultHTML += row.place_name + ' ';
                }
                if (row.topCandidate_placeLocation_latLng) {
                    if (row.place_name) {
                        resultHTML += '(';
                    }
                    resultHTML += row.topCandidate_placeLocation_latLng;
                    if (row.place_name) {
                        resultHTML += ')';
                    }
                }
                resultHTML += '</a>';
                resultHTML += '</li>';
            });
            resultHTML += '</ul>';
        } else {
            resultHTML = '<ul class="pmtl-search-result-list"><li>There is no data</li></ul>';
        }
        searchPlaceholder.innerHTML = resultHTML;
        resultHTML = null;
        // end set search result list placeholder. ---------------------

        // set pagination in search list placeholder. --------------------
        let pagination = '';
        if (response?.search?.result?.pagesOffset?.length > 1) {
            // if there is pagination offset.
            pagination = '<nav id="pmtl-search-result-pagination" aria-label="Search result page navigaiton">';
            pagination += '<ul class="pagination justify-content-center">';
            pagination += '<li class="page-item pmtl-page-item-previous';
            if (this.#currentOffset <= 0) {
                pagination += ' disabled';
            }
            pagination += '"><a class="page-link pmtl-pagination-page-previous">Previous</a></li>';
            pagination += '<li class="page-item pmtl-page-item-next';
            if ((this.#currentOffset + this.#itemsPerPage) >= this.#totalItems) {
                pagination += ' disabled';
            }
            pagination += '"><a class="page-link pmtl-pagination-page-next">Next</a></li>';
            pagination += '</ul>';
            pagination += '</nav>';
        }// endif; there is pagination offset.
        searchPlaceholder.insertAdjacentHTML('beforeend', pagination);
        // end set pagination in search list placeholder. ---------------

        return Promise.resolve(response);
    }// #displaySearchResult


    /**
     * Listen click on search menu to show/hide search panel.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenClickOpenSearchPanel() {
        const searchPanelLink = document.getElementById(this.#openSearchPanelLinkId);

        if (searchPanelLink) {
            searchPanelLink.addEventListener('click', (event) => {
                event.preventDefault();
                if (searchPanelLink.classList.contains('active')) {
                    // if search panal is already opened.
                    this.#closeSearchPanel();
                } else {
                    // if search panel is not opened.
                    this.#openSearchPanel();
                }
            });
        }
    }// #listenClickOpenSearchPanel


    /**
     * Listen click on pagination of search result and then make AJAX call to update listing.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenClickPagination() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (!thisTarget.closest('#pmtl-search-result-pagination')) {
                // if not clicking in pagination.
                // do not work here.
                return ;
            } else {
                event.preventDefault();
            }

            if (this.#ajaxPaginationWorking === true) {
                console.log('AJAX pagination is working, please wait. Exiting.');
                return ;
            }

            if (thisTarget.closest('a')) {
                thisTarget = thisTarget.closest('a');
            }

            let nextOffset = this.#currentOffset;

            if (thisTarget.classList.contains('pmtl-pagination-page-previous')) {
                // if clicking previous.
                // remove disabled from next page list item (if exists).
                thisTarget.closest('.pmtl-page-item-next')?.classList?.remove('disabled');
                if (thisTarget.closest('.pmtl-page-item-previous')?.classList?.contains('disabled')) {
                    // if this pagination button is disabled
                    // do not work here.
                    return ;
                }
                // calculate previous offset for use as current offset and predict previous of previous offset to check if it is less than zero or not.
                nextOffset = (this.#currentOffset - this.#itemsPerPage);
                if (nextOffset < 0) {
                    // if previous offset is less than zero.
                    // do not work here.
                    return ;
                }
            } else if (thisTarget.classList.contains('pmtl-pagination-page-next')) {
                // if clicking next.
                // remove disabled from previous page list item (if exists).
                thisTarget.closest('.pmtl-page-item-previous')?.classList?.remove('disabled');
                if (thisTarget.closest('.pmtl-page-item-next')?.classList?.contains('disabled')) {
                    // if this pagination button is disabled
                    // do not work here.
                    return ;
                }
                // calculate next offset for use as current offset and predict next of next offset to check if it is over total items or not.
                nextOffset = (this.#currentOffset + this.#itemsPerPage);
                if (nextOffset > this.#totalItems) {
                    // if next offset is over total items.
                    // do not work here.
                    return ;
                }
            } else {
                // if clicking on something else.
                // do not work here.
                return ;
            }// endif; clicking on previous or next.

            const searchInput = document.getElementById(this.#searchInputId);
            // make AJAX call and display visited history data.
            this.#ajaxPaginationWorking = true;
            this.#ajaxSearch(searchInput.value, nextOffset)
            .then((response) => {
                // mark ajax pagination working to false.
                this.#ajaxPaginationWorking = false;
                return this.#displaySearchResult(response);
            })
            .then((response) => {
                // set search panel container cache.
                this.#setSearchPanelContainerCache();
                return Promise.resolve(response);
            })
            .catch((errorMsg) => {
                // on ERROR!
                // mark ajax pagination working to false.
                this.#ajaxPaginationWorking = false;
                return Promise.reject(errorMsg);
            })
            ;
        });
    }// #listenClickPagination


    /**
     * Listen click on search result item and popup the marker on maps.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenClickSearchResultItem() {
        document.addEventListener('click', (event) => {
            let thisTarget = event?.target;
            if (thisTarget?.closest('.' + this.#searchResultLinkClass)) {
                // remove all `active` class from list item.
                const searchResultUL = document.querySelector('.pmtl-search-result-list');
                searchResultUL?.querySelectorAll('li')?.forEach((item) => {
                    item.classList.remove('active');
                });
                // mark `active` class to this list item.
                thisTarget.closest('li')?.classList?.add('active');

                thisTarget = thisTarget?.closest('.' + this.#searchResultLinkClass);
                event.preventDefault();
                const openMapPopupOptions = {
                    'summaryVisitedPlaces': 'summaryVisitedPlace-' + thisTarget.dataset.pmtlLatlng,
                };
                this.#LibMaps.openMapPopup('', openMapPopupOptions);
            }
        });
    }// #listenClickSearchResultItem


    /**
     * Listen search form submit and make AJAX call to search.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenFormSubmitSearch() {
        document.addEventListener('submit', (event) => {
            let thisTarget = event.target;
            if (thisTarget.getAttribute('id') === this.#searchPanelFormId) {
                event.preventDefault();

                // set search panel container cache for use later such as switch between other panels.
                this.#setSearchPanelContainerCache();
                const searchInput = document.getElementById(this.#searchInputId);

                // make AJAX call to search.
                this.#ajaxSearch(searchInput.value)
                .then((response) => {
                    return this.#displaySearchResult(response);
                })
                .then((response) => {
                    // set search panel container cache again after rendered result.
                    this.#setSearchPanelContainerCache();
                    return Promise.resolve(response);
                });
            }
        });
    }// #listenFormSubmitSearch


    /**
     * Listen key up to make auto complete.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenKeyUpAutoComplete() {
        let searchDatalist;
        const disallowedKeys = [
            'Enter',
            'Escape',
            'ArrowDown',
            'ArrowUp',
            'ArrowLeft',
            'ArrowRight',
        ];
        // the code below is listen and work immediately. it will be reset datalist.
        document.addEventListener('keyup', (event) => {
            if (event?.target?.getAttribute('id') === this.#searchInputId) {
                if (disallowedKeys.includes(event.key)) {
                    return ;
                }

                // set search panel container cache for use later such as switch between other panels.
                this.#setSearchPanelContainerCache();
                // clear autocomplete data list to let the result set them later.
                searchDatalist = document.getElementById('pmtl-search-datalist');
                searchDatalist.innerHTML = '';
            }
        });

        // the code below is listen and delay before working.
        document.addEventListener('keyup', Utils.delay(
            (event) => {
                if (event?.target?.getAttribute('id') === this.#searchInputId) {
                    event.preventDefault();
                    if (disallowedKeys.includes(event.key)) {
                        return ;
                    }

                    if (!searchDatalist) {
                        searchDatalist = document.getElementById('pmtl-search-datalist');
                    }

                    // make AJAX call to query auto complete.
                    Ajax.fetchGet(appBasePath + '/HTTP/search.php?act=autocomplete&keyword=' + encodeURIComponent(event.target.value))
                    .then((response) => {
                        let datalistOptions = '';
                        if (response?.search?.autocomplete?.words && response?.search?.autocomplete?.words?.length >= 1) {
                            response.search.autocomplete.words.forEach((word) => {
                                datalistOptions += '<option value="' + word + '">' + word + '</option>';
                            });
                        }
                        searchDatalist.innerHTML = datalistOptions;
                    });
                }
            },
            500
        ));
    }// #listenKeyUpAutoComplete


    /**
     * Listen on search input events.
     * 
     * This method was called from `init()`.
     * 
     * @returns {undefined}
     */
    #listenSearchInputEvents() {
        // listen on "change" then trigger submit.
        document.addEventListener('change', (event) => {
            const thisTarget = event?.target;
            if (thisTarget?.getAttribute('id') === this.#searchInputId) {
                const searchForm = document.getElementById(this.#searchPanelFormId);
                searchForm.requestSubmit();
            }
        });
    }// #listenSearchInputEvents


    /**
     * Open search panel (based on listing panel).
     * 
     * This method will clear all active nav items, add active class to search navbar item, open search panel.
     * 
     * This method was called from `#listenClickOpenSearchPanel()`.
     * 
     * @returns {undefined}
     */
    #openSearchPanel() {
        // clear all actived items on navmenu.
        this.#Index.clearAllActiveNavItems();
        // clear loaded map layer group.
        this.#LibMaps.clearMapTimelineLayerGroup();

        this.#copySearchPanelContainerTemplateToListingPanel();

        // add active class to search menu on the navbar item (navmenu).
        const searchPanelLink = document.getElementById(this.#openSearchPanelLinkId);
        searchPanelLink.classList.add('active');

        // do open the search panel.
        this.#ListingPanel.openPanel();
    }// #openSearchPanel


    /**
     * Set search panel container cache.
     * 
     * This method was called from `#listenFormSubmitSearch()`, `#listenKeyUpAutoComplete()`.
     * 
     * @returns {undefined}
     */
    #setSearchPanelContainerCache() {
        const searchInput = document.getElementById(this.#searchInputId);
        // manually set value attribute to the input for use with cache.
        searchInput.setAttribute('value', searchInput.value);
        // set cache.
        const searchPanelContainer = document.querySelector('.pmtl-search-panel-container');
        this.#searchPanelContainerCache = searchPanelContainer.outerHTML;
    }// #setSearchPanelContainerCache


    /**
     * Initialize the class.
     * 
     * @returns {undefined}
     */
    init() {
        this.#listenClickOpenSearchPanel();

        this.#listenKeyUpAutoComplete();
        this.#listenFormSubmitSearch();
        this.#listenSearchInputEvents();
        this.#listenClickPagination();
        this.#listenClickSearchResultItem();
    }// init


}// SearchPanel