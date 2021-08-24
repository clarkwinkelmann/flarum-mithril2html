import {Vnode} from 'mithril';
import {extend, override} from 'flarum/common/extend';
import app from 'flarum/forum/app';
import mapRoutes from 'flarum/common/utils/mapRoutes';
import Drawer from 'flarum/common/utils/Drawer';
import Pane from 'flarum/forum/utils/Pane';
import Page from 'flarum/common/components/Page';
import ForumApplication from 'flarum/forum/ForumApplication';
import Link from 'flarum/common/components/Link';

class Index extends Page {
    view() {
        return m('p', 'There should be some content here. It probably failed rendering.');
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

extend(Link, 'initAttrs', function (returnValue: any, attrs: any) {
    // If the URL is already fully qualified, don't change anything
    if (/^https?:\/\//.test(attrs.href)) {
        return;
    }

    // If the URL was relative, even if it was already external
    // we compute the absolute URL before setting it back
    const url = new URL(attrs.href, app.forum.attribute('baseUrl'));

    attrs.href = url.href;
    attrs.external = true;
});

extend(Link.prototype, 'view', function (vnode: Vnode<any>) {
    // Flarum doesn't remove this element from the attrs before dumping in the DOM
    // It's a bit stupid to keep it in the exported HTML (also, it simplifies the integration tests)
    if (vnode && vnode.attrs) {
        delete vnode.attrs.external;
    }
});
