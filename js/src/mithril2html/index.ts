import {override} from 'flarum/common/extend';
import app from 'flarum/forum/app';
import mapRoutes from 'flarum/common/utils/mapRoutes';
import Drawer from 'flarum/common/utils/Drawer';
import Pane from 'flarum/forum/utils/Pane';
import Page from 'flarum/common/components/Page';
import ForumApplication from 'flarum/forum/ForumApplication';

class Index extends Page {
    view() {
        return m('h1', 'Mithril2Html');
    }
}

app.routes.index = {
    path: '/',
    component: Index,
};

// We override the mount method for two reasons
// First, to use hashbang routing, which requires extending in Application instead of ForumApplication (or we could need to rewrite a lot)
// Second, to remove unneeded features like modals/alerts/header
override(ForumApplication.prototype, 'mount', function (this: ForumApplication) {
    this.pane = new Pane(document.getElementById('app'));

    m.route.prefix = '#!';

    // Has to be kept for compatibility
    this.drawer = new Drawer();

    // Same as original
    m.route(document.getElementById('content'), '/', mapRoutes(this.routes));
});
