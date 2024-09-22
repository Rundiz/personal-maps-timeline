/**
 * JS for index page.
 */


class Index {


    /**
     * @var object
     */
    #ajaxLoaded = {};


    /**
     * @var LibMaps
     */
    #LibMaps;


    /**
     * JS for index page.
     */
    constructor() {
        this.#init();
    }// constructor


    /**
     * AJAX get summary.
     * 
     * @returns {Promise};
     */
    #ajaxGetSummary() {
        return Ajax.fetchGet(appBasePath + '/HTTP/summary.php')
        .then((response) => {
            const mainNavbar = document.querySelector('#pmtl-main-navbar');
            const navbarNav = mainNavbar.querySelector('.navbar-nav');

            if (typeof(response?.recordDates) === 'object') {
                let summaryDateHTML = '<li class="nav-item">'
                + '<span class="nav-link navbar-text">'
                + 'Since: '
                + response.recordDates.sinceYear + ' - ' + response.recordDates.latestDate
                + '</span>'
                + '</li>';
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
                this.#ajaxGetSummary.summaryVisitedPlaces = response.visitedPlaces;
            }

            return Promise.resolve(response);
        });
    }// #ajaxGetSummary


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

        const timelinePanelObj = new TimelinePanel(this.#LibMaps);
        timelinePanelObj.init();

        this.#listenClickOutsideCloseNavMenu();
    }// #init


    /**
     * Listen on click outside navbar menu then close it.
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
     */
    #listenDefaultMapLoaded() {
        document.addEventListener('pmtl.default.maps.loaded', () => {
            // clear summary visited places to free memory.
            this.#ajaxGetSummary.summaryVisitedPlaces = null;
        });
    }// #listenDefaultMapLoaded


    /**
     * Setup default map.
     */
    #setupDefaultMap() {
        this.#LibMaps.setupDefaultMap(this.#ajaxGetSummary.summaryVisitedPlaces);
    }// #setupDefaultMap


}// Index


window.addEventListener('DOMContentLoaded', () => {
    const indexPageObj = new Index();
});