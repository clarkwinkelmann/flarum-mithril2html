module.exports=function(t){var e={};function o(r){if(e[r])return e[r].exports;var n=e[r]={i:r,l:!1,exports:{}};return t[r].call(n.exports,n,n.exports,o),n.l=!0,n.exports}return o.m=t,o.c=e,o.d=function(t,e,r){o.o(t,e)||Object.defineProperty(t,e,{enumerable:!0,get:r})},o.r=function(t){"undefined"!=typeof Symbol&&Symbol.toStringTag&&Object.defineProperty(t,Symbol.toStringTag,{value:"Module"}),Object.defineProperty(t,"__esModule",{value:!0})},o.t=function(t,e){if(1&e&&(t=o(t)),8&e)return t;if(4&e&&"object"==typeof t&&t&&t.__esModule)return t;var r=Object.create(null);if(o.r(r),Object.defineProperty(r,"default",{enumerable:!0,value:t}),2&e&&"string"!=typeof t)for(var n in t)o.d(r,n,function(e){return t[e]}.bind(null,n));return r},o.n=function(t){var e=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(e,"a",e),e},o.o=function(t,e){return Object.prototype.hasOwnProperty.call(t,e)},o.p="",o(o.s=7)}([function(t,e){t.exports=flarum.core.compat["common/extend"]},function(t,e){t.exports=flarum.core.compat["forum/app"]},function(t,e){t.exports=flarum.core.compat["common/utils/mapRoutes"]},function(t,e){t.exports=flarum.core.compat["common/utils/Drawer"]},function(t,e){t.exports=flarum.core.compat["forum/utils/Pane"]},function(t,e){t.exports=flarum.core.compat["common/components/Page"]},function(t,e){t.exports=flarum.core.compat["forum/ForumApplication"]},function(t,e,o){"use strict";function r(t,e){return(r=Object.setPrototypeOf||function(t,e){return t.__proto__=e,t})(t,e)}o.r(e);var n=o(0),u=o(1),c=o.n(u),p=o(2),i=o.n(p),a=o(3),f=o.n(a),l=o(4),s=o.n(l),d=o(5),y=o.n(d),b=o(6),x=o.n(b),v=function(t){var e,o;function n(){return t.apply(this,arguments)||this}return o=t,(e=n).prototype=Object.create(o.prototype),e.prototype.constructor=e,r(e,o),n.prototype.view=function(){return m("h1","Mithril2Html")},n}(y.a);c.a.routes.index={path:"/",component:v},Object(n.override)(x.a.prototype,"mount",(function(){this.pane=new s.a(document.getElementById("app")),m.route.prefix="#!",this.drawer=new f.a,m.route(document.getElementById("content"),"/",i()(this.routes))}))}]);
//# sourceMappingURL=mithril2html.js.map