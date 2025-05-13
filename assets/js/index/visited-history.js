/**
 * Visited history JS on index page.
 * 
 * This work on the process of "Map pop up" > click on "View all visited history".
 */


class VisitedHistory {


    /**
     * @type {Boolean} Mark that current AJAX pagination of list visited history is working or not.
     */
    #ajaxPaginationWorking = false;


    /**
     * @type {String} Clicked on visited date.
     */
    #clickedVisitedDate = '';


    /**
     * @type {Integer} Number of current offset of visited history dates.
     */
    #currentOffset = 0;


    /**
     * @type {String} Current selected month filter.
     */
    #currentSelectedMonth = '';


    /**
     * @type {String} Current selected year filter.
     */
    #currentSelectedYear = '';


    /**
     * @type {String} Filter form HTML ID.
     */
    #filterFormId = 'pmtl-visited-history-form';


    /**
     * @type {String} Filter of Google Place ID HTML ID name.
     */
    #filterPlaceIdHTMLId = 'pmtl-visited-place-id';


    /**
     * @type {String} Filter select month HTML ID.
     */
    #filterSelectMonthId = 'pmtl-visited-history-month';


    /**
     * @type {String} Filter select year HTML ID.
     */
    #filterSelectYearId = 'pmtl-visited-history-year';


    /**
     * @type {DialogElement}
     */
    #DialogElement;

    
    /**
     * @type {Index}
     */
    #Index;


    /**
     * @type {Integer} Number of items per page.
     */
    #itemsPerPage = 10;


    /**
     * @type {String} Active class name of list item of visited dates.
     */
    #listVisitedDateItemActiveClass = 'pmtl-visited-history-list-item-active';


    /**
     * @type {Array|Integer[]} Available pages offset that calculated from server.
     */
    #pagesOffset = [0];


    /**
     * @type {String} The Google Place ID. This is for use between methods.
     */
    #placeId = '';


    /**
     * @type {Integer} Total items available on DB before paginated.
     */
    #totalItems = 0;


    /**
     * Visited history class constructor.
     * 
     * @param {Index} Index The `Index` class.
     */
    constructor(Index) {
        if (typeof(Index) === 'object') {
            this.#Index = Index;
        }
        this.#DialogElement = this.#Index.DialogElement;

        this.#listenClickViewAllVisitedHistory();

        this.#listenFormSubmitVisitedHistoryFilter();
        this.#listenChangeFilterSelectBox();
        this.#listenClickPagination();
        this.#listenClickDateVisited();
    }// constructor


    /**
     * AJAX get visited history and then display it via another method.
     * 
     * This method was called from `#listenClickViewAllVisitedHistory()`.
     * 
     * @param {String} placeId The Google place ID refer from DB.
     * @param {Integer} offset The offset to retrieve visited dates list.
     * @param {String} selectedYear Selected year. If none select, set this to empty string.
     * @param {String} selectedMonth Selected month. If none select, set this to empty string.
     * @returns {Promise} Return AJAX response data.
     */
    #ajaxGetVisitedHistory(placeId, offset = 0, selectedYear = '', selectedMonth = '') {
        if (typeof(selectedYear) !== 'string') {
            throw new Error('The argument `selectedYear` must be string.');
        }
        if (typeof(selectedMonth) !== 'string') {
            throw new Error('The argument `selectedMonth` must be string.');
        }

        let url = appBasePath + '/HTTP/visited-history.php';
        let queryStrings = [];
        queryStrings.push('placeId=' + encodeURIComponent(placeId));
        queryStrings.push('offset=' + encodeURIComponent(offset));
        if (selectedYear !== '') {
            queryStrings.push('year=' + encodeURIComponent(selectedYear));
        }
        if (selectedMonth !== '') {
            queryStrings.push('month=' + encodeURIComponent(selectedMonth));
        }
        if (queryStrings.length > 0) {
            url += '?' + queryStrings.join('&');
        }

        return Ajax.fetchGet(url)
        .then((response) => {
            // set current data.
            if (typeof(response?.visitedHistory?.datesVisited?.currentOffset) === 'number') {
                this.#currentOffset = parseInt(response.visitedHistory.datesVisited.currentOffset);
            } else {
                console.warn('There is no `currentOffset` returned from server.');
            }
            if (typeof(response?.visitedHistory?.datesVisited?.itemsPerPage) === 'number') {
                this.#itemsPerPage = parseInt(response.visitedHistory.datesVisited.itemsPerPage);
            } else {
                console.warn('There is no `itemsPerPage` returned from server.');
            }
            if (typeof(response?.visitedHistory?.datesVisited?.total) === 'number') {
                this.#totalItems = parseInt(response.visitedHistory.datesVisited.total);
            } else {
                console.warn('There is no `total` returned from server.');
            }
            if (typeof(response?.visitedHistory?.datesVisited?.pagesOffset) !== 'undefined') {
                this.#pagesOffset = response.visitedHistory.datesVisited.pagesOffset;
            } else {
                console.warn('There is no `pagesOffset` returned from server.');
            }

            return Promise.resolve(response);
        })
        ;
    }// #ajaxGetVisitedHistory


    /**
     * Copy template of visited history to the dialog.
     * 
     * This method was called from `#listenClickViewAllVisitedHistory()`.
     * 
     * @returns {undefined}
     */
    #copyTemplateToDialog() {
        // setup template into dialog. -----------------------------------
        const visitedHistoryDialogTemplate = document.getElementById('pmtl-dialog-visited-history-template');
        const visitedHistoryDialogCloned = visitedHistoryDialogTemplate.content.cloneNode(true);
        let div = document.createElement('div');
        div.appendChild(visitedHistoryDialogCloned);
        this.#DialogElement.setDialogContents(null, div.innerHTML);
        div = null;
        // end setup template into dialog. ------------------------------
    }// #copyTemplateToDialog


    /**
     * Display visited history data after AJAX requested.
     * 
     * This method was called from `#listenClickPagination()`, `#listenClickViewAllVisitedHistory()`, `#listenFormSubmitVisitedHistoryFilter()`.  
     * This method must be called after `#ajaxGetVisitedHistory()`.
     * 
     * @param {Object} response
     * @returns {Promise} Return Promise object with `response` argument data.
     */
    #displayVisitedHistoryData(response) {
        const visitedHistoryDatesPlaceholder = document.getElementById('pmtl-visited-history-dates-placeholder');

        // set dates visited list to dialog list placeholder. -------------
        let datesVisitedList = '';
        if (response?.visitedHistory?.datesVisited && response?.visitedHistory?.datesVisited?.total > 0) {
            // if there are dates visited result.
            datesVisitedList = '<p>Total ' + response.visitedHistory.datesVisited.total + ' visited.';
            const totalDisplaying = (this.#currentOffset + this.#itemsPerPage);
            datesVisitedList += ' Displaying ' + (this.#currentOffset + 1) + ' - ' + (totalDisplaying <= this.#totalItems ? totalDisplaying : this.#totalItems) + '.';
            datesVisitedList += '</p>';
            datesVisitedList += '<ul class="pmtl-dates-visited-list">';
            response?.visitedHistory?.datesVisited?.items?.forEach((row) => {
                datesVisitedList += '<li';
                if (this.#clickedVisitedDate !== '' && this.#clickedVisitedDate === row.dateVisited) {
                    datesVisitedList += ' class="' + this.#listVisitedDateItemActiveClass + '"';
                }
                datesVisitedList += '>';
                datesVisitedList += '<a class="marker-popup-year-visited" data-pmtl-visit-id="' + row.visit_id + '" data-pmtl-last-visit-date="' + row.dateVisited + '">';
                datesVisitedList += row.dateVisited;
                datesVisitedList += '</a>';
                datesVisitedList += '</li>';
            });
            datesVisitedList += '</ul>';
        } else {
            datesVisitedList = '<ul class="pmtl-dates-visited-list"><li>There is no data</li></ul>';
        }// endif; there are dates visited result.
        visitedHistoryDatesPlaceholder.innerHTML = datesVisitedList;
        datesVisitedList = null;
        // end set dates visited list to dialog list placeholder. --------

        // set pagination in dialog list placeholder. --------------------
        let pagination = '';
        if (response?.visitedHistory?.datesVisited?.pagesOffset?.length > 1) {
            // if there is pagination offset.
            pagination = '<nav id="pmtl-visited-history-pagination" aria-label="Dates visited page navigaiton">';
            pagination += '<ul class="pagination justify-content-center">';
            pagination += '<li class="page-item pmtl-page-item-previous';
            if (this.#currentOffset <= 0) {
                pagination += ' disabled';
            }
            pagination += '"><a class="page-link pmtl-pagination-page-previous">Previous</a></li>';
            pagination += '<li class="page-item pmtl-page-item-next';
            if ((this.#currentOffset + this.#itemsPerPage) > this.#totalItems) {
                pagination += ' disabled';
            }
            pagination += '"><a class="page-link pmtl-pagination-page-next">Next</a></li>';
            pagination += '</ul>';
            pagination += '</nav>';
        }// endif; there is pagination offset.
        visitedHistoryDatesPlaceholder.insertAdjacentHTML('beforeend', pagination);
        // end set pagination in dialog list placeholder. ---------------

        return Promise.resolve(response);
    }// #displayVisitedHistoryData


    /**
     * Listen on change select box then trigger click submit on the filter form.
     * 
     * This method was called from `constructor()`.
     * 
     * @returns {undefined}
     */
    #listenChangeFilterSelectBox() {
        document.addEventListener('change', (event) => {
            const thisTarget = event.target;
            if (thisTarget.closest('#' + this.#filterFormId)) {
                event.preventDefault();

                if (thisTarget.getAttribute('id') === this.#filterSelectYearId) {
                    this.#currentSelectedYear = thisTarget.value;
                }
                if (thisTarget.getAttribute('id') === this.#filterSelectMonthId) {
                    this.#currentSelectedMonth = thisTarget.value;
                }

                const selectYear = document.getElementById(this.#filterSelectYearId);
                const selectMonth = document.getElementById(this.#filterSelectMonthId);
                if (thisTarget.getAttribute('id') === this.#filterSelectYearId && selectYear.value === '') {
                    selectMonth.value = '';
                }
                if (selectMonth.value !== '' && selectYear.value === '') {
                    return ;
                }

                const thisForm = thisTarget.closest('#' + this.#filterFormId);
                thisForm.querySelector('button[type="submit"]')?.click();
            }
        });
    }// #listenChangeFilterSelectBox


    /**
     * Listen click on date visited on the list and then close dialog.  
     * Also mark list item of current link as active.
     * 
     * This method was called from `constructor()`.
     * 
     * @returns {undefined}
     */
    #listenClickDateVisited() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('a')) {
                thisTarget = thisTarget.closest('a');
            }

            if (thisTarget.classList.contains('marker-popup-year-visited')) {
                // if user is clicking on date visited link.
                // remove all active class.
                this.#clickedVisitedDate = '';
                document.querySelectorAll('.' + this.#listVisitedDateItemActiveClass)?.forEach((item) => {
                    item.classList.remove(this.#listVisitedDateItemActiveClass);
                });
                // add active class to current list item.
                thisTarget.closest('li')?.classList?.add(this.#listVisitedDateItemActiveClass);
                // mark clicked visited date.
                this.#clickedVisitedDate = thisTarget.dataset.pmtlLastVisitDate;
                // close dialog.
                this.#Index.DialogElement.closeDialog();
            }
        });
    }// #listenClickDateVisited


    /**
     * Listen click on pagination of visited history and then make AJAX call to update listing.
     * 
     * This method was called from `constructor()`.
     * 
     * @returns {undefined}
     */
    #listenClickPagination() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (!thisTarget.closest('#pmtl-visited-history-pagination')) {
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

            const visitedYearSelect = document.getElementById(this.#filterSelectYearId);
            const visitedMonthSelect = document.getElementById(this.#filterSelectMonthId);
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

            // make AJAX call and display visited history data.
            this.#ajaxPaginationWorking = true;
            this.#ajaxGetVisitedHistory(this.#placeId, nextOffset, visitedYearSelect.value, visitedMonthSelect.value)
            .then((response) => {
                this.#ajaxPaginationWorking = false;
                return this.#displayVisitedHistoryData(response);
            })
            .catch((errorMsg) => {
                this.#ajaxPaginationWorking = false;
                return Promise.reject(errorMsg);
            })
            ;
        });
    }// #listenClickPagination


    /**
     * Listen click on "View all visited history" from map popup.
     * 
     * This method was called from `constructor()`.
     * 
     * @returns {undefined}
     */
    #listenClickViewAllVisitedHistory() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('.pmtl-view-all-visited-history')) {
                thisTarget = thisTarget.closest('.pmtl-view-all-visited-history');
                event.preventDefault();
            } else {
                return ;
            }

            const bootstrapDialog = document.getElementById(this.#Index.DialogElement.bootstrapDialogId);
            const filterInputPlaceId = bootstrapDialog?.querySelector('#' + this.#filterPlaceIdHTMLId);
            if (filterInputPlaceId?.value === thisTarget.dataset.pmtlPlaceId) {
                // if dialog content is already exists on the same place.
                // do not work here.
                return ;
            }

            this.#placeId = thisTarget.dataset.pmtlPlaceId;
            this.#DialogElement.removeDialogContents();
            const placeName = thisTarget.parentElement?.parentElement?.querySelector('.place-title-placement').textContent;
            this.#DialogElement.setDialogContents(placeName, null);

            this.#copyTemplateToDialog();

            // AJAX list all visited history and use pagination.
            this.#ajaxGetVisitedHistory(thisTarget.dataset.pmtlPlaceId)
            .then((response) => {
                return this.#setPlaceIdAndDisplayYearsVisited(response);
            })
            .then((response) => {
                return this.#displayVisitedHistoryData(response);
            });
        });
    }// #listenClickViewAllVisitedHistory


    /**
     * Listen form visited history filter submitted and make AJAX request.
     * 
     * This method was called from `constructor()`.
     * 
     * @returns {undefined}
     */
    #listenFormSubmitVisitedHistoryFilter() {
        document.addEventListener('submit', (event) => {
            let thisTarget = event.target;
            if (thisTarget.getAttribute('id') === this.#filterFormId) {
                event.preventDefault();

                const placeIdInput = document.getElementById(this.#filterPlaceIdHTMLId);
                const visitedYearSelect = document.getElementById(this.#filterSelectYearId);
                const visitedMonthSelect = document.getElementById(this.#filterSelectMonthId);
                this.#ajaxGetVisitedHistory(placeIdInput.value, 0, visitedYearSelect.value, visitedMonthSelect.value)
                .then((response) => {
                    return this.#displayVisitedHistoryData(response);
                })
                ;
            }
        });
    }// #listenFormSubmitVisitedHistoryFilter


    /**
     * Setup Google Place ID to form and display years visited.
     * 
     * This method was called from `#listenClickViewAllVisitedHistory()`.  
     * This method must be called after `#ajaxGetVisitedHistory()`.
     * 
     * @param {Object} response The AJAX response.
     * @returns {Promise} Return Promise object with `response` argument data.
     */
    #setPlaceIdAndDisplayYearsVisited(response) {
        // set input hidden of placeId
        const placeIdInput = document.getElementById(this.#filterPlaceIdHTMLId);
        placeIdInput.value = this.#placeId;

        // set years retrieved from DB to form select box. ----------
        let yearSelect = document.getElementById(this.#filterSelectYearId);
        yearSelect.innerHTML = '<option value="">All</option>';
        if (response?.visitedHistory?.yearsVisited) {
            response.visitedHistory.yearsVisited?.forEach((year) => {
                yearSelect.insertAdjacentHTML('beforeend', '<option value="' + year + '">' + year + '</option>');
            });
        }
        // end set years retrieved from DB to form select box. -----

        return Promise.resolve(response);
    }// #setPlaceIdAndDisplayYearsVisited


    /**
     * Reset clicked on visited date.
     * 
     * This method must be able to call from outside this class.
     * 
     * @returns {undefined}
     */
    resetClickedVisitedDate() {
        this.#clickedVisitedDate = '';
        document.querySelectorAll('.' + this.#listVisitedDateItemActiveClass)?.forEach((item) => {
            item.classList.remove(this.#listVisitedDateItemActiveClass);
        });
    }// resetClickedVisitedDate


}// VisitedHistory