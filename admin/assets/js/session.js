!function(a){a.session={_id:null,_cookieCache:void 0,_init:function(){window.name||(window.name=Math.random()),this._id=window.name,this._initCache();var a=new RegExp(this._generatePrefix()+"=([^;]+);").exec(document.cookie);if(a&&document.location.protocol!==a[1]){this._clearSession();for(var b in this._cookieCache)try{window.sessionStorage.setItem(b,this._cookieCache[b])}catch(a){}}document.cookie=this._generatePrefix()+"="+document.location.protocol+";path=/;expires="+new Date((new Date).getTime()+12e4).toUTCString()},_generatePrefix:function(){return"__session:"+this._id+":"},_initCache:function(){var a=document.cookie.split(";");this._cookieCache={};for(var b in a){var c=a[b].split("=");new RegExp(this._generatePrefix()+".+").test(c[0])&&c[1]&&(this._cookieCache[c[0].split(":",3)[2]]=c[1])}},_setFallback:function(a,b,c){var d=this._generatePrefix()+a+"="+b+"; path=/";return c&&(d+="; expires="+new Date(Date.now()+12e4).toUTCString()),document.cookie=d,this._cookieCache[a]=b,this},_getFallback:function(a){return this._cookieCache||this._initCache(),this._cookieCache[a]},_clearFallback:function(){for(var a in this._cookieCache)document.cookie=this._generatePrefix()+a+"=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;";this._cookieCache={}},_deleteFallback:function(a){document.cookie=this._generatePrefix()+a+"=; path=/; expires=Thu, 01 Jan 1970 00:00:01 GMT;",delete this._cookieCache[a]},get:function(a){return window.sessionStorage.getItem(a)||this._getFallback(a)},set:function(a,b,c){try{window.sessionStorage.setItem(a,b)}catch(a){}return this._setFallback(a,b,c||!1),this},delete:function(a){return this.remove(a)},remove:function(a){try{window.sessionStorage.removeItem(a)}catch(a){}return this._deleteFallback(a),this},_clearSession:function(){try{window.sessionStorage.clear()}catch(b){for(var a in window.sessionStorage)window.sessionStorage.removeItem(a)}},clear:function(){return this._clearSession(),this._clearFallback(),this}},a.session._init()}(jQuery);
