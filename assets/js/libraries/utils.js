/**
 * Utilities.
 */


class Utils {


    
    /**
     * Delay before running callback function.
     * 
     * @link https://stackoverflow.com/a/1909508/128761 Original source code.
     * @param {Callback} fn The callback function.
     * @param {Number} ms Number to delay in milliseconds. (See `setTimeout`.)
     * @returns {Function}
    */
    static delay(fn, ms) {
        let timer = 0;
        return function (...args) {
            clearTimeout(timer);
            timer = setTimeout(fn.bind(this, ...args), ms || 0);
        }
    }// delay


    /**
     * Format date to YYYY-MM-DD.
     * 
     * @link https://stackoverflow.com/a/23593099/128761 Original source code.
     * @param {string|Date} date 
     * @returns {string}
     */
    static formatDate(date) {
        let d;
        if (typeof(date) === 'string') {
            d = new Date(date);
        } else if (typeof(date) === 'object') {
            d = date;
        } else {
            throw new Error('The argument `date` must be string or `Date` object.');
        }

        var month = '' + (d.getMonth() + 1),
            day = '' + d.getDate(),
            year = d.getFullYear();
    
        if (month.length < 2) {
            month = '0' + month;
        }
        if (day.length < 2) {
            day = '0' + day;
        }
    
        return [year, month, day].join('-');
    }// formatDate


    /**
     * Format time to HH:MM
     * 
     * @param {Date} date 
     * @returns {string}
     */
    static formatTimeHM(date) {
        let d;
        if (typeof(date) === 'string') {
            d = new Date(date);
        } else if (typeof(date) === 'object') {
            d = date;
        } else {
            throw new Error('The argument `date` must be string or `Date` object.');
        }

        return d.toLocaleTimeString('en-US', {
            hour12: false,
            hour: '2-digit', 
            minute: '2-digit'
        });
    }// formatTimeHM


}// Utils
