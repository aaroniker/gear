function install(Vue) {

    var gear = window.$gear;
    var lang = window.$lang;

    Vue.config.debug = gear.debug;
    Vue.config.productionTip = gear.debug;

    var axios = require('axios');
    var $ = require('jquery');

    Vue.prototype.$api = axios.create({
        baseURL: gear.url + '/api',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    });

    function getLang(name) {
        if(name in lang) {
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

    Vue.filter('lang', function(name) {
        return getLang(name);
    });

    Vue.prototype.$lang = function(name) {
        return getLang(name);
    };

    Vue.prototype.$visibility = require('visibilityjs');

    new Vue({
        el: '#gear'
    });

}

if(window.Vue) {
    Vue.use(install);
}
