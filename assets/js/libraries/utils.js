/**
 * Utilities.
 */


class Utils {


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
