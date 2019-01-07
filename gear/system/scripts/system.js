import { GTable, GColumn } from './table'

function install(Vue) {

    var gear = window.$gear;
    var lang = window.$lang;

    Vue.config.debug = gear.debug;
    Vue.config.productionTip = gear.debug;

    var axios = require('axios'),
        $ = require('jquery'),
        vsprintf = require('sprintf-js').vsprintf;

    Vue.prototype.$api = axios.create({
        baseURL: gear.url + '/api',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    function getLang(name, array) {
        if(name in lang) {
            if(array !== undefined) {
                return vsprintf(lang[name], array);
            }
            return lang[name];
        }
        return name;
    }

    Vue.prototype.$message = function(message, type, stay) {
        return this.$api.post('/addMessage', {
            message: {
                message: message,
                type: type,
                stay: stay
            }
        });
    };

    Vue.filter('lang', function(name, array) {
        return getLang(name, array);
    });

    Vue.prototype.$lang = function(name, array) {
        return getLang(name, array);
    };

    Vue.prototype.$visibility = require('visibilityjs');

    new Vue({
        el: '#gear',
        components: {
            GTable,
            GColumn
        }
    });

}

if(window.Vue) {
    Vue.use(install);
}
