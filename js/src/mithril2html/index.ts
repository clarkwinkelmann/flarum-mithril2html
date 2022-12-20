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
        return m('p', 'There was a problem rendering this content.');
    }
}

class Test extends Page {
    view() {
        return [
            m('p', 'This content was successfully generated via Mithril and Headless Chrome.'),
            '\n',
            m('p', ['User Agent: ', m('code', navigator.userAgent)]),
            '\n',
            m('p', 'Date: ' + (new Date())),
        ];
    }
}

class NotFound extends Page {
    view() {
        return m('p', 'There was a problem rendering this content. The component was not found.');
    }
}

// We override the mount method for two reasons
// First, to use hashbang routing, which requires extending in Application instead of ForumApplication (or we would need to rewrite a lot)
// Second, to remove unneeded features like modals/alerts/header
override(ForumApplication.prototype, 'mount', function (this: ForumApplication) {
    this.pane = new Pane(document.getElementById('app'));

    m.route.prefix = '#!';

    // Has to be kept for compatibility
    this.drawer = new Drawer();

    // Apply our internal routes last
    this.routes.index = {
        path: '/',
        component: Index,
    };
    this.routes.test = {
        path: '/test',
        component: Test,
    };
    this.routes.notFound = {
        path: '/:404...',
        component: NotFound,
    };

    console.info('Registered routes: ' + Object.keys(this.routes).map(routeName => routeName + ' (' + this.routes[routeName].path + ')').join(', '));

    // Same as original, with added catch block
    try {
        m.route(document.getElementById('content'), '/', mapRoutes(this.routes));
    } catch (error) {
        console.log('A Flarum boot error occurred');
        throw error;
    }
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

        // These 2 attributes often end up set via LinkButton
        delete vnode.attrs.active;
        delete vnode.attrs.force;

        // Remove class attribute if empty to simplify HTML
        if (typeof vnode.attrs.className === 'string' && vnode.attrs.className.trim() === '') {
            delete vnode.attrs.className;
        }
    }
});
