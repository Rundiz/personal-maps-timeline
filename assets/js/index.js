/**
 * JS for index page.
 */


class Index {


    /**
     * @var object
     */
    #ajaxLoaded = {};


    /**
     * @type {LibMaps}
     */
    #LibMaps;


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
     * @private This method was called from `#listenClickEditPlaceName()`.
     * @param {string} placeId 
     * @returns {Promise}
     */
    #ajaxGetEditPlaceNameForm(placeId) {
        return Ajax.fetchGet(appBasePath + '/HTTP/edit-placename-form.php?placeId=' + encodeURIComponent(placeId))
        .then((response) => {
            return Promise.resolve(response);
        });
    }// #ajaxGetEditPlaceNameForm


    /**
     * AJAX get summary.
     * 
     * @private This method was called from `#init()`.
     * @returns {Promise};
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
                + '<li><a class="pmtl-nav-summary-date-eachyear dropdown-item" data-year="*">All</a></li>';
                for (let i = response.recordDates.sinceYear; i <= response.recordDates.latestYear; ++i) {
                    summaryDateHTML += '<li><a class="pmtl-nav-summary-date-eachyear dropdown-item" data-year="' + i + '">' + i + '</a></li>';
                }
                summaryDateHTML += '</ul>';
                summaryDateHTML += '</li>';
                navbarNav.insertAdjacentHTML('beforeend', summaryDateHTML);

                const inputDate = document.getElementById('pmtl-timeline-control-date-input');
                inputDate.setAttribute('min', response.recordDates.sinceDate);
                inputDate.setAttribute('max', response.recordDates.latestDate);
            }

            if (typeof(response?.totalVisit) === 'object') {
                let summaryDateHTML = '<li class="nav-item">'
                + '<span class="nav-link navbar-text">'
                + 'Total visits: '
                + response.totalVisit.unique
                + '</span>'
                + '</li>';
                navbarNav.insertAdjacentHTML('beforeend', summaryDateHTML);
            }

            if (typeof(response.visitedPlaces) === 'object') {
                this.#ajaxLoaded.summaryVisitedPlaces = response.visitedPlaces;
            }

            return Promise.resolve(response);
        });
    }// #ajaxGetSummary


    /**
     * AJAX get summary data by year.
     * 
     * @private This method was called from `#listenClickNavSummaryDateDropdown()`.
     * @param {number} selectedYear Selected year
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
            this.#LibMaps.drawYearSummary(response?.visitedPlacesYear);
            return Promise.resolve(response);
        })
    }// #ajaxGetSummaryByYear


    /**
     * Initialize the class.
     * 
     * Use this instead of in constructor because constructor did not support `async`.
     */
    async #init() {
        await this.#ajaxGetSummary();

        this.#listenDefaultMapLoaded();
        this.#LibMaps = new LibMaps();
        this.#setupDefaultMap();

        this.#TimelinePanel = new TimelinePanel(this.#LibMaps, this);
        this.#TimelinePanel.init();

        this.#listenClickEditPlaceName();
        this.#listenFormSubmitEditPlaceName();

        this.#listenClickOutsideCloseNavMenu();
        this.#listenClickNavSummaryDateDropdown();
    }// #init


    /**
     * Listen on click edit place name.
     * 
     * @private This method was called from `#init()`.
     */
    #listenClickEditPlaceName() {
        const bsModal = document.getElementById('pmtl-bs-modal');
        const loadingP = document.getElementById('pmtl-bs-modal-loading');
        if (loadingP) {
            loadingP.classList.remove('d-none');
        }

        if (bsModal) {
            bsModal.addEventListener('show.bs.modal', (event) => {
                // Button that triggered the modal
                const button = event.relatedTarget;
                if (button?.dataset?.placeId) {
                    const modalBody = bsModal.querySelector('.modal-body');
                    const modalTitle = bsModal.querySelector('.modal-title');
                    modalTitle.textContent = 'Edit place name';

                    const nodes = modalBody.childNodes;
                    // remove everything except loading element. ----------------------
                    // remove element nodes first.
                    nodes.forEach((elm) => {
                        if (elm.nodeType === Node.ELEMENT_NODE && elm.id !== 'pmtl-bs-modal-loading') {
                            elm.remove();
                        }
                    });
                    // then remove non-element nodes.
                    nodes.forEach((elm) => {
                        if (elm.nodeType !== Node.ELEMENT_NODE) {
                            elm.parentNode.removeChild(elm)
                        }
                    });
                    // end remove everything except loading element. ------------------

                    // AJAX get edit place name form and its data.
                    this.#ajaxGetEditPlaceNameForm(button.dataset.placeId)
                    .then((response) => {
                        loadingP.classList.add('d-none');

                        modalBody.insertAdjacentHTML('beforeend', response?.result?.htmlForm);
                    });
                }
            });// end event listener show.bs.modal

            // make rendered form auto focus.
            bsModal.addEventListener('shown.bs.modal', () => {
                bsModal.querySelector('#place_name')?.focus();
            });// end event listener shown.bs.modal
        }
    }// #listenClickEditPlaceName


    /**
     * Listen on click summary date > dropdown item to display summary of selected year.
     * 
     * @private This method was called from `#init()`.
     */
    #listenClickNavSummaryDateDropdown() {
        document.addEventListener('click', (event) => {
            let thisTarget = event.target;
            if (thisTarget.closest('.pmtl-nav-summary-date-eachyear')) {
                // if clicking on summary date dropdown.
                thisTarget = thisTarget.closest('.pmtl-nav-summary-date-eachyear');
                event.preventDefault();

                // close timeline panel (if opened)
                this.#TimelinePanel.closeTimelinePanel();

                // un-active all dropdown items.
                this.clearAllActiveNavItems();

                if (!isNaN(thisTarget.dataset.year)) {
                    // mark current item as active
                    thisTarget.classList.add('active');
                    // mark parent navbar item as active
                    const navItem = thisTarget.closest('.nav-item');
                    const navItemLink = navItem?.querySelector('.nav-link');
                    if (navItemLink) {
                        navItemLink?.classList?.add('active');
                    }

                    this.#ajaxGetSummaryByYear(thisTarget.dataset.year);
                }// endif; selected year is number.
            }// endif; there is a class.
        });
    }// #listenClickNavSummaryDateDropdown


    /**
     * Listen on click outside navbar menu then close it.
     * 
     * @private This method was called from `#init()`.
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
     * Listen on default map loaded.
     * 
     * @private This method was called from `#init()`.
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
     * @private This method was called from `#init()`.
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
                const bsModal = document.getElementById('pmtl-bs-modal');

                Ajax.fetchPost(appBasePath + '/HTTP/edit-placename-save.php', fetchOptions)
                .then((response) => {
                    if (response?.result?.success && response.result.success === true) {
                        // if succeeded.
                        document.querySelectorAll('.place-title-placement.place-id-' + placeIdInput?.value)?.forEach((item) => {
                            item.innerText = placeNameInput?.value;
                        });
                        bsModal.querySelector('.btn-close')?.click();
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
     * @private This method was called from `#init()`.
     */
    #setupDefaultMap() {
        this.#LibMaps.setupDefaultMap(this.#ajaxLoaded.summaryVisitedPlaces);
    }// #setupDefaultMap


    /**
     * Clear all actived navbar items.
     */
    clearAllActiveNavItems() {
        const navbarNav = document.querySelector('.navbar-nav');
        const activedItems = navbarNav?.querySelectorAll('.active');
        if (activedItems) {
            activedItems.forEach((item) => {
                item.classList.remove('active');
            });
        }
    }// clearAllActiveNavItems


}// Index


window.addEventListener('DOMContentLoaded', () => {
    const indexPageObj = new Index();
});