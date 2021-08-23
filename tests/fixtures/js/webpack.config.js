const config = require('flarum-webpack-config')();

config.entry = {
    mithril2html: './mithril2html.js',
};

module.exports = config;
