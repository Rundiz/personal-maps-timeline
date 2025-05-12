/**
 * JS for index page.
 */


class Index {


    /**
     * @var object
     */
    #ajaxLoaded = {};


    /**
     * @type {String} Bootstrap dialog ID.
     */
    #bootstrapDialogId = 'pmtl-bs-modal';


    /**
     * @type {DialogElement}
     */
    #DialogElement;


    /**
     * @type {LibMaps}
     */
    #LibMaps;


    /**
     * @type {ListingPanel}
     */
    #ListingPanel;


    /**
     * @type {TimelinePanel}
     */
    #TimelinePanel;


    /**
     * JS for index page.
     */
    constructor() {
        this.#init();
    }// constructor


    /**
     * AJAX get edit place name form and its data.
     * 
     * This method was called from `#listenClickEditPlaceName()`.
     * 
     * @param {string} placeId The Google place ID.
     * @returns {Promise} Return AJAX response data.
     */
    #ajaxGetEditPlaceNameForm(placeId) {
        return Ajax.fetchGet(appBasePath + '/HTTP/edit-placename-form.php?placeId=' + encodeURIComponent(placeId))
        .then((response) => {
            return Promise.resolve(response);
        });
    }// #ajaxGetEditPlaceNameForm


    /**
     * AJAX get summary and then display since date, total visits, min and max input date.
     * 
     * This method was called from `#init()`.
     * 
     * @returns {Promise} Return AJAX response data.
     */
    #ajaxGetSummary() {
        return Ajax.fetchGet(appBasePath + '/HTTP/summary.php')
        .then((response) => {
            const mainNavbar = document.querySelector('#pmtl-main-navbar');
            const navbarNav = mainNavbar.querySelector('.navbar-nav');

            if (typeof(response?.recordDates) === 'object') {
                let summaryDateHTML = '<li class="nav-item dropdown">'
                + '<a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">'
                + 'Since: '
                + response.recordDates.sinceYear + ' - ' + response.recordDates.latestDate
                + '</a>'
                + '<ul class="dropdown-menu">'
                + '<li><a class="pmtl-nav-summary-date-eachyear dropdown-item" data-year="*">All'
                + ' <small class="text-secondary fw-light" title="Total visits" aria-label="Total visits">(' + response.totalVisit.unique + ')</small>'
                + '</a></li>';
                for (let i = response.recordDates.sinceYear; i <= response.recordDates.latestYear; ++i) {
                    summaryDateHTML += '<li><a class="pmtl-nav-summary-date-eachyear dropdown-item" data-year="' + i + '">' + i;
                    summaryDateHTML += ' <small class="text-secondary fw-light" title="Total visits" aria-label="Total visits">(' + response.summaryPerYear[i]?.totalVisitU + ')</small>';
                    summaryDateHTML += '</a>';
                    summaryDateHTML += '</li>';
                }
                summaryDateHTML += '</ul>';
                summaryDateHTML += '</li>';
                navbarNav.insertAdjacentHTML('beforeend', summaryDateHTML);

                IndexJSObject.ajaxGetSummary.recordDates = response.recordDates;
            }

            if (typeof(response.visitedPlaces) === 'object') {
                this.#ajaxLoaded.summaryVisitedPlaces = response.visitedPlaces;
            }

            return Promise.resolve(response);
        });
    }// #ajaxGetSummary


    /**
     * AJAX get summary data by year and then draw year summary on the maps.
     * 
     * This method was called from `#listenClickNavSummaryDateDropdown()`.
     * 
     * @param {Number} selectedYear Selected year
     * @returns {Promise} Return AJAX response data.
     */
    #ajaxGetSummaryByYear(selectedYear) {
        if (
            (
                typeof(selectedYear) !== 'number' && 
                typeof(selectedYear) !== 'string'
            ) ||
            !/^-?\d+$/.test(selectedYear)
        ) {
            return Promise.reject('Selected year is not number.' + typeof(selectedYear));
        }

        return Ajax.fetchGet(appBasePath + '/HTTP/summary-by-year.php?year=' + encodeURIComponent(selectedYear))
        .then((response) => {
            const navTotalVisitElement = document.getElementById('pmtl-nav-total-visit');
            if (navTotalVisitElement) {
                navTotalVisitElement.innerText = response?.visitedPlacesYear?.total;
            }
            this.#LibMaps.drawYearSummary(response?.visitedPlacesYear);
            return Promise.resolve(response);
        });
    }// #ajaxGetSummaryByYear


    /**
     * Initialize the class.
     * 
     * Use this instead of in constructor because constructor did not support `async`.
     */
    async #init() {
        await this.#ajaxGetSummary();

        this.#listenDefaultMapLoaded();
        this.#LibMaps = new LibMaps(this);
        this.#setupDefaultMap();

        this.#ListingPanel = new ListingPanel(this.#LibMaps, this);
        this.#ListingPanel.init();

        this.#TimelinePanel = new TimelinePanel(this.#LibMaps, this);
        this.#TimelinePanel.init();

        this.#DialogElement = new DialogElement(this);
        this.#DialogElement.init();

        this.#listenClickEditPlaceName();
        this.#listenFormSubmitEditPlaceName();

        this.#listenClickOutsideCloseNavMenu();
        this.#listenClickNavSummaryDateDropdown();
    }// #init


    /**
     * Listen on click edit place name and do its tasks.
     * 
     * This method was called from `#init()`.
     */
    #listenClickEditPlaceName() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('.pmtl-edit-placename')) {
                thisTarget = thisTarget.closest('.pmtl-edit-placename');
                event.preventDefault();
            } else {
                return ;
            }

            this.#DialogElement.removeDialogContents();
            this.#DialogElement.setDialogContents('Edit place name', null);

            // AJAX get edit place name form and its data.
            this.#ajaxGetEditPlaceNameForm(thisTarget.dataset.placeId)
            .then((response) => {
                this.#DialogElement.setDialogContents(null, response?.result?.htmlForm);
            });

            // set focus on the form in the dialog element.
            const bsModal = document.getElementById(this.#DialogElement.bootstrapDialogId);
            bsModal.addEventListener('shown.bs.modal', () => {
                bsModal.querySelector('#place_name')?.focus();
            });// end event listener shown.bs.modal
        });
    }// #listenClickEditPlaceName


    /**
     * Listen on click summary date > dropdown item to display summary of selected year.
     * 
     * This method was called from `#init()`.
     */
    #listenClickNavSummaryDateDropdown() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('.pmtl-nav-summary-date-eachyear')) {
                // if clicking on summary date dropdown.
                thisTarget = thisTarget.closest('.pmtl-nav-summary-date-eachyear');
                event.preventDefault();

                // close timeline panel (if opened) and also clear timeline layer group (lot of markers from timeline panels).
                this.#TimelinePanel.closeTimelinePanel();

                // un-active all dropdown items.
                this.clearAllActiveNavItems();

                if (!isNaN(thisTarget.dataset.year)) {
                    // if selected year is number
                    IndexJSObject.summaryDateSelectedYear = thisTarget.dataset.year;
                    // mark current item as active
                    thisTarget.classList.add('active');
                    // mark parent navbar item as active
                    const navItem = thisTarget.closest('.nav-item');
                    const navItemLink = navItem?.querySelector('.nav-link');
                    if (navItemLink) {
                        navItemLink?.classList?.add('active');
                    }

                    this.#ajaxGetSummaryByYear(thisTarget.dataset.year);
                } else {
                    // if selected year is not number.
                    // restore real total visit value on navbar.
                    const navTotalVisitElement = document.getElementById('pmtl-nav-total-visit');
                    if (navTotalVisitElement) {
                        navTotalVisitElement.innerText = navTotalVisitElement.dataset.totalValue;
                    }
                }// endif; selected year is number.

                if (this.#LibMaps.isPathsTraveledLayerGroupActived()) {
                    this.ajaxGetSummaryPathsTraveled();
                }
            }// endif; there is a class.
        });
    }// #listenClickNavSummaryDateDropdown


    /**
     * Listen on click outside navbar menu then close it.
     * 
     * This method was called from `#init()`.
     */
    #listenClickOutsideCloseNavMenu() {
        document.addEventListener('click', (event) => {
            const thisTarget = event.target;
            if (thisTarget?.closest('#pmtl-main-navbar')) {
                // if clicked inside main navbar element. do nothing.
            } else {
                // if clicked outside main navbar element. close it using Bootstrap way.
                const bsNavCollapse = new bootstrap.Collapse('#navbarSupportedContent', {
                    toggle: false,
                });
                bsNavCollapse.hide();
            }
        });
    }// #listenClickOutsideCloseNavMenu


    /**
     * Listen on default map loaded then clear property `#ajaxLoaded.summaryVisitedPlaces` to free memory.
     * 
     * This method was called from `#init()`.
     */
    #listenDefaultMapLoaded() {
        document.addEventListener('pmtl.default.maps.loaded', () => {
            // clear summary visited places to free memory.
            this.#ajaxLoaded.summaryVisitedPlaces = null;
        });
    }// #listenDefaultMapLoaded


    /**
     * Listen form submit on edit place name form and make AJAX save.
     * 
     * This method was called from `#init()`.
     * 
     * @returns {undefined}
     */
    #listenFormSubmitEditPlaceName() {
        document.addEventListener('submit', (event) => {
            let thisTarget = event.target;
            if (thisTarget.getAttribute('id') === 'pmtl-edit-place-name-form') {
                event.preventDefault();

                const formData = new FormData();
                const placeIdInput = thisTarget.querySelector('#place_id');
                const placeNameInput = thisTarget.querySelector('#place_name');
                formData.set('place_id', placeIdInput?.value);
                formData.set('place_name', placeNameInput?.value);

                const fetchOptions = {
                    'body': new URLSearchParams(formData),
                    'content-type': 'application/x-www-form-urlencoded',
                };
                Ajax.fetchPost(appBasePath + '/HTTP/edit-placename-save.php', fetchOptions)
                .then((response) => {
                    if (response?.result?.success && response.result.success === true) {
                        // if succeeded.
                        document.querySelectorAll('.place-title-placement.place-id-' + placeIdInput?.value)?.forEach((item) => {
                            item.innerText = placeNameInput?.value;
                        });
                        this.#DialogElement.closeDialog(true);
                    } else {
                        // if failed.
                    }
                });
            }
        });
    }// #listenFormSubmitEditPlaceName


    /**
     * Setup default map.
     * 
     * This method was called from `#init()`.
     */
    #setupDefaultMap() {
        this.#LibMaps.setupDefaultMap(this.#ajaxLoaded.summaryVisitedPlaces);
    }// #setupDefaultMap


    /**
     * AJAX get summary of paths traveled and then draw the paths.
     * 
     * This method was called from `LibMaps.#listenMapOverlayPathsTraveledSelected()`.
     * 
     * @returns {Promise} Return AJAX response data.
     */
    ajaxGetSummaryPathsTraveled() {
        let selectedYear = '';
        if (IndexJSObject.summaryDateSelectedYear !== null) {
            selectedYear = IndexJSObject.summaryDateSelectedYear;
        }

        return Ajax.fetchGet(appBasePath + '/HTTP/summary-paths-traveled.php?year=' + encodeURIComponent(selectedYear))
        .then((response) => {
            this.#LibMaps.drawPathsTraveled(response);
            return Promise.resolve(response);
        });
    }// ajaxGetSummaryPathsTraveled


    /**
     * Clear all actived navbar items.
     * 
     * This method must be able to call from outside this class.
     */
    clearAllActiveNavItems() {
        const navbarNav = document.querySelector('.navbar-nav');
        const activedItems = navbarNav?.querySelectorAll('.active');
        if (activedItems) {
            activedItems.forEach((item) => {
                item.classList.remove('active');
            });
        }

        // also reset selected year on the JS object to `null`.
        IndexJSObject.summaryDateSelectedYear = null;
    }// clearAllActiveNavItems


    /**
     * Get ListingPanel class.
     * 
     * @type {ListingPanel} Listing panel class instance.
     */
    get ListingPanel() {
        return this.#ListingPanel;
    }// ListingPanel


    /**
     * Get TimelinePanel class.
     * 
     * @type {TimelinePanel} Timeline panel class instance.
     */
    get TimelinePanel() {
        return this.#TimelinePanel;
    }// TimelinePanel


}// Index


// Start JS on page loaded all HTML. ===================
window.addEventListener('DOMContentLoaded', () => {
    const indexPageObj = new Index();
});